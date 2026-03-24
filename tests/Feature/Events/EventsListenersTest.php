<?php

use App\Events\BusinessCreated;
use App\Events\InvoicePosted;
use App\Events\JournalEntryPosted;
use App\Events\PaymentReceived;
use App\Listeners\InvalidateReportCache;
use App\Listeners\SendWelcomeEmail;
use App\Mail\WelcomeBusiness;
use App\Models\Business;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;

// ── Listener registration ──────────────────────────────────────────────────

test('InvalidateReportCache is registered for InvoicePosted', function () {
    Event::fake();
    event(new InvoicePosted(Invoice::factory()->create()));
    Event::assertListening(InvoicePosted::class, InvalidateReportCache::class);
});

test('InvalidateReportCache is registered for PaymentReceived', function () {
    $business = Business::factory()->create();
    $payment = Payment::factory()->create(['business_id' => $business->id]);

    Event::fake();
    event(new PaymentReceived($payment));
    Event::assertListening(PaymentReceived::class, InvalidateReportCache::class);
});

test('InvalidateReportCache is registered for JournalEntryPosted', function () {
    Event::fake();
    event(new JournalEntryPosted(JournalEntry::factory()->create()));
    Event::assertListening(JournalEntryPosted::class, InvalidateReportCache::class);
});

test('SendWelcomeEmail is registered for BusinessCreated', function () {
    Event::fake();
    $user = User::factory()->create();
    $business = Business::factory()->create();
    event(new BusinessCreated($business, $user));
    Event::assertListening(BusinessCreated::class, SendWelcomeEmail::class);
});

// ── InvalidateReportCache listener ────────────────────────────────────────

test('InvoicePosted writes invalidation timestamp to cache', function () {
    $invoice = Invoice::factory()->create();

    (new InvalidateReportCache)->handle(new InvoicePosted($invoice));

    expect(Cache::has("report_invalidated_at:{$invoice->business_id}"))->toBeTrue();
});

test('JournalEntryPosted writes invalidation timestamp to cache', function () {
    $entry = JournalEntry::factory()->create();

    (new InvalidateReportCache)->handle(new JournalEntryPosted($entry));

    expect(Cache::has("report_invalidated_at:{$entry->business_id}"))->toBeTrue();
});

test('PaymentReceived writes invalidation timestamp to cache', function () {
    $business = Business::factory()->create();
    $payment = Payment::factory()->create(['business_id' => $business->id]);

    (new InvalidateReportCache)->handle(new PaymentReceived($payment));

    expect(Cache::has("report_invalidated_at:{$business->id}"))->toBeTrue();
});

test('InvalidateReportCache overwrites existing timestamp with a newer one', function () {
    $invoice = Invoice::factory()->create();
    $key = "report_invalidated_at:{$invoice->business_id}";

    Cache::put($key, now()->subHour()->toIso8601String(), now()->addDay());

    (new InvalidateReportCache)->handle(new InvoicePosted($invoice));

    $stored = Cache::get($key);
    expect($stored)->not->toBeNull();
    expect(now()->parse($stored)->isAfter(now()->subMinute()))->toBeTrue();
});

// ── SendWelcomeEmail listener ─────────────────────────────────────────────

test('BusinessCreated queues WelcomeBusiness mailable to owner', function () {
    Mail::fake();

    $user = User::factory()->create();
    $business = Business::factory()->create();

    (new SendWelcomeEmail)->handle(new BusinessCreated($business, $user));

    Mail::assertQueued(WelcomeBusiness::class, function (WelcomeBusiness $mail) use ($user, $business) {
        return $mail->business->is($business)
            && $mail->owner->is($user)
            && $mail->hasTo($user->email);
    });
});
