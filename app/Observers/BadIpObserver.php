<?php

namespace App\Observers;

use App\Models\BadIp;
use App\Models\BlockIp;
use App\Models\Tracker;

use Illuminate\Support\Facades\Log;

class BadIpObserver
{
    public function updated(BadIp $badIp)
    {
        if ($badIp->count >= 4) {

            $trackers = Tracker::where('ip', $badIp->ip)->orderBy('created_at', 'desc')->limit(8)->get();

            if (count($trackers) === 8) {

                $check = [];

                foreach ($trackers as $tracker)
                {
                    $date = date('Y-m-d', strtotime($tracker->created_at));
                    $check[]= $date;
                }

                $check = array_values(array_unique($check));

                if (count($check) == 2) {

                    $current = new \DateTime($check[0]);
                    $previous = new \DateTime($check[1]);
                    $diff = $current->diff($previous);

                    if ($diff->days == 1) {
                        BlockIp::create(['ip' => $badIp->ip, 'user_id' => $badIp->user_id]);
                        $badIp->delete();
                    }
                }

            }

            $badIp->delete();
        }
    }
}
