<?php

use App\Domain\Translation\Enums\BillingUnit;
use App\Models\ServiceType;
use App\Models\User;
use App\Services\Business\BusinessSetupService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = app(BusinessSetupService::class)->createBusiness($this->user, [
        'name' => 'Test Business',
        'currency_code' => 'USD',
        'country' => 'US',
    ]);

    $this->actingAs($this->user);
    session(['current_business_id' => $this->business->id]);
});

it('can load the service types index', function () {
    ServiceType::factory()->create(['business_id' => $this->business->id, 'name' => 'Translation', 'code' => 'translation']);

    $this->get(route('translation.service-types.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('translation/service-types/index')
            ->has('serviceTypes.data', 1)
            ->has('billingUnits')
        );
});

it('can load the create service type page', function () {
    $this->get(route('translation.service-types.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('translation/service-types/create')
            ->has('billingUnits')
        );
});

it('can create a service type', function () {
    $this->post(route('translation.service-types.store'), [
        'name' => 'Translation',
        'code' => 'translation',
        'description' => 'Full document translation service',
        'default_unit' => 'word',
    ])->assertRedirect(route('translation.service-types.index'));

    $this->assertDatabaseHas('service_types', [
        'business_id' => $this->business->id,
        'name' => 'Translation',
        'code' => 'translation',
        'default_unit' => 'word',
        'is_active' => true,
    ]);
});

it('cannot create a service type with a duplicate code', function () {
    ServiceType::factory()->create(['business_id' => $this->business->id, 'name' => 'Translation', 'code' => 'translation']);

    $this->post(route('translation.service-types.store'), [
        'name' => 'Translation (Legal)',
        'code' => 'translation',
        'default_unit' => 'word',
    ])->assertSessionHasErrors('code');
});

it('cannot create a service type with an invalid billing unit', function () {
    $this->post(route('translation.service-types.store'), [
        'name' => 'Translation',
        'code' => 'translation',
        'default_unit' => 'invalid_unit',
    ])->assertSessionHasErrors('default_unit');
});

it('can create a service type billed by hour', function () {
    $this->post(route('translation.service-types.store'), [
        'name' => 'Interpreting',
        'code' => 'interpreting',
        'default_unit' => BillingUnit::Hour->value,
    ])->assertRedirect(route('translation.service-types.index'));

    $this->assertDatabaseHas('service_types', [
        'business_id' => $this->business->id,
        'code' => 'interpreting',
        'default_unit' => 'hour',
    ]);
});

it('can update a service type', function () {
    $serviceType = ServiceType::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Translation',
        'code' => 'translation',
        'default_unit' => 'word',
    ]);

    $this->put(route('translation.service-types.update', $serviceType), [
        'name' => 'Translation',
        'code' => 'translation',
        'description' => 'Updated description',
        'default_unit' => 'word',
        'is_active' => true,
    ])->assertRedirect(route('translation.service-types.index'));

    expect($serviceType->fresh()->description)->toBe('Updated description');
});

it('can delete a service type', function () {
    $serviceType = ServiceType::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Translation',
        'code' => 'translation',
    ]);

    $this->delete(route('translation.service-types.destroy', $serviceType))
        ->assertRedirect(route('translation.service-types.index'));

    $this->assertDatabaseMissing('service_types', ['id' => $serviceType->id]);
});
