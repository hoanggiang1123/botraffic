<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Mission as MainModel;
use App\Http\Requests\MissionRequest as MainRequest;

class MissionController extends Controller
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

        $item = $this->model->create($request->all());

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

    public function show (Request $request, $id) {

        $keyword = $this->model->where('id', $id)->first();

        if ($keyowrd) {
            return $keyword;
        }

        return \response(['message' => 'Not Found'], 404);
    }
}
