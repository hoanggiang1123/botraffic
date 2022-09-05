<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Tracker as MainModel;

class TrackerController extends Controller
{
    protected $model;

    public function __construct(MainModel $mainModel)
    {
        $this->model = $mainModel;
    }

    public function index (Request $request) {

        return $this->model->listItems($request->all());
    }

    public function chart(Request $request) {

        $type = $request->type;
        $value = $request->value;

        $fromDate = $request->from_date;
        $toDate = $request->to_date;

        $label = $type === 'keyword_id' ? 'Từ khóa' : 'Link Redirect';


        if (!$type || !$value || !$fromDate || !$toDate) return response(['message' => 'Not Found'], 404);

        $items = $this->model->where($type, $value)
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->get();

        return $this->createChart($items, $fromDate, $toDate, $label);
    }

    public function createChart ($data, $from_date, $to_date, $label) {
        $chart = [
            'labels' => [],
            'datasets' => [
                [
                    'label' => $label,
                    'borderColor' => 'rgb(255, 99, 132)',
                    'backgroundColor' => 'rgba(255, 99, 132, 0.5)',
                    'fill' => true,
                    'data' => []
                ]
            ]
        ];
        $pie = [
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
        if (count($data)) {

            $fromDay = (int) date('z', strtotime($from_date));

            $toDay = (int) date('z', strtotime($to_date));

            $times = [];

            $pies = [];

            $type = ($toDay - $fromDay === 0 || $toDay - $fromDay === 1) ? 'H' : 'd/m/Y';

            $times = $devices = [];

            foreach ($data as $result) {

                $time = date($type, strtotime($result->created_at));
                $device = $result->device_type;

                $times[$time][] = $result;
                $devices[$device][] = $result;
            }

            $count = 0;

            if (count($times) > 0) {

                foreach ($times as $key => $c) {

                    $time = $key . ($type === 'H' ? ' h' : '');

                    $chart['labels'][] = $time;

                    $chart['datasets'][0]['data'][] = count($c);

                    $count+= count($c);
                }
            }

            $chart['datasets'][0]['label'] = $count . ' click ' . $label;

            if (count($devices) > 0) {

                $mapColor = [
                    'Desktop' => '#ff6386',
                    'Mobile' => '#1aa1e7',
                    'Tablet' => '#ff9f50'
                ];

                foreach ($devices as $key => $c) {


                    $pie['labels'][] = $key;

                    $color = isset($mapColor[$key]) ? $mapColor[$key] : 'green';

                    $pie['datasets'][0]['data'][] = count($c);
                    $pie['datasets'][0]['backgroundColor'][] = $color;
                }
            }


        }

        return [
            'line_chart' => $chart,
            'pie_chart' => $pie
        ];
    }
}
