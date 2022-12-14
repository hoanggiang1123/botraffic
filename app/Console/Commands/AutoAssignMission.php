<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\User;
use App\Models\Keyword;

class AutoAssignMission extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto:assign';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign mission every day at 06:00';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $users = User::where('assignable', 1)->inRandomOrder()->get();
        $keywords = Keyword::inRandomOrder()->get();

        $chunks = array_chunk($keywords, count($users));

        // foreach ($chunks as $chunk)
        // {
        //     foreach ($chunk)
        // }
    }
}
