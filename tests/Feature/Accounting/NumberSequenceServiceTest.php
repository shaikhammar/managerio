<?php

use App\Models\Business;
use App\Models\NumberSequence;
use App\Services\Accounting\NumberSequenceService;

beforeEach(function () {
    $this->business = Business::factory()->create();
    $this->numberService = app(NumberSequenceService::class);
});

it('can generate next sequence number', function () {
    $number1 = $this->numberService->getNext($this->business, 'invoice');
    expect($number1)->toBe('INV-0001');

    $number2 = $this->numberService->getNext($this->business, 'invoice');
    expect($number2)->toBe('INV-0002');
});

it('can handle custom padding and prefix', function () {
    NumberSequence::factory()->create([
        'business_id' => $this->business->id,
        'type' => 'invoice',
        'prefix' => 'SALES',
        'next_number' => 10,
        'padding' => 6,
    ]);

    $number = $this->numberService->getNext($this->business, 'invoice');
    expect($number)->toBe('SALES-000010');
});

it('correctly increments different sequence types', function () {
    $invoice = $this->numberService->getNext($this->business, 'invoice');
    $quote = $this->numberService->getNext($this->business, 'quote');

    expect($invoice)->toBe('INV-0001')
        ->and($quote)->toBe('QT-0001');
});
