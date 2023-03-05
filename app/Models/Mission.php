<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Http\Resources\MissionCollection;

class Mission extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip', 'keyword_id', 'status', 'code', 'created_by', 'internal_link_id', 'is_start'
    ];

    public function keyword () {
        return $this->belongsTo(Keyword::class, 'keyword_id');
    }

    public function listItems ($params) {

        $result = [];

        $perPage = isset($params['per_page']) ? (int) $params['per_page'] : 10000;

        $orderBy = isset($params['order_by']) ? $params['order_by'] : 'created_at';

        $order = isset($params['order']) ? $params['order'] : 'desc';

        $keywordId = isset($params['keyword_id']) ? $params['keyword_id'] : '';

        $status = isset($params['status']) ? $params['status'] : '';

        $createdBy = isset($params['created_by']) ? $params['created_by'] : '';

        $resp = self::query()

        ->when($keywordId !== '', function ($query) use ($keywordId) {

            return $query->where('keyword_id', $keywordId);

        })->when($status !== '', function ($query) use ($status) {

            return $query->where('status', $status);

        })->when($createdBy !== '', function ($query) use ($createdBy) {

            return $query->where('created_by', $createdBy);

        })->orderBy($orderBy, $order)->paginate($perPage);

        if ($resp) $result = new MissionCollection($resp);

        return $result;
    }
}
