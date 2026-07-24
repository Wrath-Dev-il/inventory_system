<?php

namespace App\Console\Commands;

use App\Services\SalesListingService;
use Illuminate\Console\Command;

class BackfillSalesListings extends Command
{
    protected $signature = 'sales:backfill-listings';

    protected $description = 'Create Sales Listing records for existing Confirmed Sales Orders that lack one';

    public function handle(SalesListingService $service)
    {
        $count = $service->backfillExistingOrders();

        if ($count === 0) {
            $this->info('All Confirmed Sales Orders already have listings.');
            return;
        }

        $this->info("Created {$count} Sales Listing(s) from existing Sales Orders.");
    }
}
