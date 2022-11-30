<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Http\Resources\InternalLinkCollection;

class InternalLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'anchor_text', 'link', 'count', 'click', 'keyword_id', 'created_by', 'status'
    ];

    public function keyword () {
        return $this->belongsTo(Keyword::class, 'keyword_id');
    }

    public function user () {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function listItems ($params) {

        $result = [];

        $perPage = isset($params['per_page']) ? (int) $params['per_page'] : 10000;

        $orderBy = isset($params['order_by']) ? $params['order_by'] : 'created_at';

        $order = isset($params['order']) ? $params['order'] : 'desc';

        $anchorText = isset($params['anchor_text']) ? $params['anchor_text'] : '';

        $link = isset($params['link']) ? $params['link'] : '';

        $keywordId = isset($params['keyword_id']) ? $params['keyword_id'] : '';

        $status = isset($params['status']) ? $params['status'] : '';


        $resp = self::query()->with('user')

        ->when($anchorText !== '', function ($query) use ($anchorText) {

            return $query->where('anchor_text', 'like', '%' .$anchorText. '%');

        })
        ->when($link !== '', function ($query) use ($link) {

            return $query->where('link','like', '%' .$link. '%');

        })

        ->when($keywordId !== '', function ($query) use ($keywordId) {

            return $query->where('keyword_id', $keywordId);
        })

        ->when($status !== '', function ($query) use ($status) {

            return $query->where('status', $status);
        })


        ->orderBy($orderBy, $order)->paginate($perPage);

        if ($resp) $result = new InternalLinkCollection($resp);

        return $result;
    }
}
