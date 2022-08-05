<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Redirector;
use App\Models\Mission;
use App\Models\Keyword;

class RedirectorController extends Controller
{
    protected $model;

    public function __construct(Redirector $redirector)
    {
        $this->model = $redirector;
    }

    public function show (Request $request, $slug) {

        $redirector = $this->model->where('slug', $slug)->first();

        if ($redirector) {

            $mission = Mission::where('ip', $request->ip())->where('status', 0)->first();

            if ($mission) return $mission;

            $notAllowKeyWordIds = Mission::query()
                        ->where('ip', $request->ip())
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
                        ->when(count($missionIds) > 0, function($query) use($notAllowKeyWordIds) {
                            $query->whereNotIn('id', $notAllowKeyWordIds);
                        })
                        ->inRandomOrder()->limit(1)->first();

            if ($keyword) {

                $mission = Mission::query()
                            ->where('status', 1)
                            ->where('keyword_id', $keyword->id)
                            ->where('ip', $request->ip())
                            ->first();

                if ($mission) {

                    $mission->update(['status', 0, 'code' => null]);

                    return $mission->load('keyword');
                }

                $mission = Mission::create([
                    'keyword_id' => $keyword->id,
                    'status' => 0,
                    'ip' => $request->ip()
                ]);

                return $mission->load('keyword');
            }

            return response(['message' => 'There is no mission now'], 404);
        }

        return response(['message' => 'Not Found'], 404);
    }

    public function getMissionCode (Request $request) {

    }
}
