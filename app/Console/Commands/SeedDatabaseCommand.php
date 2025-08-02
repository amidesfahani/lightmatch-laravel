<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Database\Seeders\UserSeeder;
use Database\Seeders\OrderSeeder;
use Database\Seeders\WalletSeeder;

class SeedDatabaseCommand extends Command
{
	protected $signature = 'db:seed-records 
        {count : Number of order records to seed (small, 10k, 100k, 1m, 10m)} 
        {--only=all : What to seed (users, wallets, orders, all)}';

	protected $description = 'Seed the database with a specified number of order records or parts (users, wallets, orders)';

	public function handle()
	{
		$countInput = $this->argument('count');
		$only = $this->option('only');

		$counts = [
			'small' => 1000,
			'10k' => 10000,
			'100k' => 100000,
			'1m' => 1000000,
			'10m' => 10000000,
		];

		$validOnly = ['all', 'users', 'wallets', 'orders'];

		if (!array_key_exists($countInput, $counts)) {
			$this->error('Invalid count. Use: small, 10k, 100k, 1m, or 10m');
			return;
		}

		if (!in_array($only, $validOnly)) {
			$this->error("Invalid --only option. Use one of: " . implode(', ', $validOnly));
			return;
		}

		$orderCount = $counts[$countInput];
		$userCount = max(1000, (int)($orderCount / 100));
		$symbols = ['BTC/USD', 'ETH/USD'];

		$this->info("Seeding type: $only");
		$this->info("Order count: $orderCount | User count: $userCount");

		$userIds = [];

		if ($only === 'all' || $only === 'users') {
			$this->info('Seeding users...');
			$userIds = (new UserSeeder($userCount))->run();
			$this->info('Users seeded.');
		}

		if ($only === 'all' || $only === 'wallets') {
			if (empty($userIds)) {
				$this->info('Loading user IDs from database...');
				$userIds = User::take($userCount)->pluck('id')->toArray();
			}
			$this->info('Seeding wallets...');
			(new WalletSeeder($userIds, $symbols))->run();
			$this->info('Wallets seeded.');
		}

		if ($only === 'all' || $only === 'orders') {
			if (empty($userIds)) {
				$this->info('Loading user IDs from database...');
				$userIds = User::take($userCount)->pluck('id')->toArray();
			}
			$this->info('Seeding orders...');
			(new OrderSeeder($userIds, $symbols, $orderCount))->run();
			$this->info('Orders seeded.');
		}

		$this->info('Seeding completed.');
	}
}
