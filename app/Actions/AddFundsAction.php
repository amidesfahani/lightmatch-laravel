<?php

namespace App\Actions;

use App\Models\Wallet;
use App\Services\TableManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AddFundsAction
{
	public function execute($userId, $symbol, $amount)
	{
		$tableManager = new TableManager();
		$tableName = 'orders_' . str_replace('/', '_', strtolower($symbol));
		if (!Schema::hasTable($tableName)) {
			$tableManager->addSymbolTable($symbol);
		}

		return DB::transaction(function () use ($userId, $symbol, $amount) {
			$wallet = Wallet::where('user_id', $userId)
				->where('symbol', $symbol)
				->lockForUpdate()
				->first();

			if (!$wallet) {
				$wallet = Wallet::create([
					'user_id' => $userId,
					'symbol' => $symbol,
					'balance' => $amount,
					'frozen_balance' => 0,
				]);
				Log::info("Created new wallet for user_id: {$userId}, symbol: {$symbol} with balance: {$amount}");
			} else {
				$wallet->increment('balance', $amount);
				Log::info("Added {$amount} to balance for user_id: {$userId}, symbol: {$symbol}");
			}

			return $wallet;
		});
	}
}
