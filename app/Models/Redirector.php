<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Str;

use App\Http\Resources\RedirectorCollection;

class Redirector extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'url', 'slug', 'created_by', 'title', 'image', 'keywords', 'description', 'safe_redirect', 'status', 'total_click_perday', 'total_click'
    ];

    // public function setNameAttribute ($name) {
    //     $this->attributes['name'] = $name;
    //     $this->attributes['slug'] = Str::slug($name);
    // }

    public function user() {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function missions () {
        return $this->hasMany(Mission::class, 'redirector_id');
    }

    public function trackers () {
        return $this->hasMany(Tracker::class, 'redirector_id');
    }

    public function listItems ($params) {

        $result = [];

        $perPage = isset($params['per_page']) ? (int) $params['per_page'] : 10000;

        $orderBy = isset($params['order_by']) ? $params['order_by'] : 'created_at';

        $order = isset($params['order']) ? $params['order'] : 'desc';

        $countMission = isset($params['count_mission']) ? $params['count_mission'] : '';

        $name = isset($params['name']) ? $params['name'] : '';

        $createdBy = isset($params['created_by']) ? $params['created_by'] : '';

        $url = isset($params['url']) ? $params['url'] : '';

        $userName = isset($params['user']) ? $params['user'] : '';

        $status = isset($params['status']) ? $params['status'] : '';

        $safeRedirect = isset($params['safe_redirect']) ? $params['safe_redirect'] : '';


        $resp = self::query()->with('user')

        ->when($countMission, function ($query){

            return $query->withCount('missions');

        })

        ->when(auth()->user()->role !== 'admin', function($query) {

            $query->where('created_by', auth()->user()->id);

        })

        ->when($name !== '', function ($query) use ($name) {

            return $query->where('name', 'like', '%' .$name. '%');

        })
        ->when($url !== '', function ($query) use ($url) {

            return $query->where('url', 'like', '%' .$url. '%');

        })
        ->when($userName !== '', function ($query) use ($userName) {

            return $query->whereHas('user', function($query) use ($userName) {
                $query->where('name', 'like', '%' . $userName .'%');
            });

        })
        ->when($status !== '', function ($query) use ($status) {

            return $query->where('status', $status);

        })
        ->when($safeRedirect !== '', function ($query) use ($safeRedirect) {

            return $query->where('safe_redirect', $safeRedirect);

        })
        ->when($createdBy !== '', function ($query) use ($createdBy) {

            return $query->where('created_by', $createdBy);

        })
        ->orderBy($orderBy, $order)->paginate($perPage);

        if ($resp) $result = new RedirectorCollection($resp);

        return $result;
    }
}
