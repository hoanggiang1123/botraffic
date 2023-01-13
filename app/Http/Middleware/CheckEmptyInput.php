<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckEmptyInput
{

    public function handle(Request $request, Closure $next)
    {
        $ipAddress = $request->ip_address ? $request->ip_address : '';
        $slug = $request->slug ? $request->slug : '';

        if (!$ipAddress || !$slug) {
            Log::info('Missing Ip address --takemission');
            return response(['message' => 'Not Found'], 404);
        };

        return $next($request);
    }
}
