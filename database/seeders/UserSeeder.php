<?php

namespace Database\Seeders;

use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    protected int $userCount;

    public function __construct(int $userCount = 1000)
    {
        $this->userCount = $userCount;
    }

    public function run(): array
    {
        $faker = Faker::create();
        $batchSize = 1000;
        $userIds = [];
        $startId = DB::table('users')->max('id') + 1 ?? 1;

        for ($i = 0; $i < $this->userCount; $i += $batchSize) {
            $users = [];
            $batchCount = min($batchSize, $this->userCount - $i);

            for ($j = 0; $j < $batchCount; $j++) {
                $index = $i + $j;
                $email = sprintf('user_%d_%s@example.com', $startId + $index, microtime(true));

                $users[] = [
                    'name' => $faker->name,
                    'email' => $email,
                    'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            try {
                DB::table('users')->insert($users);
                $newUserIds = DB::table('users')
                    ->whereBetween('id', [$startId, $startId + $batchCount - 1])
                    ->pluck('id')
                    ->toArray();

                $userIds = array_merge($userIds, $newUserIds);
                $startId += $batchCount;
            } catch (\Illuminate\Database\QueryException $e) {
                if (str_contains($e->getMessage(), 'Duplicate entry')) {
                    $i -= $batchSize;
                    continue;
                }
                throw $e;
            }
        }

        return $userIds;
    }
}
