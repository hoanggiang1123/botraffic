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
use App\Models\BadIp;
use App\Models\BlockIp;

use Browser;
use Exception;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Log;

use Illuminate\Support\Str;

use Illuminate\Support\Facades\Cache;

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

        if (!$ipAddress) {
            Log::info('Missing Ip address --takemission');
            return response(['message' => 'Not Found'], 404);
        };

        $checkCount = LimitIp::where('ip', $ipAddress)->first();

        if ($checkCount && $checkCount->count >= 4) {

            Log::info("ip $ipAddress vượt quá 4 lần --takemission");

            $redirectorCheck = Redirector::where('slug', $slug)->first();

            if ($redirectorCheck) {

                return response(['url' => $redirectorCheck->url]);
            }

            return response(['message' => 'Not Found'], 404);
        }

        $mission = $this->model->with('keyword')
            ->where('ip', $ipAddress)
            ->where('status', 0)
            ->first();

        if ($mission && $mission->keyword) {

            if ($mission->keyword) {

                $keywordCheck = Keyword::where('id', $mission->keyword->id)->first();

                if ($keywordCheck && $keywordCheck->status === 1) {

                    $internalLink = null;

                    if ($keywordCheck->internal === 1) {

                        $internalLink = \App\Models\InternalLink::where('id', $mission->internal_link_id)->where('status', 1)->first();

                        if (!$mission->internal_link_id) {
                            if ($internalLink)
                            {
                                $mission->internal_link_id = $internalLink->id;
                                $mission->save();

                            }
                        }

                    }

                    return [
                        'mission' => $mission,
                        'anchor' => $internalLink ? $internalLink->anchor_text : null
                    ];
                }
                else if ($keywordCheck->status === 0) {
                    Log::info("Từ khóa đã xóa hoặc status === 0, $ipAddress --takemission");
                    $mission->delete();
                    // return reponse(['message' => 'Not Found'], 404);
                }
            }
            else {
                $mission->delete();
                // return reponse(['message' => 'Not Found'], 404);
                Log::info("Từ khóa không tồn tại, $ipAddress --takemission");
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
                ->where('traffic_count', '>', 0)
                ->when(count($notAllowKeyWordIds) > 0, function($query) use($notAllowKeyWordIds) {
                    $query->whereNotIn('id', $notAllowKeyWordIds);
                })
                ->where('created_by', $redirectorCheck->created_by)
                ->inRandomOrder()->first();

            if ($keyword) {

                $keyword->decrement('traffic_count');

                $internalLink = null;

                if ($keyword->internal === 1) {

                    $internalLink = \App\Models\InternalLink::where('keyword_id', $keyword->id)->where('status', 1)->inRandomOrder()->first();
                }

                $mission = $this->model->create([
                    'keyword_id' => $keyword->id,
                    'status' => 0,
                    'ip' => $ipAddress,
                    'created_by' => auth()->user() && auth()->user()->id ? auth()->user()->id : null,
                    'internal_link_id' => $internalLink ? $internalLink->id : null
                ]);

                return [
                    'mission' => $mission->load('keyword'),
                    'anchor' =>  $internalLink ? $internalLink->anchor_text : null
                ];
            }
            Log::info("Hết nhiệm vụ, không có nhiệm vụ, hết count $ipAddress, $slug --takemission");
            return response(['url' => $redirectorCheck->url]);
        }

        Log::info("Không tồn tại slug: $slug --takemission");

        return response(['message' => 'Not Found'], 404);
    }

    public function takeMissionNew(Request $request)
    {
        $ipAddress = $request->ip_address ? $request->ip_address : '';
        $slug = $request->slug ? $request->slug : '';

        if (!$ipAddress) {
            Log::info('Missing Ip address --takemission');
            return response(['message' => 'Not Found'], 404);
        };

        $block = BlockIp::where('ip', $ipAddress)->first();

        if ($block) {

            Log::info("ip $ipAddress in black list --takemission");

            $redirectorCheck = Redirector::where('slug', $slug)->first();

            if ($redirectorCheck) {

                return response(['url' => $redirectorCheck->alternative_link ? $redirectorCheck->alternative_link : $redirectorCheck->url]);
            }

            return response(['message' => 'Not Found'], 404);
        }

        $checkCount = LimitIp::where('ip', $ipAddress)->first();

        if ($checkCount && $checkCount->count >= 2) {

            Log::info("ip $ipAddress vượt quá 4 lần --takemission");

            $badIp = BadIp::where('ip', $ipAddress)->first();

            $redirectorCheck = Redirector::where('slug', $slug)->first();

            if ($badIp)
            {
                $badIp->increment('count');

            } else {
                BadIp::create(['ip' => $ipAddress, 'count' => 1, 'user_id' => $redirectorCheck ? $redirectorCheck->created_by : null]);
            }


            if ($redirectorCheck) {

                return response(['url' => $redirectorCheck->url]);
            }

            return response(['message' => 'Not Found'], 404);
        }

        $mission = $this->model->with('keyword')
            ->where('ip', $ipAddress)
            ->where('status', 0)
            ->first();

        if ($mission) {

            if ($mission->keyword) {

                $keywordCheck = Keyword::where('id', $mission->keyword->id)->first();

                if ($keywordCheck && $keywordCheck->status === 1) {

                    $internalLink = null;

                    if ($keywordCheck->internal === 1) {

                        $internalLink = \App\Models\InternalLink::where('id', $mission->internal_link_id)->where('status', 1)->first();

                        if (!$mission->internal_link_id) {
                            if ($internalLink)
                            {
                                $mission->internal_link_id = $internalLink->id;
                                $mission->save();

                            }
                        }

                    }

                    return [
                        'mission' => $mission,
                        'anchor' => $internalLink ? $internalLink->anchor_text : null
                    ];
                }
                else if ($keywordCheck->status === 0) {
                    Log::info("Từ khóa đã xóa hoặc status === 0, $ipAddress --takemission");
                    $mission->delete();
                    // return reponse(['message' => 'Not Found'], 404);
                }
            }
            else {
                $mission->delete();
                // return reponse(['message' => 'Not Found'], 404);
                Log::info("Từ khóa không tồn tại, $ipAddress --takemission");
            }
        }

        $notAllowIds = [];
        $notAllowUrl = [];

        $completeKeyword = $this->model->query()
            ->with('keyword')
            ->where('ip', $ipAddress)
            ->where('status', 1)->get();

        foreach ($completeKeyword as $keyword)
        {
            $notAllowIds[] = $keyword->keyword_id;

            if ($keyword->keyword && $keyword->keyword->url)
            {
                $url = $this->getHostNameFromUrl($keyword->keyword->url);

                if ($url)  $notAllowUrl[] = $url;

            }
        }

        $keywords= Keyword::query()
            ->where('status', 1)
            ->where('approve', 1)
            ->where('traffic_count', '>', 0)
            ->when(count($notAllowIds) > 0, function($query) use($notAllowIds) {
                $query->whereNotIn('id', $notAllowIds);
            })
            ->inRandomOrder()->limit(10)->get();

        $selectedKeyword = null;

        foreach ($keywords as $keyword)
        {
            $url = $this->getHostNameFromUrl($keyword->url);

            if ( !in_array($url, $notAllowUrl) )
            {
                $selectedKeyword = $keyword;
                break;
            }
        }

        if ($selectedKeyword)
        {

            $selectedKeyword->decrement('traffic_count');

            $internalLink = null;

            if ($selectedKeyword->internal === 1) {

                $internalLink = \App\Models\InternalLink::where('keyword_id', $selectedKeyword->id)->where('status', 1)->inRandomOrder()->first();
            }

            $mission = $this->model->create([
                'keyword_id' => $selectedKeyword->id,
                'status' => 0,
                'ip' => $ipAddress,
                'created_by' => auth()->user() && auth()->user()->id ? auth()->user()->id : null,
                'internal_link_id' => $internalLink ? $internalLink->id : null
            ]);

            return [
                'mission' => $mission->load('keyword'),
                'anchor' =>  $internalLink ? $internalLink->anchor_text : null
            ];
        } else {

        }

        $redirectorCheck = Redirector::where('slug', $slug)->first();

        if ($redirectorCheck) {
            Log::info("Hết nhiệm vụ, không có nhiệm vụ, hết count $ipAddress, $slug --takemission");
            return response(['url' => $redirectorCheck->alternative_link ? $redirectorCheck->alternative_link : $redirectorCheck->url]);
        }

        Log::info("Không tồn tại slug: $slug --takemission");

        return response(['message' => 'Not Found'], 404);

    }


    public function takeMissionVerOne(Request $request)
    {

        $ipAddress = $request->ip_address ? $request->ip_address : '';
        $slug = $request->slug ? $request->slug : '';

        $mission = $this->model->with('keyword')
            ->where('ip', $ipAddress)
            ->where('status', 0)
            ->first();

        if ($mission) {

            if ($mission->keyword) {

                $keywordCheck = Keyword::where('id', $mission->keyword->id)->first();

                if ($keywordCheck && $keywordCheck->status === 1) {

                    $internalLink = null;

                    if ($keywordCheck->internal === 1) {

                        $internalLink = \App\Models\InternalLink::where('id', $mission->internal_link_id)->where('status', 1)->first();

                        if (!$mission->internal_link_id)
                        {
                            if ($internalLink)
                            {
                                $mission->internal_link_id = $internalLink->id;
                                $mission->save();

                            }
                        }
                    }

                    return [
                        'mission' => $mission,
                        'anchor' => $internalLink ? $internalLink->anchor_text : null
                    ];
                }
                else if ($keywordCheck->status === 0)
                {
                    Log::info("Từ khóa đã xóa hoặc status === 0, $ipAddress --takemission");

                    $mission->delete();
                }
            }
            else {
                $mission->delete();

                Log::info("Từ khóa không tồn tại, $ipAddress --takemission");
            }
        }

        $notAllowDomains = $this->getAllowDomains( $ipAddress );

        $redirectorCheck = Redirector::where('slug', $slug)->first();

        $createdBy = $redirectorCheck && $redirectorCheck->created_by ? $redirectorCheck->created_by : null;

        $keyword = Keyword::query()
            ->where('status', 1)
            ->where('approve', 1)
            ->where('traffic_count', '>', 0)
            ->when(is_array($notAllowDomains) && count($notAllowDomains) > 0, function($query) use($notAllowDomains) {
                $query->whereNotIn('domain', $notAllowDomains);
            })
            ->when($createdBy, function($query) use ($createdBy){
                $query->where('created_by', $createdBy);
            })
            ->inRandomOrder()->first();

        if (!$keyword) {
            $keyword = Keyword::query()
            ->where('status', 1)
            ->where('approve', 1)
            ->where('traffic_count', '>', 0)
            ->when(is_array($notAllowDomains) && count($notAllowDomains) > 0, function($query) use($notAllowDomains) {
                $query->whereNotIn('domain', $notAllowDomains);
            })
            ->inRandomOrder()->first();
        }

        if ($keyword)
        {
            $keyword->decrement('traffic_count');

            $internalLink = null;

            if ($keyword->internal === 1) {

                $internalLink = \App\Models\InternalLink::where('keyword_id', $keyword->id)->where('status', 1)->inRandomOrder()->first();
            }

            $mission = $this->model->create([
                'keyword_id' => $keyword->id,
                'status' => 0,
                'ip' => $ipAddress,
                'created_by' => auth()->user() && auth()->user()->id ? auth()->user()->id : null,
                'internal_link_id' => $internalLink ? $internalLink->id : null
            ]);

            return [
                'mission' => $mission->load('keyword'),
                'anchor' =>  $internalLink ? $internalLink->anchor_text : null
            ];
        }

        $redirectorCheck = Redirector::where('slug', $slug)->first();

        if ($redirectorCheck) {
            Log::info("Hết nhiệm vụ, không có nhiệm vụ, hết count $ipAddress, $slug --takemission");
            return response(['url' => $redirectorCheck->alternative_link ? $redirectorCheck->alternative_link : $redirectorCheck->url]);
        }

        Log::info("Không tồn tại slug: $slug --takemission");

        return response(['message' => 'Not Found'], 404);

    }


    public function getAllowDomains ( $ipAddress )
    {
        $totalDomain = Cache::get('total_domain');

        $domains = Cache::get( $ipAddress );

        if ( is_array( $domains ) && count( $domains ) >= $totalDomain) {

            Cache::forget( $ipAddress );

            return [];
        }

        return is_array( $domains ) && count( $domains ) > 0 ? $domains : [];
    }

    public function setNotAllowDomains( $ipAddress, $domain )
    {
        $domains = Cache::get( $ipAddress );

        if( is_array( $domains ) && count( $domains ) > 0 ) {
            $domains[] = $domain;

            Cache::put($ipAddress, $domains);
        }
        else {
            Cache::put($ipAddress, [$domain]);
        }
    }

    public function getHostNameFromUrl ($input) {

        $input = trim($input, '/');

        if (!preg_match('#^http(s)?://#', $input)) {
            $input = 'http://' . $input;
        }

        $urlParts = parse_url($input);

        if (isset($urlParts['host'])) {
            $domain_name = preg_replace('/^www\./', '', $urlParts['host']);

            $check = explode('.', $domain_name);

            if (count($check) > 2) {
                return $check[1] . '.' . $check[2];
            }

            return $domain_name;
        }

        return '';

    }

    public function getMissionCode (Request $request) {

        $code = '';
        $internal = false;

        $ipAddress = $request->ip_address ? $request->ip_address : '';

        $domain = $request->domain ? $request->domain : '';

        $ua = $request->ua ? $request->ua : '';

        if (!$ipAddress || !$domain) {

            Log::info('Không tồn tại ip hoặc tên miền --getCode');

            return response(['message' => 'Not Found'], 404);
        }

        $mission = $this->model->with('keyword')->where('ip', $ipAddress)->where('status', 0)->first();

        if ($mission) {

            if($mission->ua != $ua) {
                Log::info('Curl: ' . $ipAddress);
                return response(['code' => Str::random(6), 'internal' => false]);
            }

            $checkLink = rtrim($mission->keyword->url, '/');

            if ($mission->internal_link_id) {

                $internalLink = \App\Models\InternalLink::where('id', $mission->internal_link_id)->first();

                $checkLink = rtrim($internalLink->link, '/');

                $internal = true;
            }

            if ($checkLink === rtrim($domain, '/')) {

                $code = Str::random(6);

                $mission->update(['code' => $code]);
            }
        }


        return response(['code' => $code, 'internal' => $internal]);
    }

    public function getConfirmMission (Request $request) {

        DB::beginTransaction();

        try {
            $ipAddress = $request->ip_address ? $request->ip_address : '';

            $code = $request->code ? $request->code : '';

            $slug = $request->slug ? $request->slug : '';

            $redirector = Redirector::where('slug', $slug)->first();

            $createdBy = $redirector ? $redirector->created_by : 'N/A';

            if (!$ipAddress || !$code || !$redirector) {

                Log::info("Không tồn tại ip hoặc code --getConfirm");

                return response(['message' => 'Not Found'], 404);
            }

            $mission = $this->model->with('keyword')
                    ->when(auth()->user() && auth()->user()->id, function($query) {
                        $query->where('created_by', auth()->user()->id);
                    })
                    ->where('ip', $ipAddress)
                    ->where('code', $code)
                    ->where('status', 0)
                    ->first();

            if ($mission && $mission->keyword && $mission->keyword->status === 1) {

                // $checkTime = $mission->internal_link_id ? 10 : 50;

                // if (time() - strtotime($mission->updated_at) < $checkTime) {

                //     Log::info("Xác nhận mã quá nhanh (bot) $ipAddress, $slug, $code --getConfirm");

                //     return response(['message' => 'Not Found'], 404);
                // };

                // if ($mission->is_start == 1) {
                //     if (time() - strtotime($mission->updated_at) < 35) {

                //         Log::info("Xác nhận mã quá nhanh (bot) is_start $ipAddress, $slug, $code --getConfirm");

                //         return response(['message' => 'Not Found'], 404);
                //     };
                // }

                $mission->update(['status' => 1]);

                $keyword = Keyword::where('id', $mission->keyword->id)->first();
                $keyword->increment('total_click_perday');
                $keyword->increment('total_click');

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

                $this->setNotAllowDomains($ipAddress, $mission->keyword->domain);

                $checkCount = LimitIp::where('ip', $ipAddress)->first();

                if ($checkCount) {
                    $checkCount->increment('count');
                }
                else {
                    $resetArray = [1, 2, 3, 4];

                    $randomKeys = array_rand($resetArray);

                    $reset = $resetArray[$randomKeys];

                    LimitIp::create(['ip' => $ipAddress, 'count' => 1, 'reset' => $reset]);
                }

                if ($mission->internal_link_id)
                {

                    $tracker->update(['internal_link_id' => $mission->internal_link_id]);

                    $internalLink = \App\Models\InternalLink::where('id', $mission->internal_link_id)->first();

                    if ($internalLink)
                    {
                        $internalLink->increment('count');
                        $internalLink->increment('click');
                    }

                }

                if ($redirector) {

                    $redirector->increment('total_click_perday');
                    $redirector->increment('total_click');

                    $tracker->update(['redirector_id' => $redirector->id, 'redirector_user_id' => $redirector->created_by]);

                    $device = \App\Models\Device::where('ip', $ipAddress)->first();

                    if (!$device)
                    {
                        \App\Models\Device::create([
                            'ip' => $ipAddress,
                            'user_agent' => $request->header('user-agent'),
                            'redirector_user_id' => $redirector->id
                        ]);
                    }

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

                    Log::info("Không tồn tại slug --getConfirm");

                    return response(['source' => null]);

                }

                DB::commit();

                return response(['source' => null]);

            }
            Log::info("Mã không chính xác --getConfirm $ipAddress, $slug, $code, tạo bởi $createdBy");

            $badIp = BadIp::where('ip', $ipAddress)->first();

            if ($badIp) {

                $badIp->increment('count');
            }
            else {

                BadIp::create(['ip' => $ipAddress, 'count' => 1, 'user_id' => $createdBy]);
            }

            return response(['message' => 'Mã không chính xác'], 401);

        } catch (\Exception $err) {

            DB::rollBack();
            Log::info("Có lỗi xảy ra  $ipAddress, $slug, $code --getConfirm");
            return response(['message' => 'Có lỗi xảy ra'], 422);
        }

    }

    public function getMissionComplete (Request $request) {

        return (new Tracker)->listItems($request->all());
    }

    public function getAnchorText (Request $request) {
        $ipAddress = $request->ip_address ? $request->ip_address : '';

        $domain = $request->domain ? $request->domain : '';

        $anchor = '';

        if (!$ipAddress || !$domain) return response(['message' => 'Not Found'], 404);

        $missions = $this->model->with('keyword')->where('ip', $ipAddress)->where('status', 0)->get();

        if ($missions && count($missions) > 0) {

            foreach ($missions as $mission) {

                if ($mission->keyword) {

                    if (rtrim($mission->keyword->url, '/') === rtrim($domain, '/')) {

                        if ($mission->internal_link_id) {

                            $internalLink = \App\Models\InternalLink::where('id', $mission->internal_link_id)->first();

                            if ($internalLink)
                            {
                                $anchor = $internalLink->anchor_text;
                                break;
                            }

                        }
                    }
                }

            }
        }

        return response(['anchor' => $anchor]);
    }

    public function getScript (Request $request) {
        $ipAddress = $this->getIpAddress();

        $domain = $request->domain ? $request->domain : '';

        $anchor = '';

        if (!$ipAddress || !$domain) return response(['message' => 'Not Found'], 404);

        $missions = $this->model->with('keyword')->where('ip', $ipAddress)->where('status', 0)->get();

        if ($missions && count($missions) > 0) {

            foreach ($missions as $mission) {

                if ($mission->keyword) {

                    if (rtrim($mission->keyword->url, '/') === rtrim($domain, '/')) {

                        if ($mission->internal_link_id) {

                            $internalLink = \App\Models\InternalLink::where('id', $mission->internal_link_id)->first();

                            if ($internalLink)
                            {
                                $anchor = $internalLink->anchor_text;
                                break;
                            }

                        }
                    }
                }

            }
        }

        $id = Str::slug($anchor);

        $script = '
            const anchor = document.querySelector(\'a[data-key="'. $id .'"]\');
            if (anchor) {
                anchor.classList.add("linkhaybtn", "inside", "finish", "animate");

                let check = false;
                let time = Math.floor(Math.random() * (25 - 10) + 10);

                anchor.addEventListener("click", function(e){
                    if (!check) {
                        e.preventDefault();

                        const modal = document.createElement("div");
                        modal.style.position = "fixed";
                        modal.style.zIndex = 999999;
                        modal.style.top = 0;
                        modal.style.left = 0;
                        modal.style.right = 0;
                        modal.style.bottom = 0;
                        modal.style.background = "rgba(0,0,0,0.8)";
                        modal.style.display = "flex";
                        modal.style.width = "100%";
                        modal.style.height = "100%";
                        modal.style.alignItems = "center";
                        modal.style.justifyContent =  "center"

                        const modalText = document.createElement("div");
                        modalText.style.padding = "20px 50px";
                        modalText.style.background = "white";
                        modalText.style.color = "black";
                        modalText.style.borderRadius = "10px";
                        modalText.style.fontWeight = "600";

                        modalText.textContent = "Vui lòng chờ " + time + " s";

                        modal.appendChild(modalText);

                        document.querySelector("body").appendChild(modal);

                        let interVal = setInterval(function(){
                            if (time <= 0) {
                                modal.remove();
                                check = true;
                                clearTimeout(interVal);
                            }
                            else {
                                time--;
                                modalText.textContent =  "Vui lòng chờ " + time + " s";
                            }

                        }, 1000);

                    }

                });
            }
        ';
        return \response($script)->header('Content-Type', 'application/javascript');
    }

    public function getIpAddress()
    {
        $ipAddress = '';
        if (! empty($_SERVER['HTTP_CLIENT_IP'])) {
            // to get shared ISP IP address
            $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
        } else if (! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // check for IPs passing through proxy servers
            // check if multiple IP addresses are set and take the first one
            $ipAddressList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            foreach ($ipAddressList as $ip) {
                if (! empty($ip)) {
                    // if you prefer, you can check for valid IP address here
                    $ipAddress = $ip;
                    break;
                }
            }
        } else if (! empty($_SERVER['HTTP_X_FORWARDED'])) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED'];
        } else if (! empty($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])) {
            $ipAddress = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
        } else if (! empty($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipAddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } else if (! empty($_SERVER['HTTP_FORWARDED'])) {
            $ipAddress = $_SERVER['HTTP_FORWARDED'];
        } else if (! empty($_SERVER['REMOTE_ADDR'])) {
            $ipAddress = $_SERVER['REMOTE_ADDR'];
        }
        return $ipAddress;
    }

    public function getStartMission(Request $request)
    {
        $ipAddress = $request->ip;

        $domain = $request->domain;

        if (!$ipAddress || !$domain) return response(['message' => 'Not Found'], 404);

        $status = 'notok';
        $ua = '';

        $mission = $this->model->with('keyword')->where('ip', $ipAddress)->where('status', 0)->first();

        if ($mission && $mission->keyword && $mission->keyword->url) {

            if (rtrim($mission->keyword->url, '/') === rtrim($domain, '/')) {

                $status = 'ok';
                $ua = uniqid();
                $mission->update(['is_start' => 1, 'ua' => $ua]);
            }
        }

        return response(['message' => $status, 'ua' => $ua], 200);

    }


    public function getCheckMission(Request $request)
    {
        $ipAddress = $request->ip;

        $domain = $request->domain;

        if (!$ipAddress || !$domain) return response(['message' => 'Not Found'], 404);

        $status = 'notok';
        $code = '';

        $mission = $this->model->with('keyword')->where('ip', $ipAddress)->where('status', 0)->first();

        if ($mission && $mission->keyword && $mission->keyword->url) {

            if (rtrim($mission->keyword->url, '/') === rtrim($domain, '/')) {

                $status = 'ok';
                $code = $mission->code;
            }
        }

        return response(['message' => $status, 'code' => $code], 200);

    }

    public function checkIp(Request $request) {
        return $request->ip();
    }
}
