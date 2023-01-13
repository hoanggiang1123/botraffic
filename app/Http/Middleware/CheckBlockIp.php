<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;

use App\Models\Redirector;
use App\Models\BlockIp;

class CheckBlockIp
{

    public function handle(Request $request, Closure $next)
    {
        if ($request->next) return $next($request);

        $block = BlockIp::where('ip', $request->ip_address)->first();

        if ($block) {

            Log::info("ip $request->ip_address in black list --takemission");

            $redirectorCheck = Redirector::where('slug', $request->slug)->first();

            if ($redirectorCheck) {

                return response(['url' => $redirectorCheck->alternative_link ? $redirectorCheck->alternative_link : $redirectorCheck->url]);
            }

            return response(['message' => 'Not Found'], 404);
        }

        return $next($request);
    }
}
