<?php

namespace App\Http\Controllers\Translation;

use App\Domain\Translation\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectTarget;
use App\Models\TranslatorProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectDashboardController extends Controller
{
    public function board(Request $request): Response
    {
        $this->authorize('viewAny', Project::class);

        $projects = Project::query()
            ->with(['contact', 'sourceLanguage', 'serviceType', 'targets'])
            ->whereNotIn('status', [ProjectStatus::CLOSED->value])
            ->when($request->client_id, fn ($q, $id) => $q->where('contact_id', $id))
            ->when($request->service_type_id, fn ($q, $id) => $q->where('service_type_id', $id))
            ->when($request->language_pair_id, fn ($q, $id) => $q->whereHas(
                'targets',
                fn ($tq) => $tq->where('language_pair_id', $id)
            ))
            ->orderBy('deadline')
            ->get();

        $board = collect(ProjectStatus::cases())
            ->reject(fn ($s) => $s === ProjectStatus::CLOSED)
            ->mapWithKeys(fn ($status) => [
                $status->value => [
                    'status' => $status->value,
                    'label' => $status->label(),
                    'color' => $status->color(),
                    'projects' => $projects->filter(fn ($p) => $p->status === $status)->values(),
                ],
            ]);

        return Inertia::render('translation/projects/board', [
            'board' => $board,
            'filters' => $request->only('client_id', 'service_type_id', 'language_pair_id'),
        ]);
    }

    public function calendar(Request $request): Response
    {
        $this->authorize('viewAny', Project::class);

        $year = (int) $request->get('year', now()->year);
        $month = (int) $request->get('month', now()->month);

        $start = now()->setDate($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth()->endOfDay();

        $projects = Project::query()
            ->with(['contact', 'sourceLanguage', 'serviceType'])
            ->whereNotNull('deadline')
            ->whereBetween('deadline', [$start->toDateString(), $end->toDateString()])
            ->orderBy('deadline')
            ->get()
            ->map(fn ($project) => [
                'id' => $project->id,
                'name' => $project->name,
                'reference' => $project->reference,
                'deadline' => $project->deadline->toDateString(),
                'status' => $project->status->value,
                'status_label' => $project->status->label(),
                'status_color' => $project->status->color(),
                'client' => $project->contact?->name,
                'is_overdue' => $project->deadline->isPast() && ! in_array($project->status, [
                    ProjectStatus::COMPLETED,
                    ProjectStatus::DELIVERED,
                    ProjectStatus::INVOICED,
                    ProjectStatus::CLOSED,
                ]),
            ]);

        return Inertia::render('translation/projects/calendar', [
            'projects' => $projects,
            'year' => $year,
            'month' => $month,
        ]);
    }

    public function capacity(): Response
    {
        $this->authorize('viewAny', Project::class);

        $activeStatuses = [
            ProjectStatus::NEW->value,
            ProjectStatus::IN_PROGRESS->value,
            ProjectStatus::REVIEW->value,
            ProjectStatus::COMPLETED->value,
        ];

        $profiles = TranslatorProfile::query()
            ->with(['contact', 'languagePairs.sourceLanguage', 'languagePairs.targetLanguage'])
            ->get()
            ->map(function (TranslatorProfile $profile) use ($activeStatuses) {
                $pipelineWords = ProjectTarget::query()
                    ->whereHas('assignments', fn ($q) => $q->where('contact_id', $profile->contact_id))
                    ->whereHas('project', fn ($q) => $q->whereIn('status', $activeStatuses))
                    ->sum('word_count');

                $weeklyCapacity = $profile->weekly_capacity;
                $utilizationPercent = ($weeklyCapacity !== null && $weeklyCapacity > 0)
                    ? min(100, (int) round(((int) $pipelineWords / $weeklyCapacity) * 100))
                    : null;

                return [
                    'id' => $profile->id,
                    'contact_id' => $profile->contact_id,
                    'name' => $profile->contact?->name ?? 'Unknown',
                    'availability' => $profile->availability?->value,
                    'availability_label' => $profile->availability?->label(),
                    'weekly_capacity' => $weeklyCapacity,
                    'pipeline_words' => (int) $pipelineWords,
                    'utilization_percent' => $utilizationPercent,
                    'language_pairs' => $profile->languagePairs->map(fn ($lp) => [
                        'id' => $lp->id,
                        'label' => ($lp->sourceLanguage?->code ?? '?').' → '.($lp->targetLanguage?->code ?? '?'),
                    ]),
                ];
            })
            ->sortByDesc('pipeline_words')
            ->values();

        return Inertia::render('translation/projects/capacity', [
            'translators' => $profiles,
        ]);
    }
}
