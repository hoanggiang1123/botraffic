<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Curl\Curl;

class CheckIpController extends Controller
{
    public function show(Request $request, $ip) {
        $url = 'https://api.ipdata.co/'. $ip .'?api-key=070a76aafa4e1b1478f8c0d06cb1f4fbc1b9d7614dcb14f73d6ce37d';
        $data = (new Curl)->getContent($url);
        return json_decode($data, true);
    }
}
