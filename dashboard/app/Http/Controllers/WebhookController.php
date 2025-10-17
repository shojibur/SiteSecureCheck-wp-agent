<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\Request;
use App\Services\TeamsNotifier;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Verify webhook token
        $token = $request->bearerToken();
        if (!$token || $token !== env('AGENT_WEBHOOK_TOKEN')) {
            \Log::warning('Webhook auth failed', ['token' => substr($token ?? '', 0, 10)]);
            abort(403, 'Invalid webhook token');
        }

        $payload = $request->all();

        // Handle error responses
        if (isset($payload['error'])) {
            \Log::error('Scan failed', $payload);

            if ($siteId = $payload['site_id'] ?? null) {
                $site = Site::find($siteId);
                if ($site) {
                    $site->scans()->create([
                        'status' => 'failed',
                        'score' => null,
                        'issues' => [],
                        'plan' => ['error' => $payload['error']],
                        'applied' => false,
                        'raw' => $payload,
                    ]);
                }
            }

            return response()->json(['ok' => false, 'message' => 'Scan failed']);
        }

        // Process successful scan
        $site = Site::findOrFail($payload['site_id'] ?? '');
        $score = $payload['score'] ?? null;

        // Update site score
        $site->update(['last_score' => $score]);

        // Extract issues from browser_scan and wordpress_state
        $issues = [];

        if (isset($payload['browser_scan']['observations'])) {
            foreach ($payload['browser_scan']['observations'] as $obs) {
                $issues[] = [
                    'type' => 'browser',
                    'severity' => 'medium',
                    'message' => $obs
                ];
            }
        }

        // Transform recommended actions to issues
        if (isset($payload['recommended_actions'])) {
            foreach ($payload['recommended_actions'] as $action) {
                $issues[] = [
                    'type' => 'wordpress',
                    'severity' => $action['priority'] ?? 'medium',
                    'fix_type' => $action['fix_type'] ?? '',
                    'message' => $action['reason'] ?? ''
                ];
            }
        }

        // Create scan record
        $scan = $site->scans()->create([
            'status' => 'done',
            'score' => $score,
            'issues' => $issues,
            'plan' => [
                'assessment' => $payload['assessment'] ?? '',
                'recommended_actions' => $payload['recommended_actions'] ?? [],
                'browser_data' => $payload['browser_scan'] ?? [],
                'wordpress_state' => $payload['wordpress_state'] ?? []
            ],
            'applied' => !empty($payload['applied_actions']),
            'raw' => $payload,
        ]);

        // Create action records for applied fixes
        if (!empty($payload['applied_actions'])) {
            foreach ($payload['applied_actions'] as $fixType) {
                $site->actions()->create([
                    'scan_id' => $scan->id,
                    'type' => 'fix_applied',
                    'payload' => ['fix_type' => $fixType]
                ]);
            }
        }

        // Log notification action
        $site->actions()->create([
            'scan_id' => $scan->id,
            'type' => 'notify',
            'payload' => [
                'logs' => $payload['apply_logs'] ?? [],
                'auto_fix' => $payload['auto_fix_enabled'] ?? false
            ]
        ]);

        // Send Teams notification
        $scoreEmoji = $score >= 80 ? '✅' : ($score >= 60 ? '⚠️' : '🚨');
        $appliedCount = count($payload['applied_actions'] ?? []);
        $message = "Site **{$site->domain}** scored **{$score}** {$scoreEmoji}\n\n";
        $message .= $payload['assessment'] ?? '';

        if ($appliedCount > 0) {
            $message .= "\n\n✓ Applied {$appliedCount} security fix(es) automatically";
        }

        TeamsNotifier::send($site->teams_webhook, 'Security Scan Completed', $message);

        \Log::info('Webhook processed successfully', [
            'site_id' => $site->id,
            'score' => $score,
            'applied' => $appliedCount
        ]);

        return response()->json(['ok' => true, 'scan_id' => $scan->id]);
    }
}

