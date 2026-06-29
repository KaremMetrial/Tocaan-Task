<?php

namespace Modules\Order\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Modules\Order\Models\Order;
use Modules\Order\Policies\OrderPolicy;
use Modules\Order\Repositories\EloquentOrderRepository;
use Modules\Order\Repositories\OrderRepository;
use Nwidart\Modules\Support\ModuleServiceProvider;

class OrderServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Order';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'order';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    // protected array $commands = [];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    /**
     * Bind the order repository contract to its Eloquent implementation (DIP)
     * and register the authorization policy for orders.
     */
    public function register(): void
    {
        parent::register();

        $this->app->bind(OrderRepository::class, EloquentOrderRepository::class);
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Order::class, OrderPolicy::class);
    }

    /**
     * Define module schedules.
     *
     * @param  $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }
}
