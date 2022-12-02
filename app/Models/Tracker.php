<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Http\Resources\TrackerCollection;

class Tracker extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip', 'keyword_id', 'device_type', 'device_name', 'os', 'browser', 'user_id', 'redirector_id', 'internal_link_id'
    ];

    public function keyword () {
        return $this->belongsTo(Keyword::class, 'keyword_id');
    }

    public function redirector () {
        return $this->belongsTo(Redirector::class, 'redirector_id');
    }

    public function listItems ($params) {

        $result = [];

        $perPage = isset($params['per_page']) ? (int) $params['per_page'] : 10000;

        $orderBy = isset($params['order_by']) ? $params['order_by'] : 'created_at';

        $order = isset($params['order']) ? $params['order'] : 'desc';

        $keywordId = isset($params['keyword_id']) ? $params['keyword_id'] : '';

        $redirectorId = isset($params['redirector_id']) ? $params['redirector_id'] : '';

        $userId = isset($params['user_id']) ? $params['user_id'] : '';

        $deviceType = isset($params['device_type']) ? $params['device_type'] : '';

        $deviceName = isset($params['device_name']) ? $params['device_name'] : '';

        $os = isset($params['os']) ? $params['os'] : '';

        $browser = isset($params['browser']) ? $params['browser'] : '';

        $withs = [];

        isset($params['with_keyword']) ? $withs[] = 'keyword' : '';

        isset($params['with_redirector']) ? $withs[] = 'redirector' : '';

        $resp = self::query()

        ->when(count($withs) > 0, function($query) use ($withs) {

            return $query->with($withs);
        })

        ->when($keywordId !== '', function ($query) use ($keywordId) {

            return $query->where('keyword_id', $keywordId);

        })
        ->when($redirectorId !== '', function ($query) use ($redirectorId) {

            return $query->where('redirector_id', $redirectorId);

        })
        ->when($userId !== '', function ($query) use ($userId) {

            return $query->where('user_id', $userId);

        })
        ->when($deviceType !== '', function ($query) use ($deviceType) {

            return $query->where('device_type', $deviceType);

        })
        ->when($deviceName !== '', function ($query) use ($deviceName) {

            return $query->where('device_name', $deviceName);

        })
        ->when($os !== '', function ($query) use ($os) {

            return $query->where('os', $os);

        })
        ->when($browser !== '', function ($query) use ($browser) {

            return $query->where('browser', $browser);

        })
        ->orderBy($orderBy, $order)->paginate($perPage);

        if ($resp) $result = new TrackerCollection($resp);

        return $result;
    }
}
