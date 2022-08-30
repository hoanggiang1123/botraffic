<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Transaction as MainModel;
use App\Http\Requests\TransactionRequest as MainRequest;

use App\Models\User;

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

        $data = $request->all();

        $data['created_by'] = auth()->user()->id;

        $data['status'] = auth()->user()->role !== 'admin' ? 0 : $data['status'];

        $item = $this->model->create($data);

        if ($item) {
            return $item;
        }

        return response(['message' => 'Unprocess Entity'], 422);
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
    }
}
