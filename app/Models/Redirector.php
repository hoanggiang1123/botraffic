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
        'name', 'url', 'slug', 'created_by'
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

    public function listItems ($params) {

        $result = [];

        $perPage = isset($params['per_page']) ? (int) $params['per_page'] : 10000;

        $orderBy = isset($params['order_by']) ? $params['order_by'] : 'created_at';

        $order = isset($params['order']) ? $params['order'] : 'desc';

        $countMission = isset($params['count_mission']) ? $params['count_mission'] : '';

        $name = isset($params['name']) ? $params['name'] : '';

        $createdBy = isset($params['created_by']) ? $params['created_by'] : '';

        $resp = self::query()->with('user')

        ->when($countMission, function ($query){

            return $query->withCount('missions');

        })
        ->when($name !== '', function ($query) use ($name) {

            return $query->where('name', 'like', '%' .$name. '%');

        })->when($createdBy !== '', function ($query) use ($createdBy) {

            return $query->where('created_by', $createdBy);

        })->orderBy($orderBy, $order)->paginate($perPage);

        if ($resp) $result = new RedirectorCollection($resp);

        return $result;
    }
}
