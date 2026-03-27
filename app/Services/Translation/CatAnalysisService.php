<?php

namespace App\Services\Translation;

use App\Domain\Sales\Enums\InvoiceStatus;
use App\Domain\Sales\Enums\InvoiceType;
use App\Domain\Translation\Enums\CatMatchBand;
use App\Domain\Translation\Enums\CatTool;
use App\Models\CatAnalysis;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectTarget;
use App\Services\Accounting\NumberSequenceService;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CatAnalysisService
{
    public function __construct(
        private NumberSequenceService $numberSequence,
    ) {}

    public function create(ProjectTarget $target, array $data): CatAnalysis
    {
        return DB::transaction(function () use ($target, $data) {
            $analysis = CatAnalysis::withoutGlobalScopes()->create([
                'business_id' => $target->project->business_id,
                'project_target_id' => $target->id,
                'name' => $data['name'],
                'tool' => $data['tool'] ?? CatTool::Manual->value,
            ]);

            foreach (CatMatchBand::cases() as $band) {
                $bandData = collect($data['bands'] ?? [])->firstWhere('band', $band->value);
                $analysis->bands()->create([
                    'band' => $band->value,
                    'words' => (int) ($bandData['words'] ?? 0),
                    'discount_percent' => $bandData['discount_percent'] ?? $band->defaultDiscountPercent(),
                ]);
            }

            return $analysis->load('bands');
        });
    }

    public function importFromFile(ProjectTarget $target, UploadedFile $file, string $tool): CatAnalysis
    {
        $bands = match ($tool) {
            CatTool::Trados->value => $this->parseTrados($file),
            CatTool::MemoQ->value => $this->parseMemoQ($file),
            CatTool::Phrase->value => $this->parsePhrase($file),
            default => throw new DomainException("Unsupported import tool: {$tool}"),
        };

        return $this->create($target, [
            'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'tool' => $tool,
            'bands' => $this->bandsFromMap($bands),
        ]);
    }

    public function delete(CatAnalysis $analysis): void
    {
        $analysis->bands()->delete();
        $analysis->delete();
    }

    public function applyToQuote(CatAnalysis $analysis, Project $project): Invoice
    {
        $analysis->loadMissing('bands');
        $target = $analysis->projectTarget;
        $project->loadMissing(['contact', 'targets.languagePair.sourceLanguage', 'targets.languagePair.targetLanguage', 'targets.serviceType']);

        if (! $project->canGenerateQuote()) {
            throw new DomainException('A quote has already been generated for this project.');
        }

        $business = $project->business;

        return DB::transaction(function () use ($analysis, $target, $project, $business) {
            $quote = Invoice::withoutGlobalScopes()->create([
                'business_id' => $business->id,
                'contact_id' => $project->contact_id,
                'type' => InvoiceType::QUOTE,
                'number' => $this->numberSequence->getNext($business, 'quote'),
                'date' => now()->format('Y-m-d'),
                'due_date' => $project->deadline?->format('Y-m-d'),
                'reference' => $project->reference,
                'status' => InvoiceStatus::DRAFT,
                'currency_code' => $business->currency_code,
                'subtotal' => 0,
                'tax_amount' => 0,
                'total' => 0,
                'amount_paid' => 0,
                'balance_due' => 0,
                'notes' => $project->notes,
            ]);

            $sortOrder = 0;
            $subtotal = '0.00';

            // Lines for non-analysed targets use raw word count
            foreach ($project->targets as $t) {
                if ($t->id === $target->id) {
                    continue;
                }

                $pair = $t->languagePair;
                $serviceType = $t->serviceType ?? $project->serviceType;
                $description = sprintf('%s → %s (%s)', $pair->sourceLanguage->name, $pair->targetLanguage->name, $serviceType?->name ?? 'Translation');
                $unitPrice = $t->unit_price ?? '0';
                $wordCount = $t->word_count ?? 1;
                $lineTotal = bcmul((string) $wordCount, (string) $unitPrice, 2);
                $subtotal = bcadd($subtotal, $lineTotal, 2);

                $quote->lines()->create([
                    'description' => $description,
                    'quantity' => $wordCount,
                    'unit_price' => $unitPrice,
                    'discount_percent' => 0,
                    'tax_amount' => 0,
                    'line_total' => $lineTotal,
                    'sort_order' => $sortOrder++,
                ]);
            }

            // Lines for the analysed target — one line per match band
            $pair = $target->languagePair;
            $serviceType = $target->serviceType ?? $project->serviceType;
            $unitPrice = $target->unit_price ?? '0';

            foreach ($analysis->bands as $band) {
                if ($band->words === 0) {
                    continue;
                }

                $effectiveWords = $band->effectiveWords();
                $lineTotal = bcmul($effectiveWords, (string) $unitPrice, 2);
                $subtotal = bcadd($subtotal, $lineTotal, 2);

                $description = sprintf(
                    '%s → %s (%s) — %s',
                    $pair->sourceLanguage->name,
                    $pair->targetLanguage->name,
                    $serviceType?->name ?? 'Translation',
                    $band->band->label()
                );

                $quote->lines()->create([
                    'description' => $description,
                    'quantity' => $band->words,
                    'unit_price' => $unitPrice,
                    'discount_percent' => $band->discount_percent,
                    'tax_amount' => 0,
                    'line_total' => $lineTotal,
                    'sort_order' => $sortOrder++,
                ]);
            }

            $quote->update([
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'balance_due' => $subtotal,
            ]);

            $project->update(['quote_id' => $quote->id]);

            return $quote;
        });
    }

    /** @return Collection<int, Invoice> */
    public function applyToPurchaseOrders(CatAnalysis $analysis, Project $project): Collection
    {
        $analysis->loadMissing('bands');
        $target = $analysis->projectTarget;
        $target->loadMissing(['assignments.contact', 'languagePair.sourceLanguage', 'languagePair.targetLanguage', 'serviceType']);

        $business = $project->business;
        $orders = collect();
        $weightedWords = (int) ceil((float) $analysis->weightedWords());

        DB::transaction(function () use ($analysis, $target, $project, $business, $orders, $weightedWords) {
            foreach ($target->assignments as $assignment) {
                if ($assignment->purchase_order_id !== null) {
                    continue;
                }

                $pair = $target->languagePair;
                $serviceType = $target->serviceType ?? $project->serviceType;
                $description = sprintf(
                    '%s → %s (%s) — %s [CAT: %s]',
                    $pair->sourceLanguage->name,
                    $pair->targetLanguage->name,
                    $serviceType?->name ?? 'Translation',
                    $assignment->role->label(),
                    $analysis->name
                );

                $rate = $assignment->rate ?? '0';
                $lineTotal = bcmul((string) $weightedWords, (string) $rate, 2);

                $po = Invoice::withoutGlobalScopes()->create([
                    'business_id' => $business->id,
                    'contact_id' => $assignment->contact_id,
                    'type' => InvoiceType::PURCHASE_ORDER,
                    'number' => $this->numberSequence->getNext($business, 'purchase_order'),
                    'date' => now()->format('Y-m-d'),
                    'due_date' => $project->deadline?->format('Y-m-d'),
                    'reference' => $project->reference,
                    'status' => InvoiceStatus::DRAFT,
                    'currency_code' => $business->currency_code,
                    'subtotal' => $lineTotal,
                    'tax_amount' => 0,
                    'total' => $lineTotal,
                    'amount_paid' => 0,
                    'balance_due' => $lineTotal,
                    'notes' => $project->notes,
                ]);

                $po->lines()->create([
                    'description' => $description,
                    'quantity' => $weightedWords,
                    'unit_price' => $rate,
                    'discount_percent' => 0,
                    'tax_amount' => 0,
                    'line_total' => $lineTotal,
                    'sort_order' => 0,
                ]);

                $assignment->update(['purchase_order_id' => $po->id]);
                $orders->push($po);
            }
        });

        return $orders;
    }

    // ── Import parsers ────────────────────────────────────────────

    /**
     * Parse SDL Trados Studio analysis CSV export.
     * Expects columns: File, Repetitions, 100%, 99%-95%, 94%-85%, 84%-75%, 74%-50%, New, Total
     * or Context TM, Exact match, 95%-99%, 85%-94%, 75%-84%, 50%-74%, New words, Repetitions
     *
     * @return array<string, int>
     */
    private function parseTrados(UploadedFile $file): array
    {
        $rows = $this->readCsv($file);

        if (empty($rows)) {
            throw new DomainException('The uploaded file appears to be empty.');
        }

        $totals = $this->sumNumericRows($rows);
        $header = array_map('strtolower', array_map('trim', array_keys($rows[0])));

        return $this->mapTradosColumns($totals, $header);
    }

    /**
     * Parse memoQ analysis CSV export.
     * Categories row has: Repetitions, Exact match (100%), 95-99%, 85-94%, 75-84%, 50-74%, No match, Total
     *
     * @return array<string, int>
     */
    private function parseMemoQ(UploadedFile $file): array
    {
        $rows = $this->readCsv($file);

        if (empty($rows)) {
            throw new DomainException('The uploaded file appears to be empty.');
        }

        // memoQ CSV: find the "Words" row
        $wordRow = null;
        foreach ($rows as $row) {
            $first = strtolower(trim(array_values($row)[0] ?? ''));
            if ($first === 'words' || $first === 'total words') {
                $wordRow = $row;
                break;
            }
        }

        if ($wordRow === null) {
            // Fall back to summing all numeric rows
            $wordRow = $this->sumNumericRows($rows);
        }

        $header = array_map('strtolower', array_map('trim', array_keys($wordRow)));
        $values = array_values($wordRow);

        return $this->mapMemoQColumns($header, $values);
    }

    /**
     * Parse Phrase (Memsource) analysis CSV export.
     * Columns: File, 101%, 100%, 95%-99%, 85%-94%, 75%-84%, 50%-74%, 0%-49%, Repetitions, ...
     *
     * @return array<string, int>
     */
    private function parsePhrase(UploadedFile $file): array
    {
        $rows = $this->readCsv($file);

        if (empty($rows)) {
            throw new DomainException('The uploaded file appears to be empty.');
        }

        $totals = $this->sumNumericRows($rows);
        $header = array_map('strtolower', array_map('trim', array_keys($rows[0])));

        return $this->mapPhraseColumns($totals, $header);
    }

    // ── Column mapping helpers ────────────────────────────────────

    /**
     * @param  array<string, int>  $totals
     * @param  list<string>  $header
     * @return array<string, int>
     */
    private function mapTradosColumns(array $totals, array $header): array
    {
        $values = array_values($totals);

        $map = [];
        foreach ($header as $i => $col) {
            $val = (int) ($values[$i] ?? 0);
            if (str_contains($col, 'context') || $col === 'context tm' || $col === '101%') {
                $map[CatMatchBand::ContextMatch->value] = ($map[CatMatchBand::ContextMatch->value] ?? 0) + $val;
            } elseif (str_contains($col, 'repetition')) {
                $map[CatMatchBand::Repetitions->value] = ($map[CatMatchBand::Repetitions->value] ?? 0) + $val;
            } elseif ($col === '100%' || str_contains($col, 'exact match') || str_contains($col, 'exact (100%)')) {
                $map[CatMatchBand::ExactMatch->value] = ($map[CatMatchBand::ExactMatch->value] ?? 0) + $val;
            } elseif (str_contains($col, '95') || str_contains($col, '99%-95%') || str_contains($col, '95%-99%')) {
                $map[CatMatchBand::Fuzzy95_99->value] = ($map[CatMatchBand::Fuzzy95_99->value] ?? 0) + $val;
            } elseif (str_contains($col, '85') || str_contains($col, '94%-85%') || str_contains($col, '85%-94%')) {
                $map[CatMatchBand::Fuzzy85_94->value] = ($map[CatMatchBand::Fuzzy85_94->value] ?? 0) + $val;
            } elseif (str_contains($col, '75') || str_contains($col, '84%-75%') || str_contains($col, '75%-84%')) {
                $map[CatMatchBand::Fuzzy75_84->value] = ($map[CatMatchBand::Fuzzy75_84->value] ?? 0) + $val;
            } elseif (str_contains($col, '50') || str_contains($col, '74%-50%') || str_contains($col, '50%-74%')) {
                $map[CatMatchBand::Fuzzy50_74->value] = ($map[CatMatchBand::Fuzzy50_74->value] ?? 0) + $val;
            } elseif (str_contains($col, 'new') || str_contains($col, '0%')) {
                $map[CatMatchBand::NoMatch->value] = ($map[CatMatchBand::NoMatch->value] ?? 0) + $val;
            }
        }

        return $map;
    }

    /**
     * @param  list<string>  $header
     * @param  list<int>  $values
     * @return array<string, int>
     */
    private function mapMemoQColumns(array $header, array $values): array
    {
        $map = [];
        foreach ($header as $i => $col) {
            $val = (int) ($values[$i] ?? 0);
            if (str_contains($col, 'repetition')) {
                $map[CatMatchBand::Repetitions->value] = ($map[CatMatchBand::Repetitions->value] ?? 0) + $val;
            } elseif (str_contains($col, 'exact') || str_contains($col, '100%')) {
                $map[CatMatchBand::ExactMatch->value] = ($map[CatMatchBand::ExactMatch->value] ?? 0) + $val;
            } elseif (str_contains($col, '95')) {
                $map[CatMatchBand::Fuzzy95_99->value] = ($map[CatMatchBand::Fuzzy95_99->value] ?? 0) + $val;
            } elseif (str_contains($col, '85')) {
                $map[CatMatchBand::Fuzzy85_94->value] = ($map[CatMatchBand::Fuzzy85_94->value] ?? 0) + $val;
            } elseif (str_contains($col, '75')) {
                $map[CatMatchBand::Fuzzy75_84->value] = ($map[CatMatchBand::Fuzzy75_84->value] ?? 0) + $val;
            } elseif (str_contains($col, '50')) {
                $map[CatMatchBand::Fuzzy50_74->value] = ($map[CatMatchBand::Fuzzy50_74->value] ?? 0) + $val;
            } elseif (str_contains($col, 'no match') || str_contains($col, 'new')) {
                $map[CatMatchBand::NoMatch->value] = ($map[CatMatchBand::NoMatch->value] ?? 0) + $val;
            }
        }

        return $map;
    }

    /**
     * @param  array<string, int>  $totals
     * @param  list<string>  $header
     * @return array<string, int>
     */
    private function mapPhraseColumns(array $totals, array $header): array
    {
        $values = array_values($totals);

        $map = [];
        foreach ($header as $i => $col) {
            $val = (int) ($values[$i] ?? 0);
            if ($col === '101%' || str_contains($col, 'context') || str_contains($col, 'in-context')) {
                $map[CatMatchBand::ContextMatch->value] = ($map[CatMatchBand::ContextMatch->value] ?? 0) + $val;
            } elseif ($col === '100%' || str_contains($col, 'exact')) {
                $map[CatMatchBand::ExactMatch->value] = ($map[CatMatchBand::ExactMatch->value] ?? 0) + $val;
            } elseif (str_contains($col, '95') || str_contains($col, '99')) {
                $map[CatMatchBand::Fuzzy95_99->value] = ($map[CatMatchBand::Fuzzy95_99->value] ?? 0) + $val;
            } elseif (str_contains($col, '85') || str_contains($col, '94')) {
                $map[CatMatchBand::Fuzzy85_94->value] = ($map[CatMatchBand::Fuzzy85_94->value] ?? 0) + $val;
            } elseif (str_contains($col, '75') || str_contains($col, '84')) {
                $map[CatMatchBand::Fuzzy75_84->value] = ($map[CatMatchBand::Fuzzy75_84->value] ?? 0) + $val;
            } elseif (str_contains($col, '50') || str_contains($col, '74')) {
                $map[CatMatchBand::Fuzzy50_74->value] = ($map[CatMatchBand::Fuzzy50_74->value] ?? 0) + $val;
            } elseif ($col === '0%-49%' || str_contains($col, '0%') || str_contains($col, 'new') || str_contains($col, '49')) {
                $map[CatMatchBand::NoMatch->value] = ($map[CatMatchBand::NoMatch->value] ?? 0) + $val;
            } elseif (str_contains($col, 'repetition')) {
                $map[CatMatchBand::Repetitions->value] = ($map[CatMatchBand::Repetitions->value] ?? 0) + $val;
            }
        }

        return $map;
    }

    // ── CSV utilities ────────────────────────────────────────────

    /**
     * @return list<array<string, string>>
     */
    private function readCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if (! $handle) {
            throw new DomainException('Could not read the uploaded file.');
        }

        $rows = [];
        $header = null;

        while (($line = fgetcsv($handle, 0, ',')) !== false) {
            if ($header === null) {
                $header = $line;

                continue;
            }

            if (count($line) !== count($header)) {
                continue;
            }

            $rows[] = array_combine($header, $line);
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Sum all numeric values per column across all rows.
     *
     * @param  list<array<string, string>>  $rows
     * @return array<string, int>
     */
    private function sumNumericRows(array $rows): array
    {
        $totals = [];
        foreach ($rows as $row) {
            foreach ($row as $key => $value) {
                $trimmed = trim((string) $value);
                if (is_numeric($trimmed)) {
                    $totals[$key] = ($totals[$key] ?? 0) + (int) $trimmed;
                }
            }
        }

        return $totals;
    }

    /**
     * Convert a band word map to the bands array format expected by create().
     *
     * @param  array<string, int>  $bandMap  Keys are CatMatchBand enum values, values are word counts
     * @return list<array{band: string, words: int, discount_percent: int}>
     */
    private function bandsFromMap(array $bandMap): array
    {
        return collect(CatMatchBand::cases())->map(fn ($band) => [
            'band' => $band->value,
            'words' => $bandMap[$band->value] ?? 0,
            'discount_percent' => $band->defaultDiscountPercent(),
        ])->all();
    }
}
