<?php

namespace App\Providers;

use App\Events\BillingAiFeatureUsed;
use App\Events\BillingPaymentCompleted;
use App\Events\BillingSubscriptionActivated;
use App\Listeners\LogBillingEventActivity;
use App\Listeners\SendBillingPaymentCompletedNotification;
use App\Listeners\SendBillingSubscriptionActivatedNotification;
use App\Services\Billing\Contracts\PaymentGatewayInterface;
use App\Services\Billing\MomoGatewayService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGatewayInterface::class, MomoGatewayService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(BillingPaymentCompleted::class, SendBillingPaymentCompletedNotification::class);
        Event::listen(BillingSubscriptionActivated::class, SendBillingSubscriptionActivatedNotification::class);
        Event::listen(BillingPaymentCompleted::class, LogBillingEventActivity::class);
        Event::listen(BillingSubscriptionActivated::class, LogBillingEventActivity::class);
        Event::listen(BillingAiFeatureUsed::class, LogBillingEventActivity::class);
    }
}
