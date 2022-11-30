<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\InternalLink as MainModel;
use App\Http\Requests\InternalLinkRequest as MainRequest;

class InternalLinkController extends Controller
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

        if (!isset($data['created_by']) || empty($data['created_by'])) $data['created_by'] = auth()->user()->id;

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
