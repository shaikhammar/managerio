<?php

namespace Database\Factories;

use App\Domain\Translation\Enums\ProjectFileType;
use App\Models\Project;
use App\Models\ProjectFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectFile>
 */
class ProjectFileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => $this->faker->word().'.docx',
            'path' => 'project-files/'.$this->faker->uuid().'.docx',
            'type' => ProjectFileType::SOURCE->value,
            'size' => $this->faker->numberBetween(1024, 10485760),
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];
    }

    public function deliverable(): static
    {
        return $this->state(['type' => ProjectFileType::DELIVERABLE->value]);
    }
}
