<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Mission as MainModel;
use App\Http\Requests\MissionRequest as MainRequest;

use App\Models\Keyword;
use App\Models\Tracker;
use App\Models\Redirector;
use App\Models\User;
use App\Models\LimitIp;

use Browser;
use Exception;
use Illuminate\Support\Facades\DB;

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

        $mission = $this->model->with('keyword')
            ->where('ip', $ipAddress)
            ->where('status', 0)
            ->first();

        return $mission;
    }

    public function takeMission(Request $request) {

        $ipAddress = $request->ip_address ? $request->ip_address : '';
        $slug = $request->slug ? $request->slug : '';

        if (!$ipAddress) return response(['message' => 'Not Found'], 404);

        $checkCount = LimitIp::where('ip', $ipAddress)->first();

        if ($checkCount && $checkCount->count >= 5) {

            $redirect = Redirector::where('slug', $slug)->first();

            if ($redirect) {

                return response(['url' => $redirect->url]);
            }

            return reponse(['message' => 'Not Found'], 404);
        }

        $mission = $this->model->with('keyword')
            ->where('ip', $ipAddress)
            ->where('status', 0)
            ->first();

        if ($mission && $mission->keyword) {

            if ($mission->keyword) {

                $keywordCheck = Keyword::where('id', $mission->keyword->id)->first();

                if ($keywordCheck && $keywordCheck->status === 1) {

                    return $mission;
                }
                else if ($keywordCheck->status === 0) {

                    $mission->delete();
                }
            }
            else {
                $mission->delete();
            }
        }

        $redirectorCheck = Redirector::where('slug', $slug)->first();

        if ($redirectorCheck) {

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
                ->where('traffic', '>', 0)
                ->when(count($notAllowKeyWordIds) > 0, function($query) use($notAllowKeyWordIds) {
                    $query->whereNotIn('id', $notAllowKeyWordIds);
                })
                ->where('created_by', $redirectorCheck->created_by)
                ->orderBy('priority', 'asc')->first();

            if ($keyword) {

                $mission = $this->model->create([
                    'keyword_id' => $keyword->id,
                    'status' => 0,
                    'ip' => $ipAddress,
                    'created_by' => auth()->user() && auth()->user()->id ? auth()->user()->id : null
                ]);

                return $mission->load('keyword');
            }

            return response(['url' => $redirectorCheck->url]);
        }

        return reponse(['message' => 'Not Found'], 404);
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

        DB::beginTransaction();

        try {
            $ipAddress = $request->ip_address ? $request->ip_address : '';

            $code = $request->code ? $request->code : '';

            $slug = $request->slug ? $request->slug : '';

            if (!$ipAddress || !$code) return response(['message' => 'Not Found'], 404);

            $mission = $this->model->with('keyword')
                    ->when(auth()->user() && auth()->user()->id, function($query) {
                        $query->where('created_by', auth()->user()->id);
                    })
                    ->where('ip', $ipAddress)
                    ->where('code', $code)
                    ->where('status', 0)
                    ->first();

            if ($mission) {

                $mission->update(['status' => 1]);

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

                $checkCount = LimitIp::where('ip', $ipAddress)->first();

                if ($checkCount) {
                    $checkCount->increment('count');
                }
                else {
                    LimitIp::create(['ip' => $ipAddress, 'count' => 1]);
                }

                if ($slug !== '') {

                    $redirector = Redirector::where('slug', $slug)->first();

                    if ($redirector) {

                        $tracker->update(['redirector_id' => $redirector->id]);

                        DB::commit();

                        return \response(['source' => $redirector->url]);
                    }
                    else {

                        $redirector = Redirector::inRandomOrder()->limit(1)->first();

                        if ($redirector) {

                            DB::commit();

                            return \response(['source' => $redirector->url]);
                        }

                        DB::commit();

                        return response(['source' => null]);

                    }
                }

                DB::commit();

                return response(['source' => null]);

            }

            return response(['message' => 'Mã không chính xác'], 401);
        } catch (\Exception $err) {
            DB::rollBack();
            return response(['message' => 'Có lỗi xảy ra'], 422);
        }

    }

    public function getMissionComplete (Request $request) {

        return (new Tracker)->listItems($request->all());
    }
}
