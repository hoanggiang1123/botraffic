<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Transaction as MainModel;
use App\Http\Requests\TransactionRequest as MainRequest;

use App\Models\User;

use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    protected $model;

    public function __construct (MainModel $mainModel) {
        $this->model = $mainModel;
    }

    public function index (Request $request) {
        $items = $this->model->listItems($request->all());

        return $items;
    }

    public function store (MainRequest $request) {

        DB::beginTransaction();

        try {

            $data = $request->all();

            $data['created_by'] = auth()->user()->id;

            $data['status'] = auth()->user()->role !== 'admin' ? 0 : $data['status'];
            $data['transaction_code'] = auth()->user()->id . '-' . time();

            $item = $this->model->create($data);

            // User::where('id', auth()->user()->id)->update([
            //     'balance' => auth()->user()->balance + $data['amount']
            // ]);

            DB::commit();

            return response(['message' => 'Nạp tiền thành công', 'transaction_code' => $item->transaction_code]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response(['message' => 'Unprocess Entity'], 422);
        }

    }

    public function update (MainRequest $request, $id) {

        $item = $this->model->where('id', $id)->first();

        if ($item) {

            $data = $request->all();

            if (auth()->user()->role !== 'admin' && isset($data['approve'])) {

                unset($data['approve']);

                if (isset($data['traffic'])) {

                    $traffic = (int) $data['traffic'];

                    $trafficAble = $item->traffic + auth()->user()->point;

                    if ($traffic > $trafficAble) {
                        return response(['message' => 'Bạn không đủ BCOIN để chạy traffic, vui lòng nạp tiền'], 422);
                    }
                    else {

                        $point = $trafficAble - $traffic;

                        User::where('id', auth()->user()->id)->update(['point' =>  $point]);
                    }
                }
            }

            $update = $item->update($data);

            if ($update) return $update;

            return response(['message' => 'Unprocess Entity'], 422);

        }

        return response(['message' => 'Not Found'], 404);
    }


    public function destroy (Request $request) {

        $ids = $request->ids;

        $delete = $this->model->destroy($ids);

        if ($delete) return $delete;

        return response(['message' => 'Unprocess Entity'], 422);
    }

    public function convert(Request $request) {

        $data = $request->all();

        if (!isset($data['type']) || !isset($data['amount']) || !$data['amount']) {
            return response(['message' => 'Unprocess Entity'], 422);
        }

        if ($data['type'] === 'mcoin') {

            $money = $data['amount'] * 1000;

            if (auth()->user()->balance < $money) {
                return response(['message' => 'Số dư của bạn không đủ'], 422);
            }

            User::where('id', auth()->user()->id)->update([
                'point' => $data['amount'] + auth()->user()->point,
                'balance' => auth()->user()->balance - $money
            ]);

            return response(['message' => 'Mua mcoin thành công']);
        }
        if ($data['type'] === 'mcoin-m') {
            $amount = (float) $data['amount'];
            if (auth()->user()->point < $amount) {
                return response(['message' => 'Số dư của bạn không đủ'], 422);
            }

            User::where('id', auth()->user()->id)->update([
                'point' => auth()->user()->point - $amount,
                'balance' => auth()->user()->balance + ($amount * 1000)
            ]);

            return response(['message' => 'Đổi tiền thành công']);
        }

        if ($data['type'] === 'rcoin-m') {

            $amount = (float) $data['amount'];

            if (auth()->user()->refer_point < $amount) {
                return response(['message' => 'Số dư của bạn không đủ'], 422);
            }

            User::where('id', auth()->user()->id)->update([
                'refer_point' => auth()->user()->refer_point - $amount,
                'balance' => auth()->user()->balance + ($amount * 1000)
            ]);

            return response(['message' => 'Đổi tiền thành công']);
        }
    }

    public function stat () {
        $totalDeposit = $this->model->where('type', 1)->get()->sum('amount');
        $totalWithdraw = $this->model->where('type', 0)->get()->sum('amount');
        $totalPendingDeposit = $this->model->where('type', 1)->where('status', 0)->get()->count();
        $totalPendingWithdraw = $this->model->where('type', 0)->where('status', 0)->get()->count();

        return [
            ['name' => 'Tổng nạp tiền', 'value' =>  $totalDeposit],
            ['name' => 'Tổng rút tiền', 'value' =>  $totalWithdraw],
            ['name' => 'Đang chờ nạp tiền', 'value' =>  $totalPendingDeposit],
            ['name' => 'Đang chờ rút tiền', 'value' =>  $totalPendingWithdraw],
        ];
    }
}
