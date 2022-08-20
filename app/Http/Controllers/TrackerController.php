<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Tracker as MainModel;

class TrackerController extends Controller
{
    protected $model;

    public function __construct(MainModel $mainModel)
    {
        $this->model = $mainModel;
    }

    public function index (Request $request) {

        return $this->model->listItems($request->all());
    }
}
