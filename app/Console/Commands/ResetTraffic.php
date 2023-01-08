<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Keyword;
use App\Models\LimitIp;
use App\Models\Mission;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\DB;

class ResetTraffic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reset:traffic';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset keywords traffic every day at 00:00';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Keyword::query()
            ->where('status', 1)
            ->where('approve', 1)
            ->get()
            ->each(function($item) {
                $item->update(['traffic_count' => $item->traffic, 'total_click_perday' => 0]);
            });

        DB::table('redirectors')->update(['total_click_perday' => 0]);

        Mission::where('status', 1)->delete();

        $resetArray = [2, 3, 4];
        $randomKeys = array_rand($resetArray);
        $reset = $resetArray[$randomKeys];
        DB::table('limit_ips')->where('reset', $reset)->delete();

        Log::info('reset traffic success');
    }
}
