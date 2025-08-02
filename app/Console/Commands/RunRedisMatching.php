<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RedisMatchingService;

class RunRedisMatching extends Command
{
	protected $signature = 'futures:redis-match {symbol}';
	protected $description = 'Run Redis-only futures matching engine for a given symbol';

	public function handle()
	{
		$symbol = $this->argument('symbol');
		$this->info("Matching orders in Redis for: {$symbol}");
		(new RedisMatchingService())->match($symbol);
		$this->info("Matching complete.");
	}
}
