<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\OrderStatus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class RedisMatchingService
{
	public function match(string $symbol): void
	{
		$symbolKey = strtolower(str_replace('/', '_', $symbol));
		$buyKey = "orders:{$symbolKey}:buy";
		$sellKey = "orders:{$symbolKey}:sell";

		while (true) {
			$topBuy = Redis::zrevrange($buyKey, 0, 0, ['withscores' => true]);
			$topSell = Redis::zrange($sellKey, 0, 0, ['withscores' => true]);

			if (empty($topBuy) || empty($topSell)) {
				break;
			}

			$buyOrderId = array_key_first($topBuy);
			$sellOrderId = array_key_first($topSell);
			$buyPrice = $topBuy[$buyOrderId];
			$sellPrice = $topSell[$sellOrderId];

			if ($buyPrice < $sellPrice) {
				break; // No match possible
			}

			$buyDetail = Redis::hgetall("order_detail:{$buyOrderId}");
			$sellDetail = Redis::hgetall("order_detail:{$sellOrderId}");

			if (empty($buyDetail) || empty($sellDetail)) {
				Redis::zrem($buyKey, $buyOrderId);
				Redis::zrem($sellKey, $sellOrderId);
				continue;
			}

			$buyRemaining = $buyDetail['amount'] - $buyDetail['filled_amount'];
			$sellRemaining = $sellDetail['amount'] - $sellDetail['filled_amount'];

			$matchAmount = min($buyRemaining, $sellRemaining);
			if ($matchAmount <= 0) {
				Redis::zrem($buyKey, $buyOrderId);
				Redis::zrem($sellKey, $sellOrderId);
				continue;
			}

			$tradePrice = ($buyPrice + $sellPrice) / 2;

			// Simulate wallet changes (frozen -> filled for buyer, balance + for seller)
			$buyerWalletKey = "wallet:{$buyDetail['user_id']}:{$symbolKey}";
			$sellerWalletKey = "wallet:{$sellDetail['user_id']}:{$symbolKey}";

			Redis::hincrbyfloat($buyerWalletKey, 'frozen', -$matchAmount * $tradePrice);
			Redis::hincrbyfloat($sellerWalletKey, 'balance', $matchAmount);

			// Update Redis orders
			Redis::hincrbyfloat("order_detail:{$buyOrderId}", 'filled_amount', $matchAmount);
			Redis::hincrbyfloat("order_detail:{$sellOrderId}", 'filled_amount', $matchAmount);

			// Update DB order statuses
			$this->updateOrderStatus($buyOrderId, $matchAmount, $tradePrice, $symbolKey);
			$this->updateOrderStatus($sellOrderId, $matchAmount, $tradePrice, $symbolKey);

			// Remove from book if fully filled
			if ($this->isFullyFilled($buyOrderId)) {
				Redis::zrem($buyKey, $buyOrderId);
			}

			if ($this->isFullyFilled($sellOrderId)) {
				Redis::zrem($sellKey, $sellOrderId);
			}
		}
	}

	private function isFullyFilled(string|int $orderId): bool
	{
		$detail = Redis::hgetall("order_detail:{$orderId}");
		return isset($detail['filled_amount'], $detail['amount']) &&
			(float)$detail['filled_amount'] >= (float)$detail['amount'];
	}

	private function updateOrderStatus(string|int $orderId, float $matchedAmount, float $price, string $tableName): void
	{
		try {
			$redisDetail = Redis::hgetall("order_detail:{$orderId}");

			$status = OrderStatus::firstOrNew(['order_id' => $orderId]);
			$status->table_name = $tableName;
			$status->filled_amount = ($status->filled_amount ?? 0) + $matchedAmount;
			$status->pnl = $this->calculatePnL($status->filled_amount, $price, $redisDetail);
			$status->status = $this->isFullyFilled($orderId) ? 2 : 1; // 2=filled, 1=partially
			$status->updated_at = Carbon::now();
			$status->save();
		} catch (\Throwable $e) {
			Log::error("Error updating OrderStatus for order {$orderId}: " . $e->getMessage(), [
				'exception' => $e,
			]);
		}
	}

	private function calculatePnL(float $filledAmount, float $tradePrice, array $order): float
	{
		// Simplified PnL calculation
		$entryPrice = (float)($order['price'] ?? $tradePrice);
		$leverage = (float)($order['leverage'] ?? 1);
		$side = $order['type'] ?? 'buy'; // buy or sell

		if ($side === 'buy') {
			return ($tradePrice - $entryPrice) * $filledAmount * $leverage;
		} else {
			return ($entryPrice - $tradePrice) * $filledAmount * $leverage;
		}
	}
}
