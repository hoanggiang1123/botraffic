<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use App\Models\Redirector;

class CheckEmptyInput
{

    public function handle(Request $request, Closure $next)
    {
        $ipAddress = $request->ip_address ? $request->ip_address : '';
        $slug = $request->slug ? $request->slug : '';
        $proxy = $request->proxy ? $request->proxy : 0;

        if (!$ipAddress || !$slug) {
            Log::info('Missing Ip address --takemission');
            return response(['message' => 'Not Found'], 404);
        };

        if ($proxy == 1)
        {
            $redirectorCheck = Redirector::where('slug', $slug)->first();

            if ($redirectorCheck) {

                return response(['url' => $redirectorCheck->alternative_link ? $redirectorCheck->alternative_link : $redirectorCheck->url]);
            }

            return response(['message' => 'Not Found'], 404);
        }


        return $next($request);
    }
}
