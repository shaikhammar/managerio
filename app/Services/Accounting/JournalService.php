<?php

namespace App\Services\Accounting;

use App\Events\JournalEntryPosted;
use App\Models\Business;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

class JournalService
{
    public function __construct(
        private NumberSequenceService $numberSequence,
    ) {}

    /**
     * Create and immediately post a journal entry.
     * Used by other services (Invoice, Payment, etc.)
     */
    public function createAndPost(
        Business $business,
        Carbon|string $date,
        array $lines,
        string $description,
        string $sourceType = 'manual',
        ?int $sourceId = null,
    ): JournalEntry {
        return DB::transaction(function () use ($business, $date, $lines, $description, $sourceType, $sourceId) {
            $this->validateLines($lines);

            $date = $date instanceof Carbon ? $date : Carbon::parse($date);

            $entry = JournalEntry::withoutGlobalScopes()->create([
                'business_id' => $business->id,
                'entry_number' => $this->numberSequence->getNext($business, 'journal_entry'),
                'date' => $date,
                'description' => $description,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'is_posted' => true,
                'posted_at' => now(),
                'created_by' => auth()->id(),
            ]);

            foreach ($lines as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $line['account_id'],
                    'contact_id' => $line['contact_id'] ?? null,
                    'description' => $line['description'] ?? null,
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'tax_code_id' => $line['tax_code_id'] ?? null,
                ]);
            }

            $loaded = $entry->load('lines');
            JournalEntryPosted::dispatch($loaded);

            return $loaded;
        });
    }

    /**
     * Create a draft journal entry (not yet posted).
     */
    public function createDraft(
        Business $business,
        Carbon|string $date,
        array $lines,
        string $description,
        ?string $reference = null,
    ): JournalEntry {
        return DB::transaction(function () use ($business, $date, $lines, $description, $reference) {
            $this->validateLines($lines);

            $date = $date instanceof Carbon ? $date : Carbon::parse($date);

            $entry = JournalEntry::withoutGlobalScopes()->create([
                'business_id' => $business->id,
                'entry_number' => $this->numberSequence->getNext($business, 'journal_entry'),
                'date' => $date,
                'description' => $description,
                'reference' => $reference,
                'source_type' => 'manual',
                'is_posted' => false,
                'created_by' => auth()->id(),
            ]);

            foreach ($lines as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $line['account_id'],
                    'contact_id' => $line['contact_id'] ?? null,
                    'description' => $line['description'] ?? null,
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'tax_code_id' => $line['tax_code_id'] ?? null,
                ]);
            }

            return $entry->load('lines');
        });
    }

    /**
     * Post a draft journal entry.
     */
    public function post(JournalEntry $entry): JournalEntry
    {
        if ($entry->is_posted) {
            throw new DomainException('This journal entry is already posted.');
        }

        if (! $entry->isBalanced()) {
            throw new DomainException('Cannot post an unbalanced journal entry.');
        }

        $entry->update([
            'is_posted' => true,
            'posted_at' => now(),
        ]);

        $fresh = $entry->fresh();
        JournalEntryPosted::dispatch($fresh);

        return $fresh;
    }

    /**
     * Reverse a posted journal entry by creating a counter-entry.
     */
    public function reverse(JournalEntry $entry, string $reason): JournalEntry
    {
        if (! $entry->is_posted) {
            throw new DomainException('Cannot reverse an unposted entry.');
        }

        if ($entry->hasBeenReversed()) {
            throw new DomainException('This entry has already been reversed.');
        }

        return DB::transaction(function () use ($entry, $reason) {
            $reversalLines = $entry->lines->map(fn ($line) => [
                'account_id' => $line->account_id,
                'contact_id' => $line->contact_id,
                'debit' => $line->credit,    // swap debit ↔ credit
                'credit' => $line->debit,
                'description' => "Reversal: {$line->description}",
                'tax_code_id' => $line->tax_code_id,
            ])->toArray();

            $reversal = JournalEntry::withoutGlobalScopes()->create([
                'business_id' => $entry->business_id,
                'entry_number' => $this->numberSequence->getNext($entry->business, 'journal_entry'),
                'date' => now(),
                'description' => "Reversal of {$entry->entry_number}: {$reason}",
                'source_type' => $entry->source_type,
                'source_id' => $entry->source_id,
                'is_posted' => true,
                'posted_at' => now(),
                'reversal_of_id' => $entry->id,
                'created_by' => auth()->id(),
            ]);

            foreach ($reversalLines as $line) {
                JournalEntryLine::create(array_merge($line, [
                    'journal_entry_id' => $reversal->id,
                ]));
            }

            return $reversal->load('lines');
        });
    }

    /**
     * Validate that journal entry lines are balanced.
     */
    private function validateLines(array $lines): void
    {
        if (count($lines) < 2) {
            throw new DomainException('A journal entry must have at least 2 lines.');
        }

        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($lines as $line) {
            $debit = $line['debit'] ?? 0;
            $credit = $line['credit'] ?? 0;

            if ($debit < 0 || $credit < 0) {
                throw new DomainException('Debit and credit amounts must be non-negative.');
            }

            if ($debit > 0 && $credit > 0) {
                throw new DomainException('A journal line cannot have both debit and credit amounts.');
            }

            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        if (bccomp((string) $totalDebit, (string) $totalCredit, 2) !== 0) {
            throw new DomainException(
                "Journal entry is unbalanced. Debits ({$totalDebit}) ≠ Credits ({$totalCredit})"
            );
        }
    }
}
