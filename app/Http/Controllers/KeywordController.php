<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Keyword as MainModel;
use App\Http\Requests\KeywordRequest as MainRequest;

use App\Models\User;


class KeywordController extends Controller
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

        $data = $request->all();

        if (!isset($data['created_by'])) $data['created_by'] = auth()->user()->id;

        if (isset($data['traffic'])) $data['traffic_count'] = $data['traffic'];

        if (isset($data['url'])) $data['domain'] = $this->getHostNameFromUrl($data['url']);

        if (auth()->user()->role !== 'admin' && isset($data['approve'])) {

            unset($data['approve']);

            // if (isset($data['traffic']) && (int) $data['traffic'] > 0) {

            //     $traffic = (int) $data['traffic'];

            //     if ($traffic > auth()->user()->point) {

            //         return response(['message' => 'Bạn không đủ BCOIN để chạy traffic, vui lòng nạp tiền'], 422);
            //     }
            //     else {

            //         $point = (auth()->user()->point) - $traffic;

            //         auth()->user()->update(['point' =>  $point]);
            //     }
            // }
        }

        $item = $this->model->create($data);

        if ($item) {
            return $item;
        }

        return response(['message' => 'Unprocess Entity'], 422);
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

    public function update (MainRequest $request, $id) {

        $item = $this->model->where('id', $id)->first();

        if ($item) {

            $data = $request->all();

            if (auth()->user()->role !== 'admin' && isset($data['approve'])) {

                unset($data['approve']);

                // if (isset($data['traffic'])) {

                //     $traffic = (int) $data['traffic'];

                //     $trafficAble = $item->traffic + auth()->user()->point;

                //     if ($traffic > $trafficAble) {
                //         return response(['message' => 'Bạn không đủ BCOIN để chạy traffic, vui lòng nạp tiền'], 422);
                //     }
                //     else {

                //         $point = $trafficAble - $traffic;

                //         User::where('id', auth()->user()->id)->update(['point' =>  $point]);
                //     }
                // }
            }

            $this->caculateTrafficCount($data, $item);

            $update = $item->update($data);

            if ($update) return $update;

            return response(['message' => 'Unprocess Entity'], 422);

        }

        return response(['message' => 'Not Found'], 404);
    }

    public function caculateTrafficCount(&$data, $item) {
        if ( isset( $data['traffic'] ) )
        {
            $traffic = (int) $data['traffic'];

            if ( $traffic <= 0 )
            {
                $data['traffic_count'] = 0;
                $data['traffic'] = 0;

            } else {

                $gapTraffic = $item->traffic - $traffic;

                if ($gapTraffic > 0) {

                    $trafficCount = $item->traffic_count - $gapTraffic;

                    if ($trafficCount <=0) {

                        $data['traffic_count'] = 0;
                    }
                    else {
                        $data['traffic_count'] = $trafficCount;
                    }

                } else {

                    $trafficCount = $item->traffic_count + abs( $gapTraffic );

                    $data['traffic_count'] = $trafficCount;
                }
            }

        }
    }


    public function destroy (Request $request) {

        $ids = $request->ids;

        $delete = $this->model->destroy($ids);

        if ($delete) return $delete;

        return response(['message' => 'Unprocess Entity'], 422);
    }

    public function show (Request $request, $id) {

        $keyword = $this->model->withCount('trackers')->where('id', $id)->first();

        if ($keyword) {
            return $keyword;
        }

        return \response(['message' => 'Not Found'], 404);
    }
}
