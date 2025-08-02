<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Wallet;
use App\Jobs\MatchOrdersBatchJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

// 0=open, 1=partially, 2=filled, 3=cancelled

class FuturesMatchingService
{
	public function matchOrders_old($symbol)
	{
		$table = 'orders_' . str_replace('/', '_', strtolower($symbol));
		$cacheKey = "open_orders:{$symbol}";
		$orders = Order::forSymbol($symbol)
			->whereIn('status', [0, 1])
			->get();
		Redis::set($cacheKey, json_encode($orders));

		return DB::transaction(function () use ($symbol, $table) {
			$buyOrders = Order::forSymbol($symbol)
				->where('type', 'buy')
				->whereIn('status', [0, 1])
				->orderBy('price', 'desc')
				->orderBy('created_at', 'asc')
				->lockForUpdate()
				->get();

			$sellOrders = Order::forSymbol($symbol)
				->where('type', 'sell')
				->whereIn('status', [0, 1])
				->orderBy('price', 'asc')
				->orderBy('created_at', 'asc')
				->lockForUpdate()
				->get();

			foreach ($buyOrders as $buyOrder) {
				foreach ($sellOrders as $sellOrder) {
					if ($buyOrder->price >= $sellOrder->price) {
						$this->processMatch($buyOrder, $sellOrder, $symbol);
					}
				}
			}
		});
	}

	public function matchOrders(string $symbol): void
	{
		$buyOrders = Order::forSymbol($symbol)
			->where('type', 'buy')
			->whereIn('status', [0, 1])
			->where('status', '<', 3)
			->orderBy('price', 'desc')
			->orderBy('created_at', 'asc')
			->pluck('id')
			->toArray();

		$chunks = array_chunk($buyOrders, 200);

		foreach ($chunks as $chunk) {
			MatchOrdersBatchJob::dispatch($symbol, $chunk)->onQueue('matching');
		}
	}

	public function matchSubset(string $symbol, array $buyOrderIds): void
	{
		$buyOrders = Order::forSymbol($symbol)
			->whereIn('id', $buyOrderIds)
			->whereIn('status', [0, 1])
			->orderBy('price', 'desc')
			->orderBy('created_at', 'asc')
			->lockForUpdate()
			->get();

		$sellOrders = Order::forSymbol($symbol)
			->where('type', 'sell')
			->whereIn('status', [0, 1])
			->orderBy('price', 'asc')
			->orderBy('created_at', 'asc')
			->lockForUpdate()
			->get();

		foreach ($buyOrders as $buyOrder) {
			foreach ($sellOrders as $sellOrder) {
				if ($buyOrder->price >= $sellOrder->price) {
					$this->processMatch($buyOrder, $sellOrder, $symbol);
				}
			}
		}
	}

	protected function processMatch(Order $buyOrder, Order $sellOrder, $symbol)
	{
		if (in_array($buyOrder->status, [2, 3]) || in_array($sellOrder->status, [2, 3])) {
			return;
		}

		$matchAmount = min($buyOrder->amount - $buyOrder->filled_amount, $sellOrder->amount - $sellOrder->filled_amount);
		if ($matchAmount <= 0) return;

		$buyerWallet = Wallet::where('user_id', $buyOrder->user_id)
			->where('symbol', $symbol)
			->lockForUpdate()
			->first();
		$sellerWallet = Wallet::where('user_id', $sellOrder->user_id)
			->where('symbol', $symbol)
			->lockForUpdate()
			->first();

		if (!$buyerWallet || !$sellerWallet) {
			Log::error("Wallet not found for user_id: {$buyOrder->user_id} or {$sellOrder->user_id} with symbol: {$symbol}");
			$buyOrder->update(['status' => 3]);
			return;
		}

		$requiredBalance = $matchAmount * $buyOrder->price / $buyOrder->leverage;

		if ($buyerWallet->frozen_balance < $requiredBalance) {
			Log::warning("Insufficient frozen balance for user_id: {$buyOrder->user_id}, symbol: {$symbol}");
			$buyOrder->update(['status' => 3]);
			return;
		}

		$buyerWallet->decrement('frozen_balance', $requiredBalance);
		$sellerWallet->increment('balance', $matchAmount * $sellOrder->price);

		$buyOrder->increment('filled_amount', $matchAmount);
		$sellOrder->increment('filled_amount', $matchAmount);

		if ($buyOrder->filled_amount >= $buyOrder->amount) {
			$buyOrder->update(['status' => 2, 'closed_at' => now()]);
		} elseif ($buyOrder->filled_amount > 0) {
			$buyOrder->update(['status' => 1]);
		}

		if ($sellOrder->filled_amount >= $sellOrder->amount) {
			$sellOrder->update(['status' => 2, 'closed_at' => now()]);
		} elseif ($sellOrder->filled_amount > 0) {
			$sellOrder->update(['status' => 1]);
		}

		$this->calculatePnL($buyOrder, $symbol);
		$this->calculatePnL($sellOrder, $symbol);
	}

	protected function calculatePnL(Order $order, $symbol)
	{
		$currentPrice = $this->getCurrentMarketPrice($symbol);
		$pnl = 0;

		if (in_array($order->status, [0, 1])) {
			$filledAmount = $order->filled_amount;
			if ($order->type === 'buy') {
				$pnl = ($currentPrice - $order->price) * $filledAmount * $order->leverage;
			} else {
				$pnl = ($order->price - $currentPrice) * $filledAmount * $order->leverage;
			}
			Redis::set("pnl:{$order->id}:{$order->getTable()}", $pnl);
		}

		return $pnl;
	}

	protected function getCurrentMarketPrice($symbol)
	{
		return Redis::get("market_price:{$symbol}") ?? 100;
	}
}
