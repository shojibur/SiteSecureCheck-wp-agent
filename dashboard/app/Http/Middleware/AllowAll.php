<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AllowAll
{
    public function handle(Request $request, Closure $next)
    {
        // TODO: replace with real auth
        return $next($request);
    }
}

