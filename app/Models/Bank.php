<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Http\Resources\BankCollection;

class Bank extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_name', 'bank_account', 'bank_account_name', 'phone', 'momo_name', 'email', 'type', 'user_id', 'default'
    ];

    public function user () {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function listItems ($params) {
        $result = [];

        $perPage = isset($params['per_page']) ? (int) $params['per_page'] : 10000;

        $orderBy = isset($params['order_by']) ? $params['order_by'] : 'created_at';

        $order = isset($params['order']) ? $params['order'] : 'desc';

        $resp = self::where('user_id', auth()->user()->id)->orderBy($orderBy, $order)->paginate($perPage);

        if ($resp) $result = new BankCollection($resp);;

        return $result;
    }
}
