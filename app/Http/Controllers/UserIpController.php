<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\UserIp;

use Browser;

class UserIpController extends Controller
{
    protected $model;

    public function __construct(UserIp $userIp)
    {
        $this->model = $userIp;
    }

    public function index (Request $request) {
        return $this->model->listItems($request->all());
    }

    public function store (Request $request) {

        $data = [
            'is_cookies' => $request->is_cookies,
            'screen' => $request->screen,
            'hostname' => $request->hostname,
            'link' => $request->link,
            'user_agent' => $request->header('user-agent'),
            'browser_version' => Browser::browserVersion(),
            'browser_name' => Browser::browserName(),
            'os_name' => Browser::platformName(),
            'os_version' => Browser::platformVersion(),
            'device_type' => Browser::deviceType(),
            'ip' => $request->ip()
        ];

        $item = $this->model->create($data);

        if ($item) {
            return $item;
        }

        return response(['message' => 'Unprocess Entity'], 422);
    }

    // public function update (MainRequest $request, $id) {

    //     $item = $this->model->where('id', $id)->first();

    //     if ($item) {

    //         $data = $request->all();

    //         $update = $item->update($data);

    //         if ($update) return $update;

    //         return response(['message' => 'Unprocess Entity'], 422);

    //     }

    //     return response(['message' => 'Not Found'], 404);
    // }


}
