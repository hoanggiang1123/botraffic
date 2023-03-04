<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;

use App\Models\Redirector;
use App\Models\Device;

class CheckIsSameDevice
{

    public function handle(Request $request, Closure $next)
    {

        if ($request->next) return $next($request);

        if(config('app.check_device') != 1) return $next($request);

        $check = false;

        $device = Device::where('ip', $request->ip_address)->first();

        if ($device)
        {
            if ($device->user_agent == $request->ua)
            {
                $check = true;
            }

        } else {
            $check = true;
        }

        if ($check === false)
        {
            $redirectorCheck = Redirector::where('slug', $request->slug)->first();

            Log::info("ip $request->ip_address not use same devices --takemission");

            if ($redirectorCheck) {

                return response(['url' => $redirectorCheck->alternative_link ? $redirectorCheck->alternative_link : $redirectorCheck->url]);
            }

            return response(['message' => 'Not Found'], 404);
        }

        return $next($request);
    }
}
