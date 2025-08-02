<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Redis;
use Illuminate\Queue\SerializesModels;
use App\Services\FuturesMatchingService;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class MatchOrdersBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $symbol;
    protected array $buyOrderIds;

    public function __construct(string $symbol, array $buyOrderIds)
    {
        $this->symbol = $symbol;
        $this->buyOrderIds = $buyOrderIds;
    }

    public function handle()
    {
        $lockKey = "match_orders_lock:{$this->symbol}:" . md5(json_encode($this->buyOrderIds));
        if (Redis::set($lockKey, 1, 'NX', 'EX', 60)) {
            try {
                (new FuturesMatchingService())->matchSubset($this->symbol, $this->buyOrderIds);
            } finally {
                Redis::del($lockKey);
            }
        }
    }
}
