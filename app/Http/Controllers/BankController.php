<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Bank as MainModel;
use App\Http\Requests\BankRequest as MainRequest;

class BankController extends Controller
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

        $bankName = $request->bank_name ? $request->bank_name : null;
        $bankAccount = $request->bank_account ? $request->bank_account : null;
        $bankAccountName = $request->bank_account_name ? $request->bank_account_name : null;

        $phone = $request->phone ? $request->phone : null;
        $momoName = $request->momo_name ? $request->momo_name : null;

        $email = $request->email ? $request->email : null;

        $type = $request->type ? $request->type : null;

        if (!$type)  return response(['message' => 'Thông tin bank không hợp lệ'], 422);


        if ($type === 'bank' && (!$bankName || !$bankAccount || !$bankAccountName)) return response(['message' => 'Vui lòng điền đầy đủ thông tin ngân hàng'], 422);

        if ($type === 'momo' && (!$phone || !$momoName)) return response(['message' => 'Vui lòng điền đầy đủ thông tin momo'], 422);

        if ($type === 'paypal' && !$email) return response(['message' => 'Vui lòng nhập email paypal của bạn'], 422);

        $data = [
            'bank_name' => $bankName,
            'bank_account' => $bankAccount,
            'bank_account_name' => $bankAccountName,
            'phone' => $phone,
            'momo_name' => $momoName,
            'email' => $email,
            'type' => $type,
            'user_id' => auth()->user()->id
        ];

        $item = $this->model->create($data);

        if ($item) {
            return $item;
        }

        return response(['message' => 'Unprocess Entity'], 422);
    }

    public function update (MainRequest $request, $id) {

        $item = $this->model->where('id', $id)->first();

        if ($item) {

            $update = $item->update($request->all());

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
}
