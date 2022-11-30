<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Http\Resources\KeywordCollection;

class Keyword extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'url', 'picture', 'time_on_site', 'status', 'created_by', 'approve', 'traffic', 'priority', 'traffic_count', 'internal'
    ];

    public function user () {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function missions () {
        return $this->hasMany(Mission::class, 'keyword_id');
    }
    public function trackers () {
        return $this->hasMany(Tracker::class, 'keyword_id');
    }

    public function internalLinks () {
        return $this->hasMany(InternalLink::class, 'keyword_id');
    }

    public function listItems ($params) {

        $result = [];

        $perPage = isset($params['per_page']) ? (int) $params['per_page'] : 10000;

        $orderBy = isset($params['order_by']) ? $params['order_by'] : 'created_at';

        $order = isset($params['order']) ? $params['order'] : 'desc';

        $countMission = isset($params['count_mission']) ? $params['count_mission'] : '';
        $countClick = isset($params['count_click']) ? $params['count_click'] : '';

        $name = isset($params['name']) ? $params['name'] : '';

        $url = isset($params['url']) ? $params['url'] : '';

        $userName = isset($params['user']) ? $params['user'] : '';

        $status = isset($params['status']) ? $params['status'] : '';

        $approve = isset($params['approve']) ? $params['approve'] : '';

        $fromDate = isset($params['from_date']) ? $params['from_date'] : '';

        $toDate = isset($params['to_date']) ? $params['to_date'] : '';

        $resp = self::query()->with('user')

        ->when($countMission, function ($query){

            return $query->withCount('missions');

        })
        ->when($countClick, function ($query){

            return $query->withCount('trackers');

        })
        ->when(auth()->user()->role !== 'admin', function($query) {

            $query->where('created_by', auth()->user()->id);

        })

        ->when($name !== '', function ($query) use ($name) {

            return $query->where('name', 'like', '%' .$name. '%');

        })
        ->when($url !== '', function ($query) use ($url) {

            return $query->where('url','like', '%' .$url. '%');

        })

        ->when($userName !== '', function ($query) use ($userName) {

            return $query->whereHas('user', function($query) use ($userName) {
                $query->where('name', 'like', '%' . $userName .'%');
            });

        })
        ->when($status !== '', function ($query) use ($status) {

            return $query->where('status', $status);

        })
        ->when($approve !== '', function ($query) use ($approve) {

            return $query->where('approve', $approve);

        })
        ->when($fromDate !== '' && $toDate !== '', function ($query) use ($fromDate, $toDate) {

            return $query->whereBetween('created_at', [$fromDate, $toDate]);

        })
        ->orderBy($orderBy, $order)->paginate($perPage);

        if ($resp) $result = new KeywordCollection($resp);

        return $result;
    }

}
