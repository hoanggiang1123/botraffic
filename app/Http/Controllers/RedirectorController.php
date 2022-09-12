<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests\RedirectorRequest as MainRequest;

use App\Models\Redirector;
use App\Models\Mission;
use App\Models\Keyword;
use App\Models\Tracker;
use App\Models\User;

use Carbon\Carbon;

use Browser;
use App\CrawMeta\CrawMeta;

class RedirectorController extends Controller
{
    protected $model;

    public function __construct(Redirector $redirector)
    {
        $this->model = $redirector;
    }

    public function index (Request $request) {
        return $this->model->listItems($request->all());
    }

    public function show (Request $request, $id) {

        $redirector = $this->model->withCount('trackers')->where('id', $id)->first();

        if ($redirector) {
            return $redirector;
        }

        return \response(['message' => 'Not Found'], 404);
    }

    public function store (MainRequest $request) {

        $data = $request->all();

        $data['created_by'] = auth()->user()->id;
        $data['url'] = rtrim($data['url'], '/');

        if (auth()->user()->role === 'admin' && isset($data['safe_redirect']) && $data['safe_redirect'] === 1) {
            $meta = (new CrawMeta)->getMeta($data['url']);
            $data = array_merge($data, $meta);
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

            $data['url'] = rtrim($data['url'], '/');

            if (auth()->user()->role === 'admin' && isset($data['safe_redirect']) && $data['safe_redirect'] === 1) {
                $meta = (new CrawMeta)->getMeta($data['url']);
                $data = array_merge($data, $meta);
            }

            $update = $item->update($data);

            if ($update) return $update;

            return response(['message' => 'Unprocess Entity'], 422);

        }

        return response(['message' => 'Not Found'], 404);
    }

    public function redirect (Request $request) {
        $api = $request->api;
        $url = $request->url;

        $user = User::where('api', $api)->first();
        if ($user) {
            $redirector = $this->model->where('url', rtrim($url, '/'))->where('created_by', $user->id)->first();

            if (!$redirector) {
                $slug = uniqid();

                $redirector = $this->model->create([
                    'url' => $url,
                    'created_by' => $user->id,
                    'slug' => $slug,
                    'name' => $slug
                ]);
            }

            return response(['slug' => $redirector->slug]);
        }

        return response(['message' => 'Not Found'], 404);
    }
}
