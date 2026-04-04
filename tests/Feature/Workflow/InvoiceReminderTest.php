<?php

use App\Domain\Sales\Enums\InvoiceStatus;
use App\Domain\Sales\Enums\InvoiceType;
use App\Mail\InvoiceReminderMail;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\User;
use App\Services\MailService;
use Illuminate\Mail\Mailer;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = setupBusiness($this->user);
    $this->business->update([
        'smtp_host' => 'smtp.test',
        'smtp_from_email' => 'agency@test.com',
    ]);
});

function overdueInvoice(int $businessId, int $contactId, ?string $lastReminderSentAt = null): Invoice
{
    return Invoice::factory()->create([
        'business_id' => $businessId,
        'contact_id' => $contactId,
        'type' => InvoiceType::INVOICE,
        'status' => InvoiceStatus::SENT,
        'due_date' => now()->subDays(3)->format('Y-m-d'),
        'last_reminder_sent_at' => $lastReminderSentAt,
    ]);
}

it('sends a reminder for an overdue SENT invoice with no prior reminder', function () {
    $mockMailer = Mockery::mock(Mailer::class);
    $mockMailer->shouldReceive('to')->andReturnSelf();
    $mockMailer->shouldReceive('queue')->once()->with(Mockery::type(InvoiceReminderMail::class));
    $mockMailService = Mockery::mock(MailService::class);
    $mockMailService->shouldReceive('mailerFor')->once()->andReturn($mockMailer);
    app()->instance(MailService::class, $mockMailService);

    $contact = Contact::factory()->create(['business_id' => $this->business->id, 'email' => 'client@example.com']);
    overdueInvoice($this->business->id, $contact->id);

    $this->artisan('invoice-reminders:send')->assertSuccessful();
});

it('updates last_reminder_sent_at after sending', function () {
    $mockMailer = Mockery::mock(Mailer::class);
    $mockMailer->shouldReceive('to')->andReturnSelf();
    $mockMailer->shouldReceive('queue')->once();
    $mockMailService = Mockery::mock(MailService::class);
    $mockMailService->shouldReceive('mailerFor')->once()->andReturn($mockMailer);
    app()->instance(MailService::class, $mockMailService);

    $contact = Contact::factory()->create(['business_id' => $this->business->id, 'email' => 'client@example.com']);
    $invoice = overdueInvoice($this->business->id, $contact->id);

    $this->artisan('invoice-reminders:send')->assertSuccessful();

    expect($invoice->fresh()->last_reminder_sent_at)->not->toBeNull();
});

it('skips an overdue invoice with last_reminder_sent_at within 7 days', function () {
    $mockMailService = Mockery::mock(MailService::class);
    $mockMailService->shouldNotReceive('mailerFor');
    app()->instance(MailService::class, $mockMailService);

    $contact = Contact::factory()->create(['business_id' => $this->business->id, 'email' => 'client@example.com']);
    Invoice::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $contact->id,
        'type' => InvoiceType::INVOICE,
        'status' => InvoiceStatus::SENT,
        'due_date' => now()->subDays(5)->format('Y-m-d'),
        'last_reminder_sent_at' => now()->subDays(3),
    ]);

    $this->artisan('invoice-reminders:send')->assertSuccessful();
});

it('re-sends if last_reminder_sent_at is more than 7 days ago', function () {
    $mockMailer = Mockery::mock(Mailer::class);
    $mockMailer->shouldReceive('to')->andReturnSelf();
    $mockMailer->shouldReceive('queue')->once()->with(Mockery::type(InvoiceReminderMail::class));
    $mockMailService = Mockery::mock(MailService::class);
    $mockMailService->shouldReceive('mailerFor')->once()->andReturn($mockMailer);
    app()->instance(MailService::class, $mockMailService);

    $contact = Contact::factory()->create(['business_id' => $this->business->id, 'email' => 'client@example.com']);
    Invoice::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $contact->id,
        'type' => InvoiceType::INVOICE,
        'status' => InvoiceStatus::SENT,
        'due_date' => now()->subDays(10)->format('Y-m-d'),
        'last_reminder_sent_at' => now()->subDays(8),
    ]);

    $this->artisan('invoice-reminders:send')->assertSuccessful();
});

it('skips an invoice whose contact has no email', function () {
    $mockMailService = Mockery::mock(MailService::class);
    $mockMailService->shouldNotReceive('mailerFor');
    app()->instance(MailService::class, $mockMailService);

    $contact = Contact::factory()->create(['business_id' => $this->business->id, 'email' => null]);
    overdueInvoice($this->business->id, $contact->id);

    $this->artisan('invoice-reminders:send')->assertSuccessful();
});

it('does not send reminders for non-SENT invoices', function () {
    $mockMailService = Mockery::mock(MailService::class);
    $mockMailService->shouldNotReceive('mailerFor');
    app()->instance(MailService::class, $mockMailService);

    $contact = Contact::factory()->create(['business_id' => $this->business->id, 'email' => 'client@example.com']);
    foreach ([InvoiceStatus::DRAFT, InvoiceStatus::PAID, InvoiceStatus::VOID] as $status) {
        Invoice::factory()->create([
            'business_id' => $this->business->id,
            'contact_id' => $contact->id,
            'type' => InvoiceType::INVOICE,
            'status' => $status,
            'due_date' => now()->subDays(3)->format('Y-m-d'),
            'last_reminder_sent_at' => null,
        ]);
    }

    $this->artisan('invoice-reminders:send')->assertSuccessful();
});
