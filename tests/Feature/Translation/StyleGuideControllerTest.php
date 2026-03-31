<?php

use App\Models\StyleGuide;
use App\Models\User;
use App\Services\Business\BusinessSetupService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

it('can load the style guides index', function () {
    StyleGuide::factory()->create(['business_id' => $this->business->id, 'name' => 'Acme Style Guide']);

    $this->get(route('translation.style-guides.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('translation/style-guides/index')
            ->has('styleGuides.data', 1)
        );
});

it('can search style guides by name', function () {
    StyleGuide::factory()->create(['business_id' => $this->business->id, 'name' => 'Acme Style Guide']);
    StyleGuide::factory()->create(['business_id' => $this->business->id, 'name' => 'Globex Writing Guide']);

    $this->get(route('translation.style-guides.index', ['search' => 'Acme']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('styleGuides.data', 1));
});

it('can load the create style guide page', function () {
    $this->get(route('translation.style-guides.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('translation/style-guides/create')
            ->has('customers')
        );
});

it('can create a style guide without a file', function () {
    $this->post(route('translation.style-guides.store'), [
        'name' => 'Acme EN Style Guide',
        'description' => 'Formal tone, AP style',
    ])->assertRedirect(route('translation.style-guides.index'));

    $this->assertDatabaseHas('style_guides', [
        'business_id' => $this->business->id,
        'name' => 'Acme EN Style Guide',
    ]);
});

it('can create a style guide with a file upload', function () {
    Storage::fake('public');

    $file = UploadedFile::fake()->create('style-guide.pdf', 512, 'application/pdf');

    $this->post(route('translation.style-guides.store'), [
        'name' => 'Acme Style Guide PDF',
        'file' => $file,
    ])->assertRedirect(route('translation.style-guides.index'));

    $guide = StyleGuide::where('name', 'Acme Style Guide PDF')->first();

    expect($guide)->not->toBeNull();
    expect($guide->file_name)->toBe('style-guide.pdf');
    Storage::disk('public')->assertExists($guide->file_path);
});

it('validates required fields when creating a style guide', function () {
    $this->post(route('translation.style-guides.store'), [])
        ->assertSessionHasErrors(['name']);
});

it('can load the edit style guide page', function () {
    $sg = StyleGuide::factory()->create(['business_id' => $this->business->id]);

    $this->get(route('translation.style-guides.edit', $sg))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('translation/style-guides/edit')
            ->where('styleGuide.id', $sg->id)
        );
});

it('can update a style guide', function () {
    $sg = StyleGuide::factory()->create(['business_id' => $this->business->id, 'name' => 'Old Name']);

    $this->put(route('translation.style-guides.update', $sg), [
        'name' => 'Updated Guide',
    ])->assertRedirect(route('translation.style-guides.index'));

    expect($sg->fresh()->name)->toBe('Updated Guide');
});

it('deletes the file when a style guide is deleted', function () {
    Storage::fake('public');

    $file = UploadedFile::fake()->create('style-guide.pdf', 256, 'application/pdf');

    $this->post(route('translation.style-guides.store'), [
        'name' => 'Guide To Delete',
        'file' => $file,
    ]);

    $sg = StyleGuide::where('name', 'Guide To Delete')->first();
    $filePath = $sg->file_path;

    $this->delete(route('translation.style-guides.destroy', $sg))
        ->assertRedirect(route('translation.style-guides.index'));

    $this->assertDatabaseMissing('style_guides', ['id' => $sg->id]);
    Storage::disk('public')->assertMissing($filePath);
});

it('cannot access style guides of another business', function () {
    $otherUser = User::factory()->create();
    $otherBusiness = app(BusinessSetupService::class)->createBusiness($otherUser, [
        'name' => 'Other Business',
        'currency_code' => 'USD',
        'country' => 'US',
    ]);
    $sg = StyleGuide::factory()->create(['business_id' => $otherBusiness->id]);

    $this->put(route('translation.style-guides.update', $sg), ['name' => 'Hacked'])
        ->assertStatus(404);
});
