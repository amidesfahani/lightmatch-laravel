<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    protected int $orderCount;

    public function __construct(int $orderCount = 1000)
    {
        $this->orderCount = $orderCount;
    }

    public function run(): void
    {
        $symbols = ['BTC/USD', 'ETH/USD'];
        $userCount = max(1000, (int)($this->orderCount / 100));

        $userSeeder = new UserSeeder($userCount);
        $users = $userSeeder->run();

        $walletSeeder = new WalletSeeder($users, $symbols);
        $walletSeeder->run();

        $orderSeeder = new OrderSeeder($users, $symbols, $this->orderCount);
        $orderSeeder->run();
    }
}
