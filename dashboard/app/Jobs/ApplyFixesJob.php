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
use Illuminate\Support\Facades\Http;

class ApplyFixesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(public Site $site, public Scan $scan, public Action $action) {}

    public function handle(): void
    {
        // Get recommended actions from scan plan
        $recommendedActions = $this->scan->plan['recommended_actions'] ?? [];

        if (empty($recommendedActions)) {
            $this->action->update([
                'result' => [
                    'success' => false,
                    'error' => 'No recommended actions found in scan'
                ]
            ]);
            return;
        }

        // Transform recommended actions to fixes format for WordPress plugin
        $fixes = [];
        foreach ($recommendedActions as $action) {
            $fixType = $action['fix_type'] ?? 'unknown';
            $config = $action['config'] ?? [];

            // Transform AWS fix types to WordPress plugin format
            if ($fixType === 'add_security_headers') {
                // Add security headers
                if (isset($config['headers']) && is_array($config['headers'])) {
                    foreach ($config['headers'] as $key => $value) {
                        $fixes[] = [
                            'type' => 'header',
                            'key' => $key,
                            'value' => $value
                        ];
                    }
                } else {
                    // Default security headers if not specified
                    $fixes[] = [
                        'type' => 'header',
                        'key' => 'X-Frame-Options',
                        'value' => 'SAMEORIGIN'
                    ];
                    $fixes[] = [
                        'type' => 'header',
                        'key' => 'X-Content-Type-Options',
                        'value' => 'nosniff'
                    ];
                    $fixes[] = [
                        'type' => 'header',
                        'key' => 'X-XSS-Protection',
                        'value' => '1; mode=block'
                    ];
                }
            } elseif ($fixType === 'header') {
                // Direct header fix
                $fixes[] = [
                    'type' => 'header',
                    'key' => $config['key'] ?? $action['key'] ?? '',
                    'value' => $config['value'] ?? $action['value'] ?? ''
                ];
            } elseif ($fixType === 'csp') {
                // CSP fix
                $fixes[] = [
                    'type' => 'csp',
                    'policy' => $config['policy'] ?? '',
                    'mode' => $config['mode'] ?? 'report-only',
                    'report_uri' => $config['report_uri'] ?? ''
                ];
            } elseif ($fixType === 'banner') {
                // Banner/Region fix
                $fixes[] = [
                    'type' => 'banner',
                    'enabled' => $config['enabled'] ?? true,
                    'region' => $config['region'] ?? 'OTHER',
                    'message' => $config['message'] ?? '',
                    'block_scripts' => $config['block_scripts'] ?? []
                ];
            }
        }

        try {
            // Call WordPress plugin API to apply fixes
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->site->wp_api_key,
                'Content-Type' => 'application/json'
            ])->timeout(60)->post($this->site->wp_api_base . '/apply-fixes', [
                'fixes' => $fixes
            ]);

            if ($response->successful()) {
                $result = $response->json();

                // Update scan as applied
                $this->scan->update(['applied' => true]);

                // Update action with success result
                $this->action->update([
                    'result' => [
                        'success' => true,
                        'applied_count' => count($fixes),
                        'fixes' => $fixes,
                        'response' => $result
                    ]
                ]);
            } else {
                $this->action->update([
                    'result' => [
                        'success' => false,
                        'error' => 'WordPress API returned error: ' . $response->status(),
                        'response_body' => $response->body()
                    ]
                ]);
            }
        } catch (\Exception $e) {
            $this->action->update([
                'result' => [
                    'success' => false,
                    'error' => 'Failed to apply fixes: ' . $e->getMessage()
                ]
            ]);
        }
    }
}

