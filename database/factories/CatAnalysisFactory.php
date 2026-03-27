<?php

namespace Database\Factories;

use App\Domain\Translation\Enums\CatTool;
use App\Models\CatAnalysis;
use App\Models\Project;
use App\Models\ProjectTarget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CatAnalysis>
 */
class CatAnalysisFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $project = Project::factory()->create();
        $target = ProjectTarget::factory()->state(['project_id' => $project->id])->create();

        return [
            'business_id' => $project->business_id,
            'project_target_id' => $target->id,
            'name' => $this->faker->randomElement(['Initial Analysis', 'Final Count', 'Updated Analysis']),
            'tool' => CatTool::Manual->value,
        ];
    }

    public function trados(): static
    {
        return $this->state(['tool' => CatTool::Trados->value]);
    }

    public function memoq(): static
    {
        return $this->state(['tool' => CatTool::MemoQ->value]);
    }

    public function phrase(): static
    {
        return $this->state(['tool' => CatTool::Phrase->value]);
    }
}
