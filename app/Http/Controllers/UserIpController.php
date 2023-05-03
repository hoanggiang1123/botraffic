<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\UserIp;

use Browser;

use App\Exports\UserIpExport;

use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use DB;

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
            // 'user_agent' => $request->header('user-agent'),
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


    public function export(Request $request) {

        $orderBy = isset($params['order_by']) ? $params['order_by'] : 'created_at';

        $order = isset($params['order']) ? $params['order'] : 'desc';

        $hostname = isset($params['hostname']) ? $params['hostname'] : '';

        $link = isset($params['link']) ? $params['link'] : '';

        $fromDate = isset($params['from_date']) ? $params['from_date'] : '';

        $toDate = isset($params['to_date']) ? $params['to_date'] : '';


        $resp = $this->model->query()


        ->when($hostname !== '', function ($query) use ($hostname) {

            return $query->where('hostname', 'like', '%' . $hostname . '%');

        })

        ->when($link !== '', function ($query) use ($link) {

            return $query->where('link', 'like', '%' . $link . '%');

        })

        ->when($fromDate !== '' && $toDate !== '', function ($query) use ($fromDate, $toDate) {

            return $query->whereBetween(DB::raw('DATE(created_at)'), [$fromDate, $toDate]);

        })

        ->orderBy($orderBy, $order)->get();

        return $fromDate;


        try {

            $exel = new UserIpExport($resp);

            Storage::delete('public/excel/userip.xlsx');

            Excel::store($exel, 'public/excel/userip.xlsx');

            return '/storage/excel/userip.xlsx?time=' . time();

        } catch (\Exception $e) {
            return response(['message' => $e->getMessage()]);
        }

    }


}
