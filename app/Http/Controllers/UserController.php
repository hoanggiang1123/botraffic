<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User as MainModel;

class UserController extends Controller
{
    protected $model;

    public function __construct(MainModel $user)
    {
        $this->model = $user;
    }

    public function search (Request $request) {

        $name = $request->name;

        $results = $this->model->where('name', 'like', '%' . $name . '%')->get()
            ->map(function($item) {
                return [
                    'name' => $item->name,
                    'value' => $item->id
                ];
            })->toArray();

        if (count($results)) return response($results);

        return response(['message' => 'Not Found'], 404);

    }
}
