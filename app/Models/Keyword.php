<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Keyword extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'url', 'picture', 'time_on_site'
    ];

    public function user () {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function listItems ($params) {

        $result = [];

        $perPage = isset($params['per_page']) ? (int) $params['per_page'] : 10000;

        $orderBy = isset($params['order_by']) ? $params['order_by'] : 'created_at';

        $order = isset($params['order']) ? $params['order'] : 'desc';

        $resp = self::query()

        ->when(isset($params['name']) && $params['name'] !== '', function ($query) use ($params) {

            return $query->where('name', 'like', '%' .$params['name']. '%');

        })->when(isset($params['description'])  && $params['description'] !== '', function ($query) use ($params) {

            return $query->where('description','like', '%' .$params['description']. '%');

        })->orderBy($orderBy, $order)->paginate($perPage);

        if ($resp) $result = $resp;

        return $result;
    }

}
