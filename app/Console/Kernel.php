<?php

namespace App\Console;

use App\Models\ServiceOrder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Daily cleanup at 02:00: remove service orders whose masjid no longer exists
        $schedule->call(function () {
            ServiceOrder::whereDoesntHave('masjid')
                ->chunkById(100, function ($orders) {
                    foreach ($orders as $order) {
                        $order->serviceDetails()->delete();
                        $order->invoice()?->delete();
                        $order->delete();
                    }
                });
        })->dailyAt('02:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
