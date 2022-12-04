<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Mission;
use App\Models\Keyword;

class RemoveOldMission extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:oldmission';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove mission that lasted than 5 minutes';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $date = new DateTime;

        $date->modify('-5 minutes');

        $formatted_date = $date->format('Y-m-d H:i:s');

        Mission::where('status', 0)->where('updated_at', '>', $formatted_date)->get()->each(function($item) {
            Keyword::where('id', $item->keyword_id)->increment('traffic_count');
            $item->delete();
        });

    }
}
