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
         * Désactiver CSRF pour les callbacks de paiement (server-to-server) + retours
         * Laravel 11+ : validateCsrfTokens(except: [...])
         */
        $middleware->validateCsrfTokens(except: [
            // ✅ anciens
            'client-bocs/payment/notify',
            'client-bocs/payment/return/*',

            'client-financials/payment/notify',
            'client-financials/payment/return/*',

            // ✅ NOUVEAU : portefeuille virtuel (CinetPay)
            'payments/cinetpay/ipn',     // POST callback serveur
            'payments/cinetpay/return',  // GET retour utilisateur
        ]);

        /**
         * 🔐 Middleware Admin (code secret)
         */
        $middleware->alias([
            'admin.code' => \App\Http\Middleware\AdminCodeMiddleware::class,
        ]);

        // $middleware->append(\App\Http\Middleware\TestMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
