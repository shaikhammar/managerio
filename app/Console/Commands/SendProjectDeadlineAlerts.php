<?php

namespace App\Console\Commands;

use App\Domain\Translation\Enums\ProjectStatus;
use App\Mail\ProjectDeadlineAlertMail;
use App\Models\Project;
use App\Services\MailService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendProjectDeadlineAlerts extends Command
{
    protected $signature = 'project-alerts:send-deadline';

    protected $description = 'Send deadline alert emails to translators for projects due in 2 days';

    public function handle(MailService $mailService): int
    {
        $alertDate = CarbonImmutable::today()->addDays(2);
        $count = 0;

        Project::withoutGlobalScopes()
            ->whereIn('status', [ProjectStatus::IN_PROGRESS, ProjectStatus::REVIEW])
            ->whereDate('deadline', $alertDate)
            ->whereNull('deadline_alert_sent_at')
            ->with(['business', 'targets.assignments.contact'])
            ->each(function (Project $project) use ($mailService, &$count): void {
                $business = $project->business;

                if (! $business->hasEmailConfigured()) {
                    Log::info("Skipping deadline alert for project {$project->reference}: SMTP not configured.");
                    $project->update(['deadline_alert_sent_at' => today()]);

                    return;
                }

                foreach ($project->targets as $target) {
                    foreach ($target->assignments as $assignment) {
                        $email = $assignment->contact?->email;

                        if (! $email) {
                            continue;
                        }

                        $mailService->mailerFor($business)
                            ->to($email)
                            ->queue(new ProjectDeadlineAlertMail($project, $assignment->contact));

                        $count++;
                    }
                }

                $project->update(['deadline_alert_sent_at' => today()]);
            });

        $this->info("Sent {$count} deadline ".($count === 1 ? 'alert' : 'alerts').'.');

        return self::SUCCESS;
    }
}
