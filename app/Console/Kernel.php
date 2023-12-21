<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('post:rankings')->everyFifteenMinutes();
        // $schedule->command('check:suspicious-activities')->daily();
        $schedule->command('check:suspicious-activities')->daily();
        $schedule->command('check:auto-disable')->daily();
        $schedule->command('check:delete-older-posts')->everyFifteenMinutes();
        $schedule->command('check:older-posts')->daily();

        // $schedule->command('inspire')->hourly();
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
