<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Redirector;
use App\Models\Tracker;
use App\Models\Keyword;

class ConsoleController extends Controller
{
    public function summary(Request $request) {
        $fromDate = $request->from_date ? $request->from_date : '';
        $toDate = $request->to_date ? $request->to_date : '';
        $userId = $request->user_id ? $request->user_id : '';

        return [
            'link_click' => $this->getClick('redirector_id', $fromDate,  $toDate, $userId),
            // 'keyword_click' => $this->getClick('keyword_id', $fromDate,  $toDate),
        ];
    }

    public function getClick ($type, $fromDate, $toDate, $userId) {

        $ids = [];

        $createdBy = auth()->user()->role === 'admin' ? ($userId ? $userId : '') : auth()->user()->id;

        if ($createdBy) {

            $model = $type === 'keyword_id' ? new Keyword() : new Redirector();

            $ids = $model->where('created_by', $createdBy)->get()->map(function($item) {
                return $item->id;
            })->toArray();

        }

        return Tracker::query()
                ->when(count($ids) > 0, function($query) use($type, $ids) {
                    $query->whereIn($type, $ids);
                })
                ->when($fromDate && $toDate, function($query) use($fromDate, $toDate) {
                    $query->whereBetween('created_at', [$fromDate, $toDate]);
                })
                ->get()
                ->count();
    }

    public function index (Request $request) {

        $fromDate = $request->from_date ? $request->from_date : '';
        $toDate = $request->to_date ? $request->to_date : '';

        $mCoin = auth()->user()->point;
        $rCoin = auth()->user()->refer_point;
        $redirectCoin = auth()->user()->redirect_point;
        $balance = auth()->user()->balance;

        $totalMember = User::query()

            ->when(auth()->user()->role === 'admin', function($query) {

                $query->where('role', '!=', 'admin');
            })

            ->when(auth()->user()->role !== 'admin', function($query) {

                $query->where('refer_id',  auth()->user()->id);
            })

            ->where('id', '!=', auth()->user()->id)

            ->get()->count();


            $redirectors = Redirector::query()

                        ->when(auth()->user()->role !== 'admin', function($query) {

                            $query->where('created_by', auth()->user()->id);
                        })

                        ->get()

                        ->map(function($item) {

                            return $item->id;
                        })

                        ->toArray();

            $totalRedirect = 0;

            if (count($redirectors) > 0) {

                $totalRedirect = Tracker::query()

                            ->whereIn('redirector_id', $redirectors)

                            ->when($fromDate !== '' && $toDate !== '', function($query) use($fromDate, $toDate){

                                $query->whereBetween('created_at', [$fromDate, $toDate]);
                            })

                            ->get()

                            ->count();
            }

            $totalMission = Tracker::query()

                        ->when(auth()->user()->role !== 'admin', function($query) {

                            $query->where('user_id', auth()->user()->id);
                        })

                        ->whereNull('redirector_id')

                        ->when($fromDate !== '' && $toDate !== '', function($query) use($fromDate, $toDate){

                            $query->whereBetween('created_at', [$fromDate, $toDate]);
                        })

                        ->get()

                        ->count();

        return [
            'm_coin' => $mCoin,
            'r_coin' => $rCoin,
            'redirect_coin' => $redirectCoin,
            'balance' => $balance,
            'total_member' => $totalMember,
            'total_redirect' => $totalRedirect,
            'total_mission' => $totalMission,
        ];

    }

    public function chart (Request $request) {

        $fromDate = $request->from_date ? $request->from_date : '';
        $toDate = $request->to_date ? $request->to_date : '';
        $userId = $request->user_id ? $request->user_id : '';

        $createdBy = auth()->user()->role === 'admin' ? ($userId ? $userId : '') : auth()->user()->id;

        $redirectorIds = [];

        if ($createdBy) {

            $redirectorIds = Redirector::query()

                    ->where('created_by', $createdBy)

                    ->get()

                    ->map(function($item) {

                        return $item->id;
                    })

                    ->toArray();
        }



        $trackers = Tracker::query()

                    ->when(count($redirectorIds) > 0, function($query) use($redirectorIds) {
                        $query->whereIn('redirector_id', $redirectorIds);
                    })

                    ->when($fromDate !== '' && $toDate !== '', function($query) use($fromDate, $toDate){

                        $query->whereBetween('created_at', [$fromDate, $toDate]);
                    })

                    ->get();

        $chart = [
            'labels' => [],
            'datasets' => [
                [
                    'label' => 'Tổng Click',
                    'borderColor' => 'rgb(255, 99, 132)',
                    'backgroundColor' => 'rgba(255, 99, 132, 0.5)',
                    'fill' => true,
                    'data' => []
                ]
            ]
        ];

        $pieCharts= [
            'labels' => [],
            'datasets' => [
                [
                    'label' => 'Thiết bị',
                    'backgroundColor' => [],
                    'fill' => true,
                    'data' => []
                ]
            ]
        ];

        $fromDay = (int) date('z', strtotime($fromDate));

        $toDay = (int) date('z', strtotime($toDate));

        $times = [];

        $pies = [];

        $type = ($toDay - $fromDay === 0 || $toDay - $fromDay === 1) ? 'H' : 'd/m/Y';


        foreach($trackers as $result) {

            $time = date($type, strtotime($result->created_at));
            $device = $result->device_type;

            $times[$time][] = $result;
            $pies[$device][] = $result;

        }

        if (count($times) > 0) {

            foreach ($times as $key => $c) {

                $time = $key . ($type === 'H' ? ' h' : '');

                $chart['labels'][] = $time;

                $chart['datasets'][0]['data'][] = count($c);
            }
        }
        if (count($pies) > 0) {

            $mapColor = [
                'Desktop' => '#ff6386',
                'Mobile' => '#1aa1e7',
                'Tablet' => '#ff9f50'
            ];

            foreach ($pies as $key => $c) {

                $pieCharts['labels'][] = $key;

                $color = isset($mapColor[$key]) ? $mapColor[$key] : 'green';

                $pieCharts['datasets'][0]['data'][] = count($c);
                $pieCharts['datasets'][0]['backgroundColor'][] = $color;
            }
        }

        return [
            'chart' => $chart,
            'pie_chart' => $pieCharts
        ];

    }

    public function createChart ($trackers,$redirectorIds, $from_date, $to_date) {

        $chart = [
            'labels' => [],
            'datasets' => [
                [
                    'label' => 'Redirect Click',
                    'borderColor' => 'rgb(255, 99, 132)',
                    'backgroundColor' => 'rgba(255, 99, 132, 0.5)',
                    'fill' => true,
                    'data' => []
                ],
                [
                    'label' => 'Mission Click',
                    'borderColor' => 'rgb(53, 162, 235)',
                    'backgroundColor' => 'rgba(53, 162, 235, 0.5)',
                    'fill' => true,
                    'data' => []
                ]
            ]
        ];

        $pieCharts= [
            'labels' => [],
            'datasets' => [
                [
                    'label' => 'Redirect device',
                    'backgroundColor' => 'rgba(255, 99, 132, 0.5)',
                    'data' => []
                ],
                [
                    'label' => 'Mission device',
                    'backgroundColor' => 'rgba(53, 162, 235, 0.5)',
                    'data' => []
                ]
            ]
        ];

        $fromDay = (int) date('z', strtotime($from_date));

        $toDay = (int) date('z', strtotime($to_date));

        $times = [];

        $pies = [];

        $type = ($toDay - $fromDay === 0 || $toDay - $fromDay === 1) ? 'H' : 'd/m/Y';

        foreach($trackers as $result) {

            $time = date($type, strtotime($result->created_at));

            if (auth()->user()->role === 'admin') {

                if ($result->redirector_id) {

                    $times[$time]['redirect'][] = $result;

                    $pies[$result->device_type]['redirect'][] = $result;
                }
                else {
                    $times[$time]['mission'][] = $result;
                    $pies[$result->device_type]['mission'][] = $result;
                }
            }
            else {
                if ($result->redirector_id && in_array($result->redirector_id, $redirectorIds)) {

                    $times[$time]['redirect'][] = $result;

                    $pies[$result->device_type]['redirect'][] = $result;
                }
                else if (!$result->redirector_id && $result->user_id && $result->user_id === auth()->user()->id) {
                    $times[$time]['mission'][] = $result;
                    $pies[$result->device_type]['mission'][] = $result;
                }
            }
        }

        if (count($times) > 0) {

            foreach ($times as $key => $c) {

                $time = $key . ($type === 'H' ? ' h' : '');

                $chart['labels'][] = $time;

                $redirectCount = isset($c['redirect']) ? count($c['redirect']) : 0;

                $missionCount = isset($c['mision']) ? count($c['mision']) : 0;

                $chart['datasets'][0]['data'][] = $redirectCount;

                $chart['datasets'][1]['data'][] = $missionCount;
            }
        }
        if (count($pies) > 0) {

            foreach ($pies as $key => $c) {

                $pieCharts['labels'][] = $key;

                $redirectCount = isset($c['redirect']) ? count($c['redirect']) : 0;

                $missionCount = isset($c['mision']) ? count($c['mision']) : 0;

                $pieCharts['datasets'][0]['data'][] = $redirectCount;

                $pieCharts['datasets'][1]['data'][] = $missionCount;
            }
        }

        return [
            'chart' => $chart,
            'pie_chart' => $pieCharts
        ];
    }

    public function topTraffic (Request $request) {

        $fromDate = $request->from_date ? $request->from_date : '';
        $toDate = $request->to_date ? $request->to_date : '';

        $redirectorQuery = 'select redirectors.*, (select count(*) from trackers where redirectors.id = trackers.redirector_id and (trackers.created_at BETWEEN "'. $fromDate .'" and "'. $toDate .'")) as total from redirectors';

        $keywordQuery = 'select keywords.*, (select count(*) from trackers where keywords.id = trackers.keyword_id and (trackers.created_at BETWEEN "'. $fromDate .'" and "'. $toDate .'")) as total from keywords';

        if (auth()->user()->role !== 'admin') {
            $keywordQuery .= ' where created_by = ' . auth()->user()->id;

            $redirectorQuery .= ' where created_by = ' . auth()->user()->id;
        }

        $keywordQuery .= ' order by total desc limit 50';
        $redirectorQuery .= ' order by total desc limit 50';

        $keywords = \DB::select($keywordQuery);
        $redirectors = \DB::select($redirectorQuery);

        return [
            'top_links' => $redirectors,
            'top_keywords' => $keywords
        ];
    }
}
