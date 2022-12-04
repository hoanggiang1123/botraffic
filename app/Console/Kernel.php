<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

use App\Console\Commands\ResetTraffic;
use App\Console\Commands\RemoveOldMission;

class Kernel extends ConsoleKernel
{

    protected $commands = [
        ResetTraffic::class,
        RemoveOldMission:: class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('reset:traffic')->dailyAt('00:00')->timezone('Asia/Ho_Chi_Minh');
        $schedule->command('remove:oldmission')->everyFiveMinutes();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
