<?php

namespace App\Jobs;

use App\Models\Site;
use App\Models\Action;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class RunScanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(public Site $site) {}

    public function handle(): void
    {
        $payload = [
            'site_id' => $this->site->id,
            'domain' => $this->site->domain,
            'wp_api_base' => $this->site->wp_api_base,
            'wp_api_key' => $this->site->wp_api_key,
            'region_mode' => $this->site->region_mode,
            'auto_fix' => $this->site->auto_fix,
            'callback' => route('agent.webhook', absolute: true),
            'callback_token' => env('AGENT_WEBHOOK_TOKEN'),
            'apply' => $this->site->auto_fix,
        ];
        $url = (string) env('AGENT_SCAN_URL');
        $response = Http::asJson()->post($url, $payload);
        Action::create([
            'site_id' => $this->site->id,
            'type' => 'scan_request',
            'payload' => $payload,
            'result' => ['status' => $response->status()]
        ]);
    }
}

