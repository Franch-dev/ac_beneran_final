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
    protected $description = 'Delete expired service orders (status: spk_invoice_created) whose service date has passed';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $deleted = ServiceOrder::where('status', 'spk_invoice_created')
            ->where('service_date', '<', now()->toDateString())
            ->delete();

        $this->info("Cleaned {$deleted} expired spk_invoice_created orders.");

        return Command::SUCCESS;
    }
}
