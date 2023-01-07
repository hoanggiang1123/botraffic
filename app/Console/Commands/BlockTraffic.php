<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Carbon\Carbon;

use App\Models\Tracker;
use App\Models\BlockIp;

class BlockTraffic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'block:traffic';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Block bad traffic last month';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $count = Tracker::whereMonth(
            'created_at', '=', Carbon::now()->subMonth()->month
        )->count();

        $totalBlock = floor($count/2);

        $trackers = Tracker::whereMonth(
            'created_at', '=', Carbon::now()->subMonth()->month
        )->inRandomOrder()->limit($totalBlock)->get();


        $trackerChunks = $trackers->chunk(100);

        foreach ($trackerChunks as $chunks) {
            foreach ($chunks as $tracker) {
                BlockIp::firstOrCreate(['ip' => $tracker->ip, 'user_id' => $tracker->redirector_user_id]);
            }
            sleep(3);
        }

    }
}
