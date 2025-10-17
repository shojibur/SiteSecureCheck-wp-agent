<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TeamsNotifier
{
    public static function send(?string $url, string $title, string $body): void
    {
        $endpoint = $url ?: env('TEAMS_WEBHOOK_DEFAULT');
        if (!$endpoint) return;
        Http::asJson()->post($endpoint, ['text' => "**{$title}**\n{$body}"]);
    }
}

