<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;

class CheckWhiteList
{

    public function handle(Request $request, Closure $next)
    {
        $whiteListIp = [
            '222.127.108.131', '222.127.108.167'
        ];

        if ( in_array( $request->ip_address, $whiteListIp ) )
        {
            $request->request->add(['next' => true]);
        }

        return $next($request);
    }
}
