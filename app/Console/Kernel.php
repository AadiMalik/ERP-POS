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
        // $schedule->command('inspire')->hourly();

        $schedule->command('fixed-assets:post-depreciation')->dailyAt('00:15')->withoutOverlapping();
        $schedule->command('subscriptions:process-lifecycle')->dailyAt('01:00');
        $schedule->command('accounting-periods:process')->dailyAt('02:00')->withoutOverlapping();
        $schedule->command('notifications:check-alerts')->hourly();
        $schedule->command('recurring-transactions:process')->hourly();
        $schedule->command('backups:auto-run')->hourly()->withoutOverlapping();
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
