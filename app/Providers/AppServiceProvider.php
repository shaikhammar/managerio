<?php

namespace App\Providers;

use App\Events\BusinessCreated;
use App\Events\InvoicePosted;
use App\Events\JournalEntryPosted;
use App\Events\PaymentReceived;
use App\Events\QuoteRespondedFromPortal;
use App\Listeners\InvalidateReportCache;
use App\Listeners\SendQuotePortalResponseNotification;
use App\Listeners\SendWelcomeEmail;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerEventListeners();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(function () {
            $rule = Password::min(12)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols();

            return app()->isProduction()
                ? $rule->uncompromised()
                : $rule;
        });
    }

    /**
     * Register domain event → listener mappings.
     */
    protected function registerEventListeners(): void
    {
        // Invalidate cached reports whenever financial data changes.
        Event::listen(InvoicePosted::class, InvalidateReportCache::class);
        Event::listen(PaymentReceived::class, InvalidateReportCache::class);
        Event::listen(JournalEntryPosted::class, InvalidateReportCache::class);

        // Send a welcome email when a new business workspace is created.
        Event::listen(BusinessCreated::class, SendWelcomeEmail::class);

        // Notify the business owner when a client responds to a quote via the portal.
        Event::listen(QuoteRespondedFromPortal::class, SendQuotePortalResponseNotification::class);
    }
}
