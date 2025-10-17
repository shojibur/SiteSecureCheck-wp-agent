<?php

namespace App\Http\Controllers;

use App\Http\Requests\SiteStoreRequest;
use App\Http\Requests\SiteUpdateRequest;
use App\Jobs\RunScanJob;
use App\Jobs\ApplyFixesJob;
use App\Models\Site;
use App\Models\Scan;
use App\Models\Action;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SiteController extends Controller
{
    public function index(): Response
    {
        $sites = Site::query()
            ->with(['scans' => fn($q)=>$q->latest()->limit(1)])
            ->orderBy('created_at','desc')
            ->paginate(10, ['id','name','domain','last_score','auto_fix','connection_status','connection_checked_at','created_at']);
        return Inertia::render('Sites/Index', [ 'sites' => $sites ]);
    }

    public function create(): Response { return Inertia::render('Sites/Create'); }

    public function store(SiteStoreRequest $request): RedirectResponse
    {
        $site = Site::create($request->validated());
        return redirect()->route('sites.show', $site);
    }

    public function show(Site $site): Response
    {
        $scans = $site->scans()->latest()->limit(10)->get(['id','score','status','applied','plan','raw','created_at']);
        $latestIssues = optional($site->scans()->latest()->first())->issues ?? [];
        $actions = $site->actions()->latest()->limit(10)->get(['type','created_at']);
        $siteData = $site->only(['id','name','domain','region_mode','auto_fix','last_score','teams_webhook','email','created_at']);
        return Inertia::render('Sites/Show', compact('siteData','scans','latestIssues','actions'));
    }

    public function edit(Site $site): Response { return Inertia::render('Sites/Edit', ['site'=>$site->only(['id','name','domain','wp_api_base','region_mode','auto_fix','teams_webhook','email'])]); }

    public function update(SiteUpdateRequest $request, Site $site): RedirectResponse
    {
        $site->update($request->validated());
        return redirect()->route('sites.show', $site);
    }

    public function destroy(Site $site): RedirectResponse
    {
        $site->delete();
        return redirect()->route('sites.index');
    }

    public function scan(Site $site)
    {
        $scan = $site->scans()->create(['status'=>'queued']);
        dispatch(new RunScanJob($site));

        return redirect()->back()->with('message', 'Scan queued successfully');
    }

    public function applyFixes(Site $site, Scan $scan)
    {
        $action = $site->actions()->create(['scan_id'=>$scan->id,'type'=>'apply','payload'=>['scan_id'=>$scan->id]]);
        dispatch(new ApplyFixesJob($site, $scan, $action));
        return redirect()->back()->with('message', 'Fixes are being applied!');
    }

    public function checkConnection(Site $site)
    {
        $result = $site->checkConnection();

        return redirect()->back()->with('message',
            $result['success']
                ? 'Connection successful!'
                : 'Connection failed: ' . ($result['error'] ?? 'Unknown error')
        );
    }

    public function deleteScan(Site $site, Scan $scan)
    {
        $scan->delete();
        return redirect()->back()->with('message', 'Scan deleted successfully');
    }
}

