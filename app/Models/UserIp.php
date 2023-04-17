<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Http\Resources\UserIpCollection;

class UserIp extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip',
        'browser_name',
        'browser_version',
        'hostname',
        'link',
        'os_name',
        'os_version',
        'is_cookies',
        'screen',
        'user_agent',
        'device_type'
    ];

    public function listItems ($params) {

        $result = [];

        $perPage = isset($params['per_page']) ? (int) $params['per_page'] : 10000;

        $orderBy = isset($params['order_by']) ? $params['order_by'] : 'created_at';

        $order = isset($params['order']) ? $params['order'] : 'desc';

        $hostname = isset($params['hostname']) ? $params['hostname'] : '';

        $link = isset($params['link']) ? $params['link'] : '';

        $fromDate = isset($params['from_date']) ? $params['from_date'] : '';

        $toDate = isset($params['to_date']) ? $params['to_date'] : '';


        $resp = self::query()


        ->when($hostname !== '', function ($query) use ($hostname) {

            return $query->where('hostname', 'like', '%' . $hostname . '%');

        })

        ->when($link !== '', function ($query) use ($link) {

            return $query->where('link', 'like', '%' . $link . '%');

        })

        ->when($fromDate !== '' && $toDate !== '', function ($query) use ($fromDate, $toDate) {

            return $query->whereBetween('created_at', [$fromDate, $toDate]);

        })

        ->orderBy($orderBy, $order)->paginate($perPage);

        if ($resp) $result = new UserIpCollection($resp);

        return $result;
    }
}
