<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Http\Resources\TransactionCollection;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'amount', 'status', 'methods', 'created_by', 'type', 'approve', 'note', 'transaction_code', 'payment_info_id'
    ];

    public function user () {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function bank () {
        return $this->belongsTo(Bank::class, 'payment_info_id');
    }


    public function listItems ($params) {

        $result = [];

        $perPage = isset($params['per_page']) ? (int) $params['per_page'] : 10000;

        $orderBy = isset($params['order_by']) ? $params['order_by'] : 'created_at';

        $order = isset($params['order']) ? $params['order'] : 'desc';

        $withUser = isset($params['with_user']) ? $params['with_user'] : '';

        $withBank = isset($params['with_bank']) ? $params['with_bank'] : '';

        $status = isset($params['status']) ? $params['status'] : '';

        $methods = isset($params['methods']) ? $params['methods'] : '';

        $type = isset($params['type']) ? (int) $params['type'] : '';

        $note = isset($params['note']) ? $params['note'] : '';

        $amount = isset($params['amount']) ? $params['amount'] : '';


        $createdBy = auth()->user() && auth()->user()->role &&  auth()->user()->role ===  'admin' ? auth()->user()->id : null;

        $with = [];

        if ($withUser) $with[] = 'user';
        if ($withBank) $with[] = 'bank';

        $resp = self::query()

        ->when(count($with) > 0, function ($query) use ($with) {

            return $query->with($with);

        })

        ->when($createdBy !== 'admin', function ($query) use ($createdBy) {

            return $query->where('created_by', $createdBy);

        })

        ->when($status !== '', function ($query) use ($status) {

            return $query->where('status', $status);

        })
        ->when($methods !== '', function ($query) use ($methods) {

            return $query->where('methods', $methods);

        })

        ->when($type !== '', function ($query) use ($type) {

            return $query->where('type', $type);

        })

        ->when($amount !== '', function ($query) use ($amount) {

            return $query->where('amount', $amount);

        })

        ->when($note !== '', function ($query) use ($note) {

            return $query->where('note', 'like', '%' . $note . '%');

        })

        ->orderBy($orderBy, $order)->paginate($perPage);

        if ($resp) $result = new TransactionCollection($resp);

        return $result;
    }

}
