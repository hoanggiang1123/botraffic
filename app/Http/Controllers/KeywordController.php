<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Keyword as MainModel;
use App\Http\Requests\KeywordRequest as MainRequest;

use App\Models\User;


class KeywordController extends Controller
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

        if (!isset($data['created_by'])) $data['created_by'] = auth()->user()->id;

        if (isset($data['traffic'])) $data['traffic_count'] = $data['traffic'];

        if (auth()->user()->role !== 'admin' && isset($data['approve'])) {

            unset($data['approve']);

            // if (isset($data['traffic']) && (int) $data['traffic'] > 0) {

            //     $traffic = (int) $data['traffic'];

            //     if ($traffic > auth()->user()->point) {

            //         return response(['message' => 'Bạn không đủ BCOIN để chạy traffic, vui lòng nạp tiền'], 422);
            //     }
            //     else {

            //         $point = (auth()->user()->point) - $traffic;

            //         auth()->user()->update(['point' =>  $point]);
            //     }
            // }
        }

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

                // if (isset($data['traffic'])) {

                //     $traffic = (int) $data['traffic'];

                //     $trafficAble = $item->traffic + auth()->user()->point;

                //     if ($traffic > $trafficAble) {
                //         return response(['message' => 'Bạn không đủ BCOIN để chạy traffic, vui lòng nạp tiền'], 422);
                //     }
                //     else {

                //         $point = $trafficAble - $traffic;

                //         User::where('id', auth()->user()->id)->update(['point' =>  $point]);
                //     }
                // }
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

    public function show (Request $request, $id) {

        $keyword = $this->model->withCount('trackers')->where('id', $id)->first();

        if ($keyword) {
            return $keyword;
        }

        return \response(['message' => 'Not Found'], 404);
    }
}
