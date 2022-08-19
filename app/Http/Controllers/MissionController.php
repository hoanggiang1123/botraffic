<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Mission as MainModel;
use App\Http\Requests\MissionRequest as MainRequest;

use App\Models\Keyword;
use App\Models\Tracker;

use Browser;

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

    public function getMission(Request $request) {

        $mission = null;
        $ipAddress = $request->ip_address ? $request->ip_address : '';

        if (!$ipAddress) return response(['message' => 'Not Found'], 404);

        $mission = $this->model->with('keyword')->where('ip', $ipAddress)->where('status', 0)->first();

        return $mission;
    }

    public function takeMission(Request $request) {
        $ipAddress = $request->ip_address ? $request->ip_address : '';

        if (!$ipAddress) return response(['message' => 'Not Found'], 404);

        $mission = $this->model->with('keyword')->where('ip', $ipAddress)->where('status', 0)->first();

        if ($mission) return $mission;

        $notAllowKeyWordIds = $this->model->query()
            ->where('ip', $ipAddress)
            ->where('status', 1)->get()
            ->map(function($mission){
                return $mission->keyword_id;
            })
            ->toArray();

        $keyword = Keyword::query()
            ->where('status', 1)
            ->where('approve', 1)
            ->when(count($notAllowKeyWordIds) > 0, function($query) use($notAllowKeyWordIds) {
                $query->whereNotIn('id', $notAllowKeyWordIds);
            })
            ->inRandomOrder()->limit(1)->first();

        if ($keyword) {

            $mission = $this->model->create([
                'keyword_id' => $keyword->id,
                'status' => 0,
                'ip' => $ipAddress,
                'created_by' => auth()->user() && auth()->user()->id ? auth()->user()->id : null
            ]);

            return $mission->load('keyword');
        }

        return response(['keyword' => null]);
    }

    public function getMissionCode (Request $request) {

        $code = '';

        $ipAddress = $request->ip_address ? $request->ip_address : '';

        $domain = $request->domain ? $request->domain : '';

        if (!$ipAddress || !$domain) return response(['message' => 'Not Found'], 404);

        $missions = $this->model->with('keyword')->where('ip', $ipAddress)->where('status', 0)->get();

        if ($missions && count($missions) > 0) {

            foreach ($missions as $mission) {

                if (rtrim($mission->keyword->url, '/') === rtrim($domain, '/')) {

                    $code = uniqid();

                    $mission->update(['code' => $code]);

                    break;
                }
            }
        }

        return response(['code' => $code]);
    }

    public function getConfirmMission (Request $request) {
        $ipAddress = $request->ip_address ? $request->ip_address : '';

        $code = $request->code ? $request->code : '';

        $slug = $request->slug ? $request->slug : '';

        if (!$ipAddress || !$code) return response(['message' => 'Not Found'], 404);

        $mission = $this->model->with('keyword')->where('ip', $ipAddress)->where('code', $code)->first();

        if ($mission) {

            $mission->update(['status' => 1]);

            if (auth()->user()) auth()->user()->increment('point');

            $deviceType = Browser::deviceType();
            $deviceName = Browser::deviceFamily();
            $browser = Browser::browserFamily();
            $os = Browser::platformFamily();

            $tracker = Tracker::create([
                'ip' => $ipAddress,
                'keyword_id' => $mission->keyword->id,
                'device_type' => $deviceType,
                'device_name' => $deviceName,
                'browser' => $browser,
                'os' => $os,
                'user_id' => auth()->user() && auth()->user()->id ? auth()->user()->id : null
            ]);

            if ($slug !== '') {

                $redirector = $this->model->where('slug', $slug)->first();

                if ($redirector) {

                    $tracker->update(['redirector_id' => $redirector->id]);

                    return \response(['source' => $redirector->url]);
                }
                else {

                    $redirector = $this->model->inRandomOrder()->limit(1)->first();

                    if ($redirector) {

                        return \response(['source' => $redirector->url]);
                    }

                    return response(['source' => null]);

                }
            }

            return response(['source' => null]);

        }

        return response(['message' => 'Mã không chính xác'], 401);
    }
}
