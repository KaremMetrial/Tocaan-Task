<?php

namespace Modules\Order\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Order\Events\OrderCreated;
use Modules\Order\Listeners\LogOrderCreated;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        OrderCreated::class => [
            LogOrderCreated::class,
        ],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * Disabled: module listeners are registered explicitly above. Auto-discovery
     * only scans the application's app/Listeners directory, not module ones.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = false;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}
