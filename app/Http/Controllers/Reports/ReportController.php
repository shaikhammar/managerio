<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateReport;
use App\Services\Accounting\LedgerService;
use App\Services\Accounting\ReportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reportService,
        private LedgerService $ledgerService,
    ) {}

    public function index(): Response
    {
        return Inertia::render('reports/index');
    }

    public function profitAndLoss(Request $request): Response
    {
        $startDate = Carbon::parse($request->input('start_date', now()->startOfMonth()->toDateString()));
        $endDate = Carbon::parse($request->input('end_date', now()->endOfMonth()->toDateString()));

        $business = $request->user()->currentBusiness();
        $report = $this->reportService->profitAndLoss($business, $startDate, $endDate);

        return Inertia::render('reports/profit-and-loss', [
            'report' => $report,
            'filters' => ['start_date' => $startDate->toDateString(), 'end_date' => $endDate->toDateString()],
        ]);
    }

    public function balanceSheet(Request $request): Response
    {
        $asOfDate = Carbon::parse($request->input('as_of_date', now()->toDateString()));
        $business = $request->user()->currentBusiness();
        $report = $this->reportService->balanceSheet($business, $asOfDate);

        return Inertia::render('reports/balance-sheet', [
            'report' => $report,
            'filters' => ['as_of_date' => $asOfDate->toDateString()],
        ]);
    }

    public function trialBalance(Request $request): Response
    {
        $asOfDate = Carbon::parse($request->input('as_of_date', now()->toDateString()));
        $business = $request->user()->currentBusiness();
        $trialBalance = $this->ledgerService->getTrialBalance($business, $asOfDate);

        return Inertia::render('reports/trial-balance', [
            'trialBalance' => $trialBalance,
            'filters' => ['as_of_date' => $asOfDate->toDateString()],
        ]);
    }

    public function generalLedger(Request $request): Response
    {
        $startDate = Carbon::parse($request->input('start_date', now()->startOfMonth()->toDateString()));
        $endDate = Carbon::parse($request->input('end_date', now()->endOfMonth()->toDateString()));
        $business = $request->user()->currentBusiness();

        $filters = [
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
        ];

        // Build the cache key the same way GenerateReport::cacheKey() does.
        $cacheKey = 'report:'
            .$business->id.':'
            .$request->user()->id
            .':general_ledger:'
            .md5(serialize($filters));

        $cached = Cache::get($cacheKey);

        // Serve from cache if available and data is not invalidated.
        if ($cached && ($cached['status'] ?? '') === 'completed') {
            $invalidatedAt = Cache::get("report_invalidated_at:{$business->id}");
            $generatedAt = $cached['generated_at'] ?? null;

            $isStale = $invalidatedAt && $generatedAt && $invalidatedAt > $generatedAt;

            if (! $isStale) {
                return Inertia::render('reports/general-ledger', [
                    'ledger' => $cached['data'],
                    'filters' => $filters,
                    'asyncStatus' => 'completed',
                    'cacheKey' => $cacheKey,
                ]);
            }
        }

        // No valid cache — dispatch the job and return a loading state.
        Cache::put($cacheKey, ['status' => 'queued'], GenerateReport::CACHE_TTL);

        GenerateReport::dispatch($business, $request->user(), 'general_ledger', $filters);

        return Inertia::render('reports/general-ledger', [
            'ledger' => [],
            'filters' => $filters,
            'asyncStatus' => 'queued',
            'cacheKey' => $cacheKey,
        ]);
    }

    public function agedReceivables(Request $request): Response
    {
        $asOfDate = Carbon::parse($request->input('as_of_date', now()->toDateString()));
        $business = $request->user()->currentBusiness();
        $report = $this->reportService->agedReceivables($business, $asOfDate);

        return Inertia::render('reports/aged-receivables', [
            'report' => $report,
            'filters' => ['as_of_date' => $asOfDate->toDateString()],
        ]);
    }

    public function agedPayables(Request $request): Response
    {
        $asOfDate = Carbon::parse($request->input('as_of_date', now()->toDateString()));
        $business = $request->user()->currentBusiness();
        $report = $this->reportService->agedPayables($business, $asOfDate);

        return Inertia::render('reports/aged-payables', [
            'report' => $report,
            'filters' => ['as_of_date' => $asOfDate->toDateString()],
        ]);
    }

    /**
     * Dispatch an async report generation job.
     * Returns the cache key that the frontend can poll via status().
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'report_type' => ['required', 'string', 'in:profit_and_loss,balance_sheet,aged_receivables,aged_payables,trial_balance,general_ledger'],
            'filters' => ['sometimes', 'array'],
        ]);

        $business = $request->user()->currentBusiness();
        $filters = $request->input('filters', []);

        $job = new GenerateReport($business, $request->user(), $request->input('report_type'), $filters);

        Cache::put($job->cacheKey(), ['status' => 'queued'], GenerateReport::CACHE_TTL);

        dispatch($job);

        return response()->json([
            'key' => $job->cacheKey(),
            'status' => 'queued',
        ]);
    }

    /**
     * Poll the status of an async report by its cache key.
     */
    public function status(Request $request): JsonResponse
    {
        $request->validate([
            'key' => ['required', 'string'],
        ]);

        $cached = Cache::get($request->input('key'));

        if (! $cached) {
            return response()->json(['status' => 'not_found'], 404);
        }

        return response()->json($cached);
    }
}
