<?php

namespace Modules\Payment\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Modules\Payment\Contracts\MyFatoorahClient;
use Modules\Payment\Contracts\PaymentGateway;
use Modules\Payment\PaymentGatewayManager;
use Modules\Payment\Repositories\EloquentPaymentRepository;
use Modules\Payment\Repositories\PaymentRepository;
use Modules\Payment\Services\LibraryMyFatoorahClient;
use Nwidart\Modules\Support\ModuleServiceProvider;

class PaymentServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Payment';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'payment';

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
     * Register the repository binding and the gateway manager.
     *
     * The manager is populated from config('payment.gateways'), so adding a
     * gateway is a config-only change (Open/Closed Principle).
     */
    public function register(): void
    {
        parent::register();

        $this->app->bind(PaymentRepository::class, EloquentPaymentRepository::class);

        // MyFatoorah integration (official myfatoorah/laravel-package), wrapped
        // behind a swappable contract so the gateway stays testable.
        $this->app->bind(MyFatoorahClient::class, LibraryMyFatoorahClient::class);

        $this->app->singleton(PaymentGatewayManager::class, function (): PaymentGatewayManager {
            $manager = new PaymentGatewayManager;
            $credentials = (array) config('payment.credentials', []);

            foreach ((array) config('payment.gateways', []) as $gatewayClass) {
                /** @var PaymentGateway $gateway */
                $gateway = new $gatewayClass;
                $scoped = $credentials[$gateway->key()] ?? [];

                // Re-instantiate with its scoped credentials when any are configured.
                $manager->register($scoped === [] ? $gateway : new $gatewayClass($scoped));
            }

            return $manager;
        });
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
