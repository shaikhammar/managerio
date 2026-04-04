<?php

namespace App\Listeners;

use App\Events\ProjectMovedToInProgress;
use App\Services\Translation\ProjectService;
use Illuminate\Support\Facades\Log;

class AutoGeneratePurchaseOrdersListener
{
    public function __construct(
        private ProjectService $projectService,
    ) {}

    public function handle(ProjectMovedToInProgress $event): void
    {
        $project = $event->project;
        $project->loadMissing(['targets.assignments']);

        $hasAssignments = $project->targets->flatMap->assignments->isNotEmpty();

        if (! $hasAssignments) {
            Log::debug("Project {$project->reference} moved to In Progress with no translator assignments — skipping auto-PO.");

            return;
        }

        $this->projectService->generatePurchaseOrders($project);
    }
}
