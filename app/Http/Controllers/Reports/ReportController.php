<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\Accounting\LedgerService;
use App\Services\Accounting\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reportService,
        private LedgerService $ledgerService,
    ) {}

    public function index()
    {
        return Inertia::render('reports/index');
    }

    public function profitAndLoss(Request $request)
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

    public function balanceSheet(Request $request)
    {
        $asOfDate = Carbon::parse($request->input('as_of_date', now()->toDateString()));
        $business = $request->user()->currentBusiness();
        $report = $this->reportService->balanceSheet($business, $asOfDate);

        return Inertia::render('reports/balance-sheet', [
            'report' => $report,
            'filters' => ['as_of_date' => $asOfDate->toDateString()],
        ]);
    }

    public function trialBalance(Request $request)
    {
        $asOfDate = Carbon::parse($request->input('as_of_date', now()->toDateString()));
        $business = $request->user()->currentBusiness();
        $trialBalance = $this->ledgerService->getTrialBalance($business, $asOfDate);

        return Inertia::render('reports/trial-balance', [
            'trialBalance' => $trialBalance,
            'filters' => ['as_of_date' => $asOfDate->toDateString()],
        ]);
    }

    public function generalLedger(Request $request)
    {
        $startDate = Carbon::parse($request->input('start_date', now()->startOfMonth()->toDateString()));
        $endDate = Carbon::parse($request->input('end_date', now()->endOfMonth()->toDateString()));
        $business = $request->user()->currentBusiness();
        $ledger = $this->ledgerService->getGeneralLedger($business, $startDate, $endDate);

        return Inertia::render('reports/general-ledger', [
            'ledger' => $ledger,
            'filters' => ['start_date' => $startDate->toDateString(), 'end_date' => $endDate->toDateString()],
        ]);
    }

    public function agedReceivables(Request $request)
    {
        $asOfDate = Carbon::parse($request->input('as_of_date', now()->toDateString()));
        $business = $request->user()->currentBusiness();
        $report = $this->reportService->agedReceivables($business, $asOfDate);

        return Inertia::render('reports/aged-receivables', [
            'report' => $report,
            'filters' => ['as_of_date' => $asOfDate->toDateString()],
        ]);
    }

    public function agedPayables(Request $request)
    {
        $asOfDate = Carbon::parse($request->input('as_of_date', now()->toDateString()));
        $business = $request->user()->currentBusiness();
        $report = $this->reportService->agedPayables($business, $asOfDate);

        return Inertia::render('reports/aged-payables', [
            'report' => $report,
            'filters' => ['as_of_date' => $asOfDate->toDateString()],
        ]);
    }
}
