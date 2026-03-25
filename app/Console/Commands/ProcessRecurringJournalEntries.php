<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Services\Accounting\RecurringJournalService;
use Illuminate\Console\Command;

class ProcessRecurringJournalEntries extends Command
{
    protected $signature = 'accounting:process-recurring
                            {--business= : Process only for a specific business ID}';

    protected $description = 'Post all due recurring journal entries';

    public function handle(RecurringJournalService $recurringJournalService): int
    {
        $business = null;

        if ($businessId = $this->option('business')) {
            $business = Business::find($businessId);

            if ($business === null) {
                $this->error("Business with ID {$businessId} not found.");

                return self::FAILURE;
            }
        }

        $count = $recurringJournalService->processAll($business);

        $this->info("Processed {$count} recurring journal ".($count === 1 ? 'entry' : 'entries').'.');

        return self::SUCCESS;
    }
}
