<?php

namespace Database\Factories;

use App\Domain\Translation\Enums\ProjectStatus;
use App\Models\Business;
use App\Models\Contact;
use App\Models\Language;
use App\Models\Project;
use App\Models\ServiceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $business = Business::factory()->create();

        return [
            'business_id' => $business->id,
            'contact_id' => Contact::factory()->state(['business_id' => $business->id]),
            'source_language_id' => Language::factory()->state(['business_id' => $business->id]),
            'service_type_id' => ServiceType::factory()->state(['business_id' => $business->id]),
            'name' => $this->faker->sentence(4),
            'reference' => 'PROJ-'.$this->faker->numerify('###'),
            'deadline' => $this->faker->optional()->dateTimeBetween('+1 week', '+3 months')?->format('Y-m-d'),
            'notes' => $this->faker->optional()->sentence(),
            'status' => ProjectStatus::NEW->value,
            'quote_id' => null,
            'invoice_id' => null,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(['status' => ProjectStatus::IN_PROGRESS->value]);
    }

    public function completed(): static
    {
        return $this->state(['status' => ProjectStatus::COMPLETED->value]);
    }

    public function closed(): static
    {
        return $this->state(['status' => ProjectStatus::CLOSED->value]);
    }
}
