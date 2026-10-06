<?php

namespace App\Jobs;

use App\Models\Scan;
use App\Services\Scanner\WebsiteScanService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessWebsiteScan implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;

    public function __construct(public Scan $scan) {}

    public function handle(WebsiteScanService $scanner): void
    {
        $scanner->processScan($this->scan);
    }
}
