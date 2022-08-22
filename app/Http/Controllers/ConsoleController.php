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

        $mCoint = auth()->user()->point;
        $rCoint = auth()->user()->refer_point;
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

                    ->where('created_by', auth()->user()->id)

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

                    ->where('user_id', auth()->user()->id)

                    ->when($fromDate !== '' && $toDate !== '', function($query) use($fromDate, $toDate){

                        $query->whereBetween('created_at', [$fromDate, $toDate]);
                    })

                    ->get()

                    ->count();



        return [
            'm_coint' => $mCoint,
            'r_coint' => $rCoint,
            'balance' => $balance,
            'total_member' => $totalMember,
            'total_redirect' => $totalRedirect,
            'total_mission' => $totalMission
        ];

    }

    public function chart () {

    }
}
