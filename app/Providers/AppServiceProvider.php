<?php

namespace App\Providers;

use App\Services\PaymentService;
use App\Services\RefundService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PaymentService::class, function () {
            $drivers = [];
            foreach (config('gateways.drivers') as $key => $config) {
                $drivers[$key] = app($config['class']);
            }

            return new PaymentService($drivers);
        });

        $this->app->singleton(RefundService::class, function () {
            $drivers = [];
            foreach (config('gateways.drivers') as $key => $config) {
                $drivers[$key] = app($config['class']);
            }

            return new RefundService($drivers);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     * 
     * @codeCoverageIgnore
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(
            fn (): ?Password => app()->isProduction()
                ? Password::min(12)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
                : null,
        );
    }
}
