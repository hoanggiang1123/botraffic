<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\User;
use App\Models\Keyword;

use Illuminate\Support\Facades\Log;

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

        $userKeys = $this->removeDup($keywords);

        Log::info($userKeys);
    }

    public function removeDup ($keywords, $dupKeywords = [], $userKeys = [], $count = 1) {


        $users = User::where('assignable', 0)->inRandomOrder()->get();

        foreach ($keywords as $key => $keyword)
        {
            $index = $key % count($users);

            $id = $users[$index]->id;

            if (isset($userKeys[$id][$keyword->url]))
            {
                if ($count >= 10) {

                    $userKeys[$id][$keyword->url][] = $keyword->id;
                }
                else {

                    $dupKeywords[] = $keyword;
                }
            }
            else {

                $userKeys[$id][$keyword->url][] = $keyword->id;
            }


        }

        if (count($dupKeywords) > 0) {
            $count++;
            $this->removeDup($dupKeywords, [], $userKeys, $count);
        }

        return [$userKeys, $dupKeywords];
    }
}
