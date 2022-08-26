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
            'chart' =>  $chartData['chart']
        ];

    }

    public function createChart ($trackers,$redirectorIds, $from_date, $to_date) {

        $charts = [];

        $chart = [
            'labels' => [],
            'datasets' => [
                [
                    'label' => 'Redirect Click',
                    'data' => []
                ],
                [
                    'label' => 'Mission Click',
                    'data' => []
                ]
            ]
        ];

        $totalMission = $totalRedirect = 0;

        $fromDay = (int) date('z', strtotime($from_date));

        $toDay = (int) date('z', strtotime($to_date));

        $times = [];

        $type = ($toDay - $fromDay === 0 || $toDay - $fromDay === 1) ? 'H' : 'd/m/Y';

        foreach($results as $result) {

            $time = date($type, strtotime($result->created_at));

            $redirect = $mission = [];

            if (\in_array($result->redirector_id, $redirectorIds)) {

                $times[$time]['redirect'][] = $result;
            }
            else {

                $times[$time]['mission'][] = $result;
            }


        }

        if (count($times) > 0) {

            foreach ($times as $key => $items) {

                $redirectCount = isset($item['redirect']) ? count($item['redirect']) : 0;

                $missionCount = isset($item['mission']) ? count($item['mission']) : 0;

                $charts[] = ['Time' => $key . ($type === 'H' ? ' h' : ''), 'redirect' => count($items), 'mission' => $missionCount];
            }



            foreach ($charts as $c) {

                $chart['labels'][] = $c['Time'];

                $chart['datasets'][0]['data'][] = $c['redirect'];

                $chart['datasets'][1]['data'][] = $c['mission'];

                $totalMission += (int) $c['mission'];

                $totalRedirect += (int) $c['redirect'];
            }
        }

        return [
            'chart' => $chart,
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
