<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletSeeder extends Seeder
{
    protected array $userIds;
    protected array $symbols;

    public function __construct(array $userIds, array $symbols)
    {
        $this->userIds = array_unique($userIds);
        $this->symbols = $symbols;
    }

    public function run(): void
    {
        $batchSize = 1000;

        foreach ($this->symbols as $symbol) {
            $wallets = [];
            foreach ($this->userIds as $userId) {
                $exists = DB::table('wallets')
                    ->where('user_id', $userId)
                    ->where('symbol', $symbol)
                    ->exists();

                if ($exists) {
                    Log::info("Wallet already exists for user_id: {$userId}, symbol: {$symbol}, skipping...");
                    continue;
                }

                $wallets[] = [
                    'user_id' => $userId,
                    'symbol' => $symbol,
                    'balance' => fake()->randomFloat(8, 100000, 1000000),
                    'frozen_balance' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($wallets) >= $batchSize) {
                    try {
                        DB::table('wallets')->insertOrIgnore($wallets);
                    } catch (\Illuminate\Database\QueryException $e) {
                    }
                    $wallets = [];
                }
            }

            if (!empty($wallets)) {
                try {
                    DB::table('wallets')->insertOrIgnore($wallets);
                } catch (\Illuminate\Database\QueryException $e) {
                }
            }
        }
    }
}
