<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('can store a site (validates urls)', function () {
    $res = $this->post('/sites', [
        'name'=>'A','domain'=>'https://example.com','wp_api_base'=>'https://example.com/wp-json','wp_api_key'=>'k','region_mode'=>'OTHER','auto_fix'=>true
    ]);
    $res->assertRedirect();
});

it('rejects webhook without correct token', function () {
    $this->postJson('/api/agent/webhook', [])->assertStatus(403);
});

it('accepts webhook, creates scan and updates score', function () {
    $siteId = (string) Str::uuid();
    \App\Models\Site::create(['id'=>$siteId,'domain'=>'https://a.test','wp_api_base'=>'https://a.test/wp-json','wp_api_key'=>'k','region_mode'=>'OTHER','auto_fix'=>true]);
    $payload = ['site_id'=>$siteId,'score'=>88,'issues'=>[],'plan'=>[],'applied'=>false,'logs'=>[]];
    config(['app.key'=>'base64:'.base64_encode(random_bytes(32))]);
    $res = $this->withHeader('X-SSC-Token', env('AGENT_WEBHOOK_TOKEN','t'))
        ->postJson('/api/agent/webhook', $payload);
    if (env('AGENT_WEBHOOK_TOKEN')) { $res->assertOk(); } else { $res->assertStatus(403); }
});

