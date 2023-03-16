<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

use App\Models\Redirector;
use App\Models\LimitIp;

use Illuminate\Support\Facades\Log;

class CheckLimitIp
{

    public function handle(Request $request, Closure $next)
    {

        if ($request->next) return $next($request);

        $checkCount = LimitIp::where('ip', $request->ip_address)->first();

        if ($checkCount && $checkCount->count >= (int) config('app.limit_mission')) {

            Log::info("ip $request->ip_address vượt quá 3 lần --takemission");

            $redirectorCheck = Redirector::where('slug', $request->slug)->first();

            if ($redirectorCheck) {

                return response(['url' => $redirectorCheck->alternative_link ? $redirectorCheck->alternative_link : $redirectorCheck->url]);
            }

            return response(['message' => 'Not Found'], 404);
        }

        return $next($request);
    }
}
