<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        /**
         * Désactiver CSRF pour CinetPay
         * Laravel 11+ utilise une nouvelle méthode :
         * on utilise skipWhen() pour ignorer CSRF sur certaines routes.
         */
        $middleware->validateCsrfTokens(except: [
            'client-bocs/payment/notify',
            'client-bocs/payment/return/*',


             // 🔽 nouveaux pour les états financiers
    'client-financials/payment/notify',
    'client-financials/*/payment-return',
        ]);

        // SI tu veux ajouter un middleware custom un jour :
        // $middleware->append(\App\Http\Middleware\TestMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
