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

    public function select(Request $request) {
        return $this->model->get();
    }

    public function api () {
        $user = $this->model->where('id', auth()->user()->id)->first();

        if ($user) {

            if (!$user->api) {

                $user->update(['api' => $this->struuid(true)]);
            }

            return response(['api' => $user->api]);
        }

        return response(['message' => 'Not Found'], 404);
    }

    public function struuid($entropy){
        $s=uniqid("",$entropy);
        $num= hexdec(str_replace(".","",(string)$s));
        $index = '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $base= strlen($index);
        $out = '';
            for($t = floor(log10($num) / log10($base)); $t >= 0; $t--) {
                $a = floor($num / pow($base,$t));
                $out = $out.substr($index,$a,1);
                $num = $num-($a*pow($base,$t));
            }
        return $out;
    }

    public function index (Request $request) {
        return $this->model->listItems($request->all());
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

    public function update (Request $request, $id) {

        if (auth()->user()->role !== 'admin') return response(['message' => 'Un anthorized'], 402);

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

        if (auth()->user()->role !== 'admin') return response(['message' => 'Un anthorized'], 402);

        $ids = $request->ids;

        $delete = $this->model->destroy($ids);

        if ($delete) return $delete;

        return response(['message' => 'Unprocess Entity'], 422);
    }
}
