<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests\RedirectorRequest as MainRequest;

use App\Models\Redirector;
use App\Models\Mission;
use App\Models\Keyword;
use App\Models\Tracker;

use Carbon\Carbon;

use Browser;

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

    public function getMission (Request $request) {

        $ipAddress = $request->ip_address ? $request->ip_address : '';

        if (!$ipAddress) return response(['message' => 'Not Found'], 404);

        $mission = Mission::with('keyword')->where('ip', $ipAddress)->where('status', 0)->first();

        if ($mission) return $mission;

        $notAllowKeyWordIds = Mission::query()
                    ->where('ip', $ipAddress)
                    ->where('status', 1)->get()
                    ->filter(function($mission) {

                        $taskDate = Carbon::createFromFormat('Y-m-d H:i:s', $mission->updated_at);

                        $checkDate = Carbon::now()->subDays(3);

                        if ($checkDate->lt($taskDate)) {
                            return $mission;
                        }
                    })
                    ->map(function($mission){
                        return $mission->keyword_id;
                    })
                    ->toArray();

        $keyword = Keyword::query()
                    ->where('status', 1)
                    ->when(count($notAllowKeyWordIds) > 0, function($query) use($notAllowKeyWordIds) {
                        $query->whereNotIn('id', $notAllowKeyWordIds);
                    })
                    ->inRandomOrder()->limit(1)->first();

        if ($keyword) {

            $mission = Mission::query()
                        ->where('status', 1)
                        ->where('keyword_id', $keyword->id)
                        ->where('ip', $ipAddress)
                        ->first();

            if ($mission) {

                $mission->update(['status', 0, 'code' => null]);

                return $mission->load('keyword');
            }

            $mission = Mission::create([
                'keyword_id' => $keyword->id,
                'status' => 0,
                'ip' => $ipAddress
            ]);

            return $mission->load('keyword');
        }

        return response(['message' => 'Not Found'], 404);
    }

    public function getMissionCode (Request $request) {

        $code = '';

        $ipAddress = $request->ip_address ? $request->ip_address : '';

        $domain = $request->domain ? $request->domain : '';

        if (!$ipAddress || !$domain) return response(['message' => 'Not Found'], 404);

        $missions = Mission::with('keyword')->where('ip', $ipAddress)->where('status', 0)->get();

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

    public function confirmMission (Request $request) {

        $ipAddress = $request->ip_address ? $request->ip_address : '';

        $code = $request->code ? $request->code : '';

        $slug = $request->slug ? $request->slug : '';

        if (!$ipAddress || !$code) return response(['message' => 'Not Found'], 404);

        $mission = Mission::with('keyword')->where('ip', $ipAddress)->where('code', $code)->first();

        if ($mission) {

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

            $mission->update(['status' => 1]);

            $redirector = $this->model->where('slug', $slug)->first();

            if (!$redirector) {

                $redirector = $this->model->inRandomOrder()->limit(1)->first();
            }

            if ($redirector) {
                $tracker->update(['redirector_id' => $redirector->id]);
                return \response(['source' => $redirector->url]);
            }

            return response(['source' => null]);

        }

        return response(['message' => 'Mã không chính xác'], 401);
    }

    // public function store (MainRequest $request) {

    //     $data = $request->all();

    //     if (auth()->user() && auth()->user()->id) $data['created_by'] = auth()->user()->id;

    //     $item = $this->model->create($data);

    //     return $item;
    // }
}
