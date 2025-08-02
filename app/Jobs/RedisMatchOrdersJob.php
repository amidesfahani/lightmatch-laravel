<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Services\RedisMatchingService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class RedisMatchOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $symbol;

    public function __construct(string $symbol)
    {
        $this->symbol = $symbol;
    }

    public function handle()
    {
        (new RedisMatchingService())->match($this->symbol);
    }
}
