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
        $url = (string) env('AGENT_SCAN_URL');

        // If Agent URL is not configured, create a helpful error scan
        if (empty($url)) {
            $scan = $this->site->scans()->where('status', 'queued')->latest()->first();
            if ($scan) {
                $scan->update([
                    'status' => 'failed',
                    'issues' => [[
                        'id' => 'agent-not-configured',
                        'severity' => 'info',
                        'why' => 'Agent is not configured yet. Set AGENT_SCAN_URL in .env or use /sites/{id}/test/mock-scan to test the plugin.'
                    ]],
                    'raw' => [
                        'error' => 'AGENT_SCAN_URL not configured',
                        'help' => 'Use test endpoints: /sites/{id}/test/connection, /sites/{id}/test/mock-scan'
                    ]
                ]);
            }

            Action::create([
                'site_id' => $this->site->id,
                'type' => 'scan_request_failed',
                'payload' => ['reason' => 'Agent URL not configured'],
                'result' => [
                    'error' => 'AGENT_SCAN_URL not set in .env',
                    'hint' => 'The Agent (AWS Lambda) is not implemented yet. Use test endpoints to verify plugin connection.'
                ]
            ]);

            return;
        }

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

        try {
            $response = Http::timeout(30)->asJson()->post($url, $payload);

            Action::create([
                'site_id' => $this->site->id,
                'type' => 'scan_request',
                'payload' => $payload,
                'result' => [
                    'status' => $response->status(),
                    'success' => $response->successful()
                ]
            ]);
        } catch (\Exception $e) {
            // Update scan to failed if Agent is unreachable
            $scan = $this->site->scans()->where('status', 'queued')->latest()->first();
            if ($scan) {
                $scan->update([
                    'status' => 'failed',
                    'issues' => [[
                        'id' => 'agent-unreachable',
                        'severity' => 'error',
                        'why' => 'Could not reach Agent: ' . $e->getMessage()
                    ]],
                    'raw' => ['error' => $e->getMessage()]
                ]);
            }

            Action::create([
                'site_id' => $this->site->id,
                'type' => 'scan_request_failed',
                'payload' => $payload,
                'result' => [
                    'error' => $e->getMessage(),
                    'hint' => 'Agent may not be deployed yet. Use test endpoints instead.'
                ]
            ]);
        }
    }
}

