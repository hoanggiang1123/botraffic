<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Keyword;
use App\Models\LimitIp;
use App\Models\Mission;
use Illuminate\Support\Facades\Log;

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
                $item->update(['traffic_count' => $item->traffic]);
            });

        LimitIp::truncate();
        Mission::where('status', 1)->delete();
        Log::info('reset traffic success');
    }
}
