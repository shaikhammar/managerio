<?php

namespace Database\Factories;

use App\Domain\Translation\Enums\ProjectAssignmentRole;
use App\Models\Contact;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\ProjectTarget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectAssignment>
 */
class ProjectAssignmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $target = ProjectTarget::factory()->create();
        $project = Project::withoutGlobalScopes()->find($target->project_id);

        return [
            'project_target_id' => $target->id,
            'contact_id' => Contact::factory()->state(['business_id' => $project->business_id]),
            'role' => ProjectAssignmentRole::TRANSLATOR->value,
            'rate' => $this->faker->optional()->randomFloat(4, 0.03, 0.15),
            'purchase_order_id' => null,
        ];
    }
}
