<?php

namespace App\Http\Controllers\Translation;

use App\Http\Controllers\Controller;
use App\Services\Translation\TranslationReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TranslationReportController extends Controller
{
    public function __construct(
        private TranslationReportService $reportService,
    ) {}

    public function index(): Response
    {
        return Inertia::render('translation/reports/index');
    }

    public function projectProfitability(Request $request): Response
    {
        $start = Carbon::parse($request->input('start_date', now()->startOfYear()->toDateString()));
        $end = Carbon::parse($request->input('end_date', now()->toDateString()));
        $business = $request->user()->currentBusiness();

        return Inertia::render('translation/reports/project-profitability', [
            'report' => $this->reportService->projectProfitability($business, $start, $end),
            'filters' => ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString()],
        ]);
    }

    public function revenueByLanguagePair(Request $request): Response
    {
        $start = Carbon::parse($request->input('start_date', now()->startOfYear()->toDateString()));
        $end = Carbon::parse($request->input('end_date', now()->toDateString()));
        $business = $request->user()->currentBusiness();

        return Inertia::render('translation/reports/revenue-language-pair', [
            'report' => $this->reportService->revenueByLanguagePair($business, $start, $end),
            'filters' => ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString()],
        ]);
    }

    public function revenueByServiceType(Request $request): Response
    {
        $start = Carbon::parse($request->input('start_date', now()->startOfYear()->toDateString()));
        $end = Carbon::parse($request->input('end_date', now()->toDateString()));
        $business = $request->user()->currentBusiness();

        return Inertia::render('translation/reports/revenue-service-type', [
            'report' => $this->reportService->revenueByServiceType($business, $start, $end),
            'filters' => ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString()],
        ]);
    }

    public function revenueByClient(Request $request): Response
    {
        $start = Carbon::parse($request->input('start_date', now()->startOfYear()->toDateString()));
        $end = Carbon::parse($request->input('end_date', now()->toDateString()));
        $business = $request->user()->currentBusiness();

        return Inertia::render('translation/reports/revenue-client', [
            'report' => $this->reportService->revenueByClient($business, $start, $end),
            'filters' => ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString()],
        ]);
    }

    public function translatorUtilisation(Request $request): Response
    {
        $start = Carbon::parse($request->input('start_date', now()->startOfYear()->toDateString()));
        $end = Carbon::parse($request->input('end_date', now()->toDateString()));
        $business = $request->user()->currentBusiness();

        return Inertia::render('translation/reports/translator-utilisation', [
            'report' => $this->reportService->translatorUtilisation($business, $start, $end),
            'filters' => ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString()],
        ]);
    }

    public function averageMargin(Request $request): Response
    {
        $start = Carbon::parse($request->input('start_date', now()->startOfYear()->toDateString()));
        $end = Carbon::parse($request->input('end_date', now()->toDateString()));
        $business = $request->user()->currentBusiness();

        return Inertia::render('translation/reports/average-margin', [
            'report' => $this->reportService->averageMargin($business, $start, $end),
            'filters' => ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString()],
        ]);
    }

    public function deliveryPerformance(Request $request): Response
    {
        $start = Carbon::parse($request->input('start_date', now()->startOfYear()->toDateString()));
        $end = Carbon::parse($request->input('end_date', now()->toDateString()));
        $business = $request->user()->currentBusiness();

        return Inertia::render('translation/reports/delivery-performance', [
            'report' => $this->reportService->deliveryPerformance($business, $start, $end),
            'filters' => ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString()],
        ]);
    }

    public function pipeline(Request $request): Response
    {
        $business = $request->user()->currentBusiness();

        return Inertia::render('translation/reports/pipeline', [
            'report' => $this->reportService->pipeline($business),
        ]);
    }
}
