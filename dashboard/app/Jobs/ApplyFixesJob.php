<?php

namespace App\Jobs;

use App\Models\Site;
use App\Models\Scan;
use App\Models\Action;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ApplyFixesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(public Site $site, public Scan $scan, public Action $action) {}
    public function handle(): void
    {
        // Stub: record intent to apply fixes
        $this->action->update(['result' => ['queued' => true]]);
    }
}

