<?php

namespace App\Jobs;

use App\Models\Business;
use App\Models\User;
use App\Services\Accounting\LedgerService;
use App\Services\Accounting\ReportService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class GenerateReport implements ShouldQueue
{
    use Queueable;

    /** How long (in seconds) the cached result lives. */
    public const CACHE_TTL = 3600;

    public function __construct(
        public readonly Business $business,
        public readonly User $requestedBy,
        public readonly string $reportType,
        public readonly array $filters = [],
    ) {}

    public function handle(ReportService $reportService, LedgerService $ledgerService): void
    {
        $data = match ($this->reportType) {
            'profit_and_loss' => $reportService->profitAndLoss(
                $this->business,
                Carbon::parse($this->filters['start_date'] ?? now()->startOfMonth()),
                Carbon::parse($this->filters['end_date'] ?? now()->endOfMonth()),
            ),
            'balance_sheet' => $reportService->balanceSheet(
                $this->business,
                Carbon::parse($this->filters['as_of_date'] ?? now()),
            ),
            'aged_receivables' => $reportService->agedReceivables(
                $this->business,
                Carbon::parse($this->filters['as_of_date'] ?? now()),
            ),
            'aged_payables' => $reportService->agedPayables(
                $this->business,
                Carbon::parse($this->filters['as_of_date'] ?? now()),
            ),
            'trial_balance' => $ledgerService->getTrialBalance(
                $this->business,
                Carbon::parse($this->filters['as_of_date'] ?? now()),
            ),
            'general_ledger' => $ledgerService->getGeneralLedger(
                $this->business,
                Carbon::parse($this->filters['start_date'] ?? now()->startOfMonth()),
                Carbon::parse($this->filters['end_date'] ?? now()->endOfMonth()),
            ),
            default => throw new InvalidArgumentException("Unknown report type: {$this->reportType}"),
        };

        Cache::put($this->cacheKey(), [
            'status' => 'completed',
            'data' => $data,
            'generated_at' => now()->toIso8601String(),
        ], self::CACHE_TTL);
    }

    public function failed(\Throwable $exception): void
    {
        Cache::put($this->cacheKey(), [
            'status' => 'failed',
            'error' => $exception->getMessage(),
        ], self::CACHE_TTL);
    }

    /**
     * Unique cache key for this report request.
     */
    public function cacheKey(): string
    {
        $filterHash = md5(serialize($this->filters));

        return "report:{$this->business->id}:{$this->requestedBy->id}:{$this->reportType}:{$filterHash}";
    }
}
