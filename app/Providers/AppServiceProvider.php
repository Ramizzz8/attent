<?php

namespace App\Providers;

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
        $this->app->scoped(\App\Tenancy\EmpresaActual::class);
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Event::listen(\App\Events\TicketEstadoCambiado::class, [\App\Listeners\AvisarPorEmail::class, 'estadoCambiado']);
        \Illuminate\Support\Facades\Event::listen(\App\Events\MensajeCreado::class, [\App\Listeners\AvisarPorEmail::class, 'mensajeCreado']);
        \Illuminate\Support\Facades\Event::listen(\App\Events\TicketEstadoCambiado::class, [\App\Listeners\AvisarEnTiempoReal::class, 'estadoCambiado']);
        \Illuminate\Support\Facades\Event::listen(\App\Events\MensajeCreado::class, [\App\Listeners\AvisarEnTiempoReal::class, 'mensajeCreado']);

        $this->configureDefaults();
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

        Password::defaults(fn (): ?Password => app()->isProduction()
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
