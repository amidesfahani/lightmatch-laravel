<?php

namespace App\Actions;

use App\Models\Order;
use App\Models\Wallet;
use App\Services\TableManager;
use Illuminate\Support\Facades\DB;
use App\Jobs\MatchFuturesOrdersJob;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;

class PlaceOrderAction
{
	public function execute(array $data)
	{
		$tableManager = new TableManager();
		$tableName = 'orders_' . str_replace('/', '_', strtolower($data['symbol']));
		if (!Schema::hasTable($tableName)) {
			$tableManager->addSymbolTable($data['symbol']);
		}

		return DB::transaction(function () use ($data) {
			$wallet = Wallet::where('user_id', $data['user_id'])
				->where('symbol', $data['symbol'])
				->lockForUpdate()
				->first();

			if (!$wallet) {
				throw new \Exception("Wallet not found for user_id: {$data['user_id']} and symbol: {$data['symbol']}");
			}

			$requiredBalance = ($data['amount'] * $data['price']) / $data['leverage'];

			if ($wallet->balance < $requiredBalance) {
				throw new \Exception('Insufficient balance');
			}

			$wallet->decrement('balance', $requiredBalance);
			$wallet->increment('frozen_balance', $requiredBalance);

			$order = Order::forSymbol($data['symbol'])->create([
				'user_id' => $data['user_id'],
				'type' => $data['type'],
				'amount' => $data['amount'],
				'price' => $data['price'],
				'leverage' => $data['leverage'],
				'status' => 0,
				'opened_at' => now(),
			]);

			Redis::zadd("orders:{$data['symbol']}:{$data['type']}", $data['price'], json_encode($order->toArray()));
            MatchFuturesOrdersJob::dispatch($data['symbol'])->onQueue('matching');

			// if (!Redis::get("lock:match:{$data['symbol']}")) {
			// 	Redis::setex("lock:match:{$data['symbol']}", 1, 1);
			// 	MatchFuturesOrdersJob::dispatch($data['symbol']);
			// }

			return $order;
		});
	}
}
