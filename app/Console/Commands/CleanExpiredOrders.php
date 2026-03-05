<?php

namespace App\Console\Commands;

use App\Models\ServiceOrder;
use Illuminate\Console\Command;

class CleanExpiredOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:clean-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete pending service orders that have passed their service date';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $deleted = ServiceOrder::where('status', 'pending')
            ->where('service_date', '<', now()->toDateString())
            ->delete();

        $this->info("Cleaned {$deleted} expired pending orders.");

        return Command::SUCCESS;
    }
}
