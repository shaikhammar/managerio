<?php

use App\Jobs\GenerateReport;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = setupBusiness($this->user);
    $this->actingAs($this->user);
});

// ── POST /reports/generate ─────────────────────────────────────────────────

test('generate dispatches job and returns cache key', function () {
    Queue::fake();

    $response = $this->postJson(route('reports.generate'), [
        'report_type' => 'profit_and_loss',
        'filters' => [
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
        ],
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure(['key', 'status'])
        ->assertJsonFragment(['status' => 'queued']);

    Queue::assertPushed(GenerateReport::class);
});

test('generate puts queued status in cache', function () {
    Queue::fake();

    $response = $this->postJson(route('reports.generate'), [
        'report_type' => 'balance_sheet',
    ]);

    $key = $response->json('key');

    expect(Cache::get($key))->toMatchArray(['status' => 'queued']);
});

test('generate rejects invalid report type', function () {
    $this->postJson(route('reports.generate'), [
        'report_type' => 'invalid_type',
    ])->assertUnprocessable();
});

test('generate requires report_type', function () {
    $this->postJson(route('reports.generate'), [])->assertUnprocessable();
});

// ── GET /reports/status ────────────────────────────────────────────────────

test('status returns not_found when key does not exist', function () {
    $this->getJson(route('reports.status', ['key' => 'nonexistent-key']))
        ->assertNotFound()
        ->assertJsonFragment(['status' => 'not_found']);
});

test('status returns queued status for pending job', function () {
    $key = 'report:test-key:queued';
    Cache::put($key, ['status' => 'queued'], 60);

    $this->getJson(route('reports.status', ['key' => $key]))
        ->assertSuccessful()
        ->assertJsonFragment(['status' => 'queued']);
});

test('status returns completed result with data', function () {
    $key = 'report:test-key:completed';
    Cache::put($key, [
        'status' => 'completed',
        'data' => ['accounts' => []],
        'generated_at' => now()->toIso8601String(),
    ], 60);

    $this->getJson(route('reports.status', ['key' => $key]))
        ->assertSuccessful()
        ->assertJsonFragment(['status' => 'completed'])
        ->assertJsonStructure(['status', 'data', 'generated_at']);
});

test('status requires key parameter', function () {
    $this->getJson(route('reports.status'))->assertUnprocessable();
});

// ── GET /reports/general-ledger (async page render) ───────────────────────

test('general ledger page dispatches job on fresh request', function () {
    Queue::fake();

    $response = $this->get(route('reports.general-ledger'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('reports/general-ledger')
            ->where('asyncStatus', 'queued')
            ->has('cacheKey')
        );

    Queue::assertPushed(GenerateReport::class);
});

test('general ledger page serves from cache when completed and fresh', function () {
    Queue::fake();

    // Pre-populate cache as a completed report
    $user = $this->user;
    $business = $this->business;
    $filters = [
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->endOfMonth()->toDateString(),
    ];
    $cacheKey = 'report:'.$business->id.':'.$user->id.':general_ledger:'.md5(serialize($filters));

    Cache::put($cacheKey, [
        'status' => 'completed',
        'data' => [],
        'generated_at' => now()->toIso8601String(),
    ], GenerateReport::CACHE_TTL);

    $response = $this->get(route('reports.general-ledger', $filters));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('asyncStatus', 'completed')
        );

    Queue::assertNothingPushed();
});
