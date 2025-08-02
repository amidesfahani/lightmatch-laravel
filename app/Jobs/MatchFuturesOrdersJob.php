<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Services\FuturesMatchingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class MatchFuturesOrdersJob implements ShouldQueue
{
	use Dispatchable, Queueable;

	protected $symbol;

	public function __construct($symbol)
	{
		$this->symbol = $symbol;
	}

	public function handle()
	{
		$matchingService = new FuturesMatchingService();
		$matchingService->matchOrders($this->symbol);
	}
}
