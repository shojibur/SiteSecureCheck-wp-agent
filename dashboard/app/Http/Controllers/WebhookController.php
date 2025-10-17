<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\Request;
use App\Services\TeamsNotifier;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        $token = $request->header('X-SSC-Token');
        abort_unless($token && $token === env('AGENT_WEBHOOK_TOKEN'), 403);
        $p = $request->all();
        $site = Site::query()->findOrFail($p['site_id'] ?? '');
        $site->update(['last_score' => $p['score'] ?? null]);
        $scan = $site->scans()->create([
            'status' => 'done',
            'score' => $p['score'] ?? null,
            'issues' => $p['issues'] ?? [],
            'plan' => $p['plan'] ?? [],
            'applied' => (bool)($p['applied'] ?? false),
            'raw' => $p,
        ]);
        $site->actions()->create(['scan_id'=>$scan->id,'type'=>'notify','payload'=>['logs'=>$p['logs'] ?? []]]);
        TeamsNotifier::send(null, 'Scan Completed', "Site {$site->domain} scored " . ($p['score'] ?? '-'));
        return response()->json(['ok'=>true]);
    }
}

