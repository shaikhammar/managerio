<?php

namespace App\Services\Accounting;

use App\Domain\Accounting\Enums\AssetStatus;
use App\Domain\Accounting\Enums\DepreciationMethod;
use App\Models\Business;
use App\Models\DepreciationEntry;
use App\Models\FixedAsset;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FixedAssetService
{
    public function __construct(
        private JournalService $journalService,
    ) {}

    /**
     * Register a new fixed asset.
     *
     * @param  array{asset_account_id: int, accumulated_depreciation_account_id: int, depreciation_expense_account_id: int, name: string, description?: string, asset_tag?: string, purchase_date: string, purchase_cost: float, salvage_value?: float, useful_life_months: int, depreciation_method: string, notes?: string}  $data
     */
    public function create(Business $business, array $data): FixedAsset
    {
        return FixedAsset::withoutGlobalScopes()->create([
            'business_id' => $business->id,
            'asset_account_id' => $data['asset_account_id'],
            'accumulated_depreciation_account_id' => $data['accumulated_depreciation_account_id'],
            'depreciation_expense_account_id' => $data['depreciation_expense_account_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'asset_tag' => $data['asset_tag'] ?? null,
            'purchase_date' => Carbon::parse($data['purchase_date']),
            'purchase_cost' => $data['purchase_cost'],
            'salvage_value' => $data['salvage_value'] ?? 0,
            'useful_life_months' => $data['useful_life_months'],
            'depreciation_method' => $data['depreciation_method'],
            'status' => AssetStatus::Active,
            'notes' => $data['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Update an existing fixed asset.
     *
     * @param  array{asset_account_id?: int, accumulated_depreciation_account_id?: int, depreciation_expense_account_id?: int, name?: string, description?: string, asset_tag?: string, purchase_date?: string, purchase_cost?: float, salvage_value?: float, useful_life_months?: int, depreciation_method?: string, notes?: string}  $data
     */
    public function update(FixedAsset $asset, array $data): FixedAsset
    {
        $asset->update($data);

        return $asset->fresh();
    }

    /**
     * Retire an asset (taken out of service but not sold).
     */
    public function retire(FixedAsset $asset): FixedAsset
    {
        if ($asset->status !== AssetStatus::Active) {
            throw new DomainException('Only active assets can be retired.');
        }

        $asset->update(['status' => AssetStatus::Retired]);

        return $asset->fresh();
    }

    /**
     * Dispose of an asset (sale or write-off) and post the gain/loss journal entry.
     *
     * @param  array{disposal_date: string, disposal_proceeds: float, bank_account_id?: int}  $data
     */
    public function dispose(FixedAsset $asset, Business $business, array $data): FixedAsset
    {
        if ($asset->status === AssetStatus::Disposed) {
            throw new DomainException('Asset has already been disposed.');
        }

        return DB::transaction(function () use ($asset, $business, $data) {
            $asset->load(['depreciationEntries']);

            $disposalDate = Carbon::parse($data['disposal_date']);
            $proceeds = (string) ($data['disposal_proceeds'] ?? 0);
            $cost = (string) $asset->purchase_cost;
            $accumulated = $asset->accumulatedDepreciation();
            $bookValue = bcsub($cost, $accumulated, 2);
            $gainLoss = bcsub($proceeds, $bookValue, 2);

            // Build journal entry lines:
            // DR Accumulated Depreciation (remove contra-asset)
            // CR Asset Account (remove cost)
            // DR/CR Bank (proceeds if any)
            // CR/DR Gain/Loss on Disposal
            $lines = [];

            // Remove accumulated depreciation (DR)
            if (bccomp($accumulated, '0', 2) > 0) {
                $lines[] = [
                    'account_id' => $asset->accumulated_depreciation_account_id,
                    'description' => "Disposal of {$asset->name} — remove accumulated depreciation",
                    'debit' => (float) $accumulated,
                    'credit' => 0,
                ];
            }

            // Remove asset cost (CR)
            $lines[] = [
                'account_id' => $asset->asset_account_id,
                'description' => "Disposal of {$asset->name} — remove asset cost",
                'debit' => 0,
                'credit' => (float) $cost,
            ];

            // Record proceeds (DR bank/cash if any)
            if (bccomp($proceeds, '0', 2) > 0 && isset($data['bank_account_id'])) {
                $lines[] = [
                    'account_id' => $data['bank_account_id'],
                    'description' => "Disposal of {$asset->name} — proceeds received",
                    'debit' => (float) $proceeds,
                    'credit' => 0,
                ];
            }

            // Record gain or loss
            $gainLossAccountId = $data['gain_loss_account_id'];
            if (bccomp($gainLoss, '0', 2) > 0) {
                // Gain: CR
                $lines[] = [
                    'account_id' => $gainLossAccountId,
                    'description' => "Disposal of {$asset->name} — gain on disposal",
                    'debit' => 0,
                    'credit' => (float) $gainLoss,
                ];
            } elseif (bccomp($gainLoss, '0', 2) < 0) {
                // Loss: DR
                $lines[] = [
                    'account_id' => $gainLossAccountId,
                    'description' => "Disposal of {$asset->name} — loss on disposal",
                    'debit' => (float) abs((float) $gainLoss),
                    'credit' => 0,
                ];
            }

            $this->journalService->createAndPost(
                business: $business,
                date: $disposalDate,
                lines: $lines,
                description: "Asset disposal: {$asset->name}",
                sourceType: 'fixed_asset_disposal',
                sourceId: $asset->id,
            );

            $asset->update([
                'status' => AssetStatus::Disposed,
                'disposal_date' => $disposalDate,
                'disposal_proceeds' => $proceeds,
            ]);

            return $asset->fresh();
        });
    }

    /**
     * Run depreciation for all active assets for a given period (month).
     * Skips assets that already have a depreciation entry for the period.
     *
     * @return array{processed: int, skipped: int}
     */
    public function runDepreciation(Business $business, Carbon $periodStart): array
    {
        $periodEnd = $periodStart->copy()->endOfMonth()->startOfDay();
        $processed = 0;
        $skipped = 0;

        FixedAsset::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('status', AssetStatus::Active)
            ->each(function (FixedAsset $asset) use ($business, $periodStart, $periodEnd, &$processed, &$skipped) {
                // Skip if already depreciated for this period
                $alreadyRun = DepreciationEntry::withoutGlobalScopes()
                    ->where('fixed_asset_id', $asset->id)
                    ->where('period_start', $periodStart->toDateString())
                    ->exists();

                if ($alreadyRun) {
                    $skipped++;

                    return;
                }

                // Skip if asset was purchased after this period
                if ($asset->purchase_date->gt($periodEnd)) {
                    $skipped++;

                    return;
                }

                // Skip if fully depreciated
                if ($asset->isFullyDepreciated()) {
                    $skipped++;

                    return;
                }

                $amount = $this->calculateMonthlyDepreciation($asset);

                if (bccomp($amount, '0', 2) <= 0) {
                    $skipped++;

                    return;
                }

                $this->postDepreciationEntry($asset, $business, $periodStart, $periodEnd, $amount);
                $processed++;
            });

        return ['processed' => $processed, 'skipped' => $skipped];
    }

    /**
     * Calculate the projected depreciation schedule for an asset.
     *
     * @return Collection<int, array{period: string, depreciation: string, accumulated: string, book_value: string}>
     */
    public function calculateSchedule(FixedAsset $asset): Collection
    {
        $schedule = collect();
        $accumulated = $asset->accumulatedDepreciation();
        $cost = (string) $asset->purchase_cost;
        $salvage = (string) $asset->salvage_value;
        $start = $asset->purchase_date->copy()->startOfMonth();

        for ($i = 0; $i < $asset->useful_life_months; $i++) {
            $periodStart = $start->copy()->addMonths($i);
            $bookValue = bcsub($cost, $accumulated, 2);

            if (bccomp($bookValue, $salvage, 2) <= 0) {
                break;
            }

            $depreciation = $this->calculateMonthlyDepreciationFromValues(
                method: $asset->depreciation_method,
                cost: $cost,
                salvage: $salvage,
                usefulLifeMonths: $asset->useful_life_months,
                bookValue: $bookValue,
            );

            // Don't depreciate below salvage
            $maxDepreciation = bcsub($bookValue, $salvage, 2);
            if (bccomp($depreciation, $maxDepreciation, 2) > 0) {
                $depreciation = $maxDepreciation;
            }

            $accumulated = bcadd($accumulated, $depreciation, 2);

            $schedule->push([
                'period' => $periodStart->format('Y-m'),
                'depreciation' => $depreciation,
                'accumulated' => $accumulated,
                'book_value' => bcsub($cost, $accumulated, 2),
            ]);
        }

        return $schedule;
    }

    private function postDepreciationEntry(
        FixedAsset $asset,
        Business $business,
        Carbon $periodStart,
        Carbon $periodEnd,
        string $amount,
    ): void {
        DB::transaction(function () use ($asset, $business, $periodStart, $periodEnd, $amount) {
            $journalEntry = $this->journalService->createAndPost(
                business: $business,
                date: $periodEnd,
                lines: [
                    [
                        'account_id' => $asset->depreciation_expense_account_id,
                        'description' => "Depreciation — {$asset->name} ({$periodStart->format('M Y')})",
                        'debit' => (float) $amount,
                        'credit' => 0,
                    ],
                    [
                        'account_id' => $asset->accumulated_depreciation_account_id,
                        'description' => "Depreciation — {$asset->name} ({$periodStart->format('M Y')})",
                        'debit' => 0,
                        'credit' => (float) $amount,
                    ],
                ],
                description: "Depreciation: {$asset->name} ({$periodStart->format('M Y')})",
                sourceType: 'depreciation',
                sourceId: $asset->id,
            );

            DepreciationEntry::withoutGlobalScopes()->create([
                'business_id' => $business->id,
                'fixed_asset_id' => $asset->id,
                'journal_entry_id' => $journalEntry->id,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'depreciation_amount' => $amount,
                'created_by' => auth()->id(),
            ]);
        });
    }

    private function calculateMonthlyDepreciation(FixedAsset $asset): string
    {
        return $this->calculateMonthlyDepreciationFromValues(
            method: $asset->depreciation_method,
            cost: (string) $asset->purchase_cost,
            salvage: (string) $asset->salvage_value,
            usefulLifeMonths: $asset->useful_life_months,
            bookValue: $asset->bookValue(),
        );
    }

    private function calculateMonthlyDepreciationFromValues(
        DepreciationMethod $method,
        string $cost,
        string $salvage,
        int $usefulLifeMonths,
        string $bookValue,
    ): string {
        return match ($method) {
            DepreciationMethod::StraightLine => bcdiv(
                bcsub($cost, $salvage, 10),
                (string) $usefulLifeMonths,
                2,
            ),
            DepreciationMethod::DecliningBalance => bcdiv(
                bcmul($bookValue, bcdiv('2', (string) $usefulLifeMonths, 10), 10),
                '1',
                2,
            ),
        };
    }
}
