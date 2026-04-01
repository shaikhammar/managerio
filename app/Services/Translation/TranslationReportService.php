<?php

namespace App\Services\Translation;

use App\Domain\Translation\Enums\ProjectStatus;
use App\Models\Business;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TranslationReportService
{
    /** @return array<string, mixed> */
    public function projectProfitability(Business $business, Carbon $start, Carbon $end): array
    {
        $projects = Project::query()
            ->with([
                'contact:id,name',
                'targets.assignments.purchaseOrder:id,total',
                'targets',
                'invoice:id,total',
            ])
            ->where('business_id', $business->id)
            ->whereNotNull('deadline')
            ->whereDate('deadline', '>=', $start->toDateString())
            ->whereDate('deadline', '<=', $end->toDateString())
            ->orderBy('deadline')
            ->get();

        $rows = $projects->map(function (Project $project): array {
            $revenue = $project->invoice
                ? (float) $project->invoice->total
                : $this->estimatedRevenue($project);

            $cost = $this->totalCost($project);
            $margin = $revenue - $cost;
            $marginPct = $revenue > 0 ? round($margin / $revenue * 100, 1) : 0.0;

            return [
                'id' => $project->id,
                'name' => $project->name,
                'reference' => $project->reference,
                'client' => $project->contact?->name ?? '—',
                'status' => $project->status->value,
                'status_label' => $project->status->label(),
                'deadline' => $project->deadline?->toDateString(),
                'revenue' => round($revenue, 2),
                'cost' => round($cost, 2),
                'margin' => round($margin, 2),
                'margin_pct' => $marginPct,
            ];
        });

        $totalRevenue = $rows->sum('revenue');
        $totalCost = $rows->sum('cost');
        $totalMargin = $totalRevenue - $totalCost;
        $totalMarginPct = $totalRevenue > 0 ? round($totalMargin / $totalRevenue * 100, 1) : 0.0;

        return [
            'period' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
            'projects' => $rows->values()->all(),
            'totals' => [
                'revenue' => round($totalRevenue, 2),
                'cost' => round($totalCost, 2),
                'margin' => round($totalMargin, 2),
                'margin_pct' => $totalMarginPct,
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function revenueByLanguagePair(Business $business, Carbon $start, Carbon $end): array
    {
        $rows = DB::table('project_targets as pt')
            ->join('projects as p', 'p.id', '=', 'pt.project_id')
            ->join('language_pairs as lp', 'lp.id', '=', 'pt.language_pair_id')
            ->join('languages as src', 'src.id', '=', 'lp.source_language_id')
            ->join('languages as tgt', 'tgt.id', '=', 'lp.target_language_id')
            ->where('p.business_id', $business->id)
            ->whereNotNull('p.deadline')
            ->whereDate('p.deadline', '>=', $start->toDateString())
            ->whereDate('p.deadline', '<=', $end->toDateString())
            ->groupBy('lp.id', 'src.code', 'src.name', 'tgt.code', 'tgt.name')
            ->select([
                'lp.id as language_pair_id',
                'src.code as source_code',
                'src.name as source_name',
                'tgt.code as target_code',
                'tgt.name as target_name',
                DB::raw('COUNT(DISTINCT p.id) as project_count'),
                DB::raw('SUM(COALESCE(pt.word_count, 0)) as word_count'),
                DB::raw('SUM(COALESCE(pt.word_count, 0) * COALESCE(pt.unit_price, 0)) as revenue'),
            ])
            ->orderByDesc('revenue')
            ->get();

        $totalRevenue = $rows->sum('revenue');

        $mapped = $rows->map(fn ($row) => [
            'language_pair' => "{$row->source_code} → {$row->target_code}",
            'source_name' => $row->source_name,
            'target_name' => $row->target_name,
            'project_count' => (int) $row->project_count,
            'word_count' => (int) $row->word_count,
            'revenue' => round((float) $row->revenue, 2),
            'revenue_pct' => $totalRevenue > 0 ? round((float) $row->revenue / $totalRevenue * 100, 1) : 0.0,
        ]);

        return [
            'period' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
            'rows' => $mapped->values()->all(),
            'totals' => ['revenue' => round((float) $totalRevenue, 2)],
        ];
    }

    /** @return array<string, mixed> */
    public function revenueByServiceType(Business $business, Carbon $start, Carbon $end): array
    {
        $rows = DB::table('project_targets as pt')
            ->join('projects as p', 'p.id', '=', 'pt.project_id')
            ->leftJoin('service_types as st', 'st.id', '=', 'pt.service_type_id')
            ->where('p.business_id', $business->id)
            ->whereNotNull('p.deadline')
            ->whereDate('p.deadline', '>=', $start->toDateString())
            ->whereDate('p.deadline', '<=', $end->toDateString())
            ->groupBy('st.id', 'st.name')
            ->select([
                'st.id as service_type_id',
                'st.name as service_type_name',
                DB::raw('COUNT(DISTINCT p.id) as project_count'),
                DB::raw('SUM(COALESCE(pt.word_count, 0)) as word_count'),
                DB::raw('SUM(COALESCE(pt.word_count, 0) * COALESCE(pt.unit_price, 0)) as revenue'),
            ])
            ->orderByDesc('revenue')
            ->get();

        $totalRevenue = $rows->sum('revenue');

        $mapped = $rows->map(fn ($row) => [
            'service_type' => $row->service_type_name ?? 'Unspecified',
            'project_count' => (int) $row->project_count,
            'word_count' => (int) $row->word_count,
            'revenue' => round((float) $row->revenue, 2),
            'revenue_pct' => $totalRevenue > 0 ? round((float) $row->revenue / $totalRevenue * 100, 1) : 0.0,
        ]);

        return [
            'period' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
            'rows' => $mapped->values()->all(),
            'totals' => ['revenue' => round((float) $totalRevenue, 2)],
        ];
    }

    /** @return array<string, mixed> */
    public function revenueByClient(Business $business, Carbon $start, Carbon $end): array
    {
        $rows = DB::table('projects as p')
            ->leftJoin('contacts as c', 'c.id', '=', 'p.contact_id')
            ->leftJoin('project_targets as pt', 'pt.project_id', '=', 'p.id')
            ->where('p.business_id', $business->id)
            ->whereNotNull('p.deadline')
            ->whereDate('p.deadline', '>=', $start->toDateString())
            ->whereDate('p.deadline', '<=', $end->toDateString())
            ->groupBy('c.id', 'c.name')
            ->select([
                'c.id as client_id',
                'c.name as client_name',
                DB::raw('COUNT(DISTINCT p.id) as project_count'),
                DB::raw('SUM(COALESCE(pt.word_count, 0)) as word_count'),
                DB::raw('SUM(COALESCE(pt.word_count, 0) * COALESCE(pt.unit_price, 0)) as revenue'),
            ])
            ->orderByDesc('revenue')
            ->get();

        $totalRevenue = $rows->sum('revenue');

        $mapped = $rows->map(fn ($row) => [
            'client' => $row->client_name ?? '—',
            'project_count' => (int) $row->project_count,
            'word_count' => (int) $row->word_count,
            'revenue' => round((float) $row->revenue, 2),
            'revenue_pct' => $totalRevenue > 0 ? round((float) $row->revenue / $totalRevenue * 100, 1) : 0.0,
        ]);

        return [
            'period' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
            'rows' => $mapped->values()->all(),
            'totals' => ['revenue' => round((float) $totalRevenue, 2)],
        ];
    }

    /** @return array<string, mixed> */
    public function translatorUtilisation(Business $business, Carbon $start, Carbon $end): array
    {
        $rows = DB::table('project_assignments as pa')
            ->join('project_targets as pt', 'pt.id', '=', 'pa.project_target_id')
            ->join('projects as p', 'p.id', '=', 'pt.project_id')
            ->join('contacts as c', 'c.id', '=', 'pa.contact_id')
            ->leftJoin('invoices as po', 'po.id', '=', 'pa.purchase_order_id')
            ->where('p.business_id', $business->id)
            ->whereNotNull('p.deadline')
            ->whereDate('p.deadline', '>=', $start->toDateString())
            ->whereDate('p.deadline', '<=', $end->toDateString())
            ->groupBy('c.id', 'c.name')
            ->select([
                'c.id as translator_id',
                'c.name as translator_name',
                DB::raw('COUNT(DISTINCT p.id) as project_count'),
                DB::raw('SUM(COALESCE(pt.word_count, 0)) as word_count'),
                DB::raw('SUM(COALESCE(po.total, COALESCE(pt.word_count, 0) * COALESCE(pa.rate, 0))) as earned'),
            ])
            ->orderByDesc('word_count')
            ->get();

        $mapped = $rows->map(function ($row) {
            $wordCount = (int) $row->word_count;
            $earned = round((float) $row->earned, 2);
            $avgRate = $wordCount > 0 ? round($earned / $wordCount, 4) : 0.0;

            return [
                'translator' => $row->translator_name,
                'project_count' => (int) $row->project_count,
                'word_count' => $wordCount,
                'earned' => $earned,
                'avg_rate' => $avgRate,
            ];
        });

        return [
            'period' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
            'rows' => $mapped->values()->all(),
            'totals' => [
                'word_count' => $mapped->sum('word_count'),
                'earned' => round($mapped->sum('earned'), 2),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function averageMargin(Business $business, Carbon $start, Carbon $end): array
    {
        $projects = Project::query()
            ->with([
                'targets.assignments.purchaseOrder:id,total',
                'targets',
                'invoice:id,total',
            ])
            ->where('business_id', $business->id)
            ->whereNotNull('deadline')
            ->whereDate('deadline', '>=', $start->toDateString())
            ->whereDate('deadline', '<=', $end->toDateString())
            ->get();

        $totalRevenue = 0.0;
        $totalCost = 0.0;

        foreach ($projects as $project) {
            $totalRevenue += $project->invoice
                ? (float) $project->invoice->total
                : $this->estimatedRevenue($project);
            $totalCost += $this->totalCost($project);
        }

        $grossMargin = $totalRevenue - $totalCost;
        $marginPct = $totalRevenue > 0 ? round($grossMargin / $totalRevenue * 100, 1) : 0.0;

        return [
            'period' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
            'project_count' => $projects->count(),
            'total_revenue' => round($totalRevenue, 2),
            'total_cost' => round($totalCost, 2),
            'gross_margin' => round($grossMargin, 2),
            'margin_pct' => $marginPct,
        ];
    }

    /** @return array<string, mixed> */
    public function deliveryPerformance(Business $business, Carbon $start, Carbon $end): array
    {
        $completedStatuses = [
            ProjectStatus::DELIVERED->value,
            ProjectStatus::INVOICED->value,
            ProjectStatus::CLOSED->value,
        ];

        $activeStatuses = [
            ProjectStatus::NEW->value,
            ProjectStatus::IN_PROGRESS->value,
            ProjectStatus::REVIEW->value,
            ProjectStatus::COMPLETED->value,
        ];

        // Projects in the period that reached a terminal/near-terminal state
        $completed = Project::query()
            ->with('contact:id,name')
            ->where('business_id', $business->id)
            ->whereIn('status', $completedStatuses)
            ->whereNotNull('deadline')
            ->whereDate('deadline', '>=', $start->toDateString())
            ->whereDate('deadline', '<=', $end->toDateString())
            ->get();

        $onTime = 0;
        $late = 0;

        $completedRows = $completed->map(function (Project $project) use (&$onTime, &$late): array {
            // Use updated_at as proxy for completion time
            $deliveredOnTime = $project->deadline >= $project->updated_at->toDateString();

            if ($deliveredOnTime) {
                $onTime++;
            } else {
                $late++;
            }

            return [
                'id' => $project->id,
                'name' => $project->name,
                'client' => $project->contact?->name ?? '—',
                'status' => $project->status->value,
                'status_label' => $project->status->label(),
                'deadline' => $project->deadline->toDateString(),
                'on_time' => $deliveredOnTime,
            ];
        });

        $total = $completed->count();
        $onTimeRate = $total > 0 ? round($onTime / $total * 100, 1) : 0.0;

        // Currently overdue active projects (regardless of period)
        $today = now()->toDateString();
        $overdue = Project::query()
            ->with('contact:id,name')
            ->where('business_id', $business->id)
            ->whereIn('status', $activeStatuses)
            ->whereNotNull('deadline')
            ->whereDate('deadline', '<', $today)
            ->orderBy('deadline')
            ->get()
            ->map(fn (Project $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'client' => $p->contact?->name ?? '—',
                'status' => $p->status->value,
                'status_label' => $p->status->label(),
                'deadline' => $p->deadline->toDateString(),
                'days_overdue' => (int) abs(now()->startOfDay()->diffInDays($p->deadline->startOfDay())),
            ]);

        return [
            'period' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
            'summary' => [
                'total' => $total,
                'on_time' => $onTime,
                'late' => $late,
                'on_time_rate' => $onTimeRate,
            ],
            'completed' => $completedRows->values()->all(),
            'overdue' => $overdue->values()->all(),
        ];
    }

    /** @return array<string, mixed> */
    public function pipeline(Business $business): array
    {
        $activeStatuses = [
            ProjectStatus::NEW->value,
            ProjectStatus::IN_PROGRESS->value,
            ProjectStatus::REVIEW->value,
            ProjectStatus::COMPLETED->value,
        ];

        $today = now()->toDateString();

        $projects = Project::query()
            ->with([
                'contact:id,name',
                'targets',
                'targets.languagePair.sourceLanguage:id,code',
                'targets.languagePair.targetLanguage:id,code',
            ])
            ->where('business_id', $business->id)
            ->whereIn('status', $activeStatuses)
            ->orderByRaw('CASE WHEN deadline IS NULL THEN 1 ELSE 0 END, deadline ASC')
            ->get();

        $rows = $projects->map(function (Project $project): array {
            $expectedRevenue = $this->estimatedRevenue($project);

            $daysUntilDeadline = null;

            if ($project->deadline !== null) {
                $diff = now()->startOfDay()->diffInDays($project->deadline->startOfDay(), false);
                $daysUntilDeadline = (int) $diff;
            }

            $languagePairs = $project->targets
                ->map(fn ($t) => $t->languagePair
                    ? "{$t->languagePair->sourceLanguage?->code} → {$t->languagePair->targetLanguage?->code}"
                    : null)
                ->filter()
                ->unique()
                ->values()
                ->all();

            return [
                'id' => $project->id,
                'name' => $project->name,
                'reference' => $project->reference,
                'client' => $project->contact?->name ?? '—',
                'status' => $project->status->value,
                'status_label' => $project->status->label(),
                'deadline' => $project->deadline?->toDateString(),
                'days_until_deadline' => $daysUntilDeadline,
                'expected_revenue' => round($expectedRevenue, 2),
                'language_pairs' => $languagePairs,
            ];
        });

        return [
            'rows' => $rows->values()->all(),
            'totals' => [
                'project_count' => $projects->count(),
                'expected_revenue' => round($rows->sum('expected_revenue'), 2),
            ],
        ];
    }

    private function estimatedRevenue(Project $project): float
    {
        return (float) $project->targets->reduce(
            fn ($carry, $target) => bcadd(
                $carry,
                bcmul((string) ($target->word_count ?? 0), (string) ($target->unit_price ?? 0), 4),
                4
            ),
            '0'
        );
    }

    private function totalCost(Project $project): float
    {
        $seen = [];
        $cost = 0.0;

        foreach ($project->targets as $target) {
            foreach ($target->assignments as $assignment) {
                if ($assignment->purchaseOrder) {
                    $poId = $assignment->purchaseOrder->id;
                    if (! isset($seen[$poId])) {
                        $seen[$poId] = true;
                        $cost += (float) $assignment->purchaseOrder->total;
                    }
                } elseif ($assignment->rate !== null && $target->word_count !== null) {
                    $cost += (float) bcmul((string) $target->word_count, (string) $assignment->rate, 4);
                }
            }
        }

        return $cost;
    }
}
