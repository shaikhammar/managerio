<?php

namespace Database\Factories;

use App\Models\LanguagePair;
use App\Models\Project;
use App\Models\ProjectTarget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectTarget>
 */
class ProjectTargetFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $project = Project::factory()->create();

        return [
            'project_id' => $project->id,
            'language_pair_id' => LanguagePair::factory()->state(['business_id' => $project->business_id]),
            'service_type_id' => null,
            'word_count' => $this->faker->optional()->numberBetween(500, 50000),
            'unit_price' => $this->faker->optional()->randomFloat(4, 0.05, 0.30),
        ];
    }
}
