<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ClientePortal;
use App\Http\Middleware\ResolverEmpresaPanel;
use App\Http\Middleware\ResolverEmpresaPortal;
use App\Models\Empresa;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            // El portal (/{slug}) va el último para no tapar ninguna ruta de la aplicación.
            Route::middleware('web')->group(base_path('routes/portal.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->alias([
            'empresa.panel' => ResolverEmpresaPanel::class,
            'empresa.portal' => ResolverEmpresaPortal::class,
            'cliente.portal' => ClientePortal::class,
            'admin' => \App\Http\Middleware\SoloAdmin::class,
        ]);

        // La empresa actual debe fijarse antes de resolver los modelos de la URL,
        // para que el filtro por empresa se aplique también al route model binding.
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: ResolverEmpresaPortal::class);
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: ResolverEmpresaPanel::class);

        // El límite de tamaño se comprueba con la sesión ya abierta, para poder volver al formulario con el error.
        $middleware->remove(\Illuminate\Http\Middleware\ValidatePostSize::class);

        $middleware->web(append: [
            \Illuminate\Http\Middleware\ValidatePostSize::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Envío mayor que post_max_size: vuelve al formulario con un error en vez de un 413 en bruto.
        $exceptions->render(function (\Illuminate\Http\Exceptions\PostTooLargeException $e, Request $request) {
            if (! $request->expectsJson()) {
                return back()->withErrors(['adjuntos' => 'Los archivos son demasiado grandes en total. Envía menos fotos o más ligeras (máximo 5 MB cada una).']);
            }
        });

        // Enlace mágico caducado o manipulado: página amable en lugar de un 403.
        $exceptions->render(function (InvalidSignatureException $e, Request $request) {
            $empresa = Empresa::where('slug', $request->segment(1))->first();

            return Inertia::render('portal/enlace-caducado', [
                'portal' => $empresa ? ResolverEmpresaPortal::datosPortal($empresa) : null,
            ])->toResponse($request);
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
