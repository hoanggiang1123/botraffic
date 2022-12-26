<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;


use App\Models\Tracker;
use App\Models\Redirector;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Log;

class UpdateRedirectorUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:redirector';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update redirector recursive';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->updateRedirector();
    }

    public function updateRedirector(&$batch = 1)
    {
        $trackers = Tracker::whereNotNull('redirector_id')->where('job', 0)->limit(1000)->get();

        if (count($trackers) > 0) {
            foreach ($trackers as $tracker) {
                $redirector = DB::table('redirectors')->where('id', $tracker->redirector_id)->first();
                if ($redirector) {
                    $tracker->update(['redirector_user_id' => $redirector->created_by, 'job' => 1]);
                }
            }
            Log::info('finish bash ' . $batch);

            $batch++;

            sleep(3);

            $this->updateRedirector($batch);

        } else {
            Log::info('finish');
        }
    }
}
