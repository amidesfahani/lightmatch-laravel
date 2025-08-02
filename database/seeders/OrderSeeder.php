<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Services\TableManager;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    protected array $userIds;
    protected array $symbols;
    protected int $orderCount;

    public function __construct(array $userIds, array $symbols, int $orderCount)
    {
        $this->userIds = $userIds;
        $this->symbols = $symbols;
        $this->orderCount = $orderCount;
    }

    public function run(): void
    {
        $faker = \Faker\Factory::create();
        $tableManager = new TableManager();
        $batchSize = 1000;

        foreach ($this->symbols as $symbol) {
            $tableManager->addSymbolTable($symbol);
            $orders = [];
            for ($i = 0; $i < $this->orderCount; $i++) {
                $orders[] = [
                    'user_id' => $this->userIds[array_rand($this->userIds)],
                    'type' => $faker->randomElement(['buy', 'sell']),
                    'amount' => $faker->randomFloat(8, 1, 100),
                    'price' => $faker->randomFloat(8, 50, 500),
                    'leverage' => $faker->randomElement([1, 5, 10, 20]),
                    'status' => 0,
                    'filled_amount' => 0,
                    'opened_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($orders) >= $batchSize) {
                    Order::forSymbol($symbol)->insert($orders);
                    $orders = [];
                }
            }

            if (!empty($orders)) {
                Order::forSymbol($symbol)->insert($orders);
            }
        }
    }
}
