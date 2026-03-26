<?php

namespace App\Services\Accounting;

use App\Models\Business;
use App\Models\RecurringJournalEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RecurringJournalService
{
    public function __construct(
        private JournalService $journalService,
    ) {}

    /**
     * Create a new recurring journal entry template.
     *
     * @param  array{name: string, description?: string, frequency: string, start_date: string, end_date?: string, day_of_month: int, template_lines: array<int, array{account_id: int, description?: string, debit: float, credit: float}>}  $data
     */
    public function create(Business $business, array $data): RecurringJournalEntry
    {
        $startDate = Carbon::parse($data['start_date']);
        $nextRunDate = $this->calculateNextRunDate($startDate, (int) $data['day_of_month']);

        return RecurringJournalEntry::withoutGlobalScopes()->create([
            'business_id' => $business->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'frequency' => $data['frequency'],
            'start_date' => $startDate,
            'end_date' => isset($data['end_date']) ? Carbon::parse($data['end_date']) : null,
            'next_run_date' => $nextRunDate,
            'day_of_month' => $data['day_of_month'],
            'is_active' => true,
            'template_lines' => $data['template_lines'],
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Update an existing recurring journal entry template.
     *
     * @param  array{name?: string, description?: string, frequency?: string, start_date?: string, end_date?: string, day_of_month?: int, template_lines?: array<int, mixed>, is_active?: bool}  $data
     */
    public function update(RecurringJournalEntry $recurringEntry, array $data): RecurringJournalEntry
    {
        if (isset($data['start_date']) || isset($data['day_of_month'])) {
            $startDate = Carbon::parse($data['start_date'] ?? $recurringEntry->start_date);
            $dayOfMonth = (int) ($data['day_of_month'] ?? $recurringEntry->day_of_month);
            $data['next_run_date'] = $this->calculateNextRunDate($startDate, $dayOfMonth);
        }

        $recurringEntry->update($data);

        return $recurringEntry->fresh();
    }

    /**
     * Process all due recurring entries across all (or a specific) business.
     */
    public function processAll(?Business $business = null): int
    {
        $query = RecurringJournalEntry::withoutGlobalScopes()->due();

        if ($business !== null) {
            $query->where('business_id', $business->id);
        }

        $processed = 0;

        $query->with('business')->each(function (RecurringJournalEntry $entry) use (&$processed) {
            if ($entry->isExpired()) {
                $entry->update(['is_active' => false]);

                return;
            }

            $this->processSingle($entry);
            $processed++;
        });

        return $processed;
    }

    /**
     * Process a single recurring entry: post a journal entry and advance the schedule.
     */
    public function processSingle(RecurringJournalEntry $recurringEntry): void
    {
        DB::transaction(function () use ($recurringEntry) {
            $this->journalService->createAndPost(
                business: $recurringEntry->business,
                date: $recurringEntry->next_run_date,
                lines: $recurringEntry->template_lines,
                description: $recurringEntry->name,
                sourceType: 'recurring',
                sourceId: $recurringEntry->id,
            );

            $nextRunDate = $this->advanceNextRunDate($recurringEntry);

            $recurringEntry->update([
                'last_run_at' => now(),
                'next_run_date' => $nextRunDate,
                'is_active' => $recurringEntry->end_date === null || $nextRunDate->lte($recurringEntry->end_date),
            ]);
        });
    }

    private function calculateNextRunDate(Carbon $from, int $dayOfMonth): Carbon
    {
        $date = $from->copy()->startOfMonth()->addDays($dayOfMonth - 1);

        if ($date->lt($from)) {
            $date->addMonth();
        }

        return $date;
    }

    private function advanceNextRunDate(RecurringJournalEntry $recurringEntry): Carbon
    {
        $current = Carbon::parse($recurringEntry->next_run_date);
        $next = $current->copy()->addMonths($recurringEntry->frequency->monthsToAdd());

        return $next->setDay(min($recurringEntry->day_of_month, $next->daysInMonth));
    }
}
