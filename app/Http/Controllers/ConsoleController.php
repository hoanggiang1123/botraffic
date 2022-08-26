<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Redirector;
use App\Models\Tracker;

class ConsoleController extends Controller
{
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


        $redirectorIds = Redirector::query()

                    ->when(auth()->user()->role !== 'admin', function($query){

                        $query->where('created_by', auth()->user()->id);
                    })

                    ->get()

                    ->map(function($item) {

                        return $item->id;
                    })

                    ->toArray();

        $trackers = Tracker::query()

                    ->when($fromDate !== '' && $toDate !== '', function($query) use($fromDate, $toDate){

                        $query->whereBetween('created_at', [$fromDate, $toDate]);
                    })

                    ->orderBy('created_at', 'asc')

                    ->get();


        $chartData = $this->createChart($trackers,$redirectorIds, $fromDate, $toDate);

        return [
            'm_coin' => $mCoin,
            'r_coin' => $rCoin,
            'redirect_coin' => $redirectCoin,
            'balance' => $balance,
            'total_member' => $totalMember,
            'total_redirect' => $chartData['total_redirect'],
            'total_mission' => $chartData['total_mission'],
            'chart' =>  $chartData['chart'],
            'pie_chart' => $chartData['pie_chart']
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
                    'backgroundColor' => [],
                    'data' => []
                ]
            ]
        ];

        $totalMission = $totalRedirect = 0;

        $fromDay = (int) date('z', strtotime($from_date));

        $toDay = (int) date('z', strtotime($to_date));

        $times = [];

        $pies = [];

        $type = ($toDay - $fromDay === 0 || $toDay - $fromDay === 1) ? 'H' : 'd/m/Y';

        foreach($trackers as $result) {

            $time = date($type, strtotime($result->created_at));

            if (\in_array($result->redirector_id, $redirectorIds)) {

                $times[$time]['redirect'][] = $result;
            }
            else {

                $times[$time]['mission'][] = $result;
            }

            $pies[$result->device_type][] = $result;


        }

        if (count($times) > 0) {

            foreach ($times as $key => $c) {

                $time = $key . ($type === 'H' ? ' h' : '');

                $chart['labels'][] = $time;

                $redirectCount = isset($c['redirect']) ? count($c['redirect']) : 0;

                $missionCount = isset($c['mision']) ? count($c['mision']) : 0;

                $chart['datasets'][0]['data'][] = $redirectCount;

                $chart['datasets'][1]['data'][] = $missionCount;

                $totalMission += $missionCount;

                $totalRedirect += $redirectCount;
            }
        }
        if (count($pies) > 0) {

            $colors = [
                'Desktop' => 'rgba(255, 99, 132, 0.2)',
                'Mobile' => 'rgba(54, 162, 235, 0.2)',
                'Tablet' => 'rgba(255, 206, 86, 0.2)',
            ];

            foreach ($pies as $key => $c) {

                $pieCharts['labels'][] = $key;

                $pieCharts['datasets'][0]['data'][] = count($c);
                $pieCharts['datasets'][0]['backgroundColor'][] = isset($colors[$key]) ? $colors[$key] : 'rgba(75, 192, 192, 0.2)';
            }
        }

        return [
            'chart' => $chart,
            'pie_chart' => $pieCharts,
            'total_redirect' => $totalRedirect,
            'total_mission' => $totalMission
        ];
    }

    public function chart (Request $request) {

        $fromDate = $request->from_date ? $request->from_date : '';
        $toDate = $request->to_date ? $request->to_date : '';

        $redirectors = Tracker::query()
                    ->when(auth()->user()->role !== 'admin', function($query){

                        $query->where('user_id', auth()->user()->id);
                    })
                    ->when($fromDate !== '' && $toDate !== '', function($query) use($fromDate, $toDate){

                        $query->whereBetween('created_at', [$fromDate, $toDate]);
                    });
    }
}
