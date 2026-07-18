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

            // ✅ Wallet (déjà)
            'payments/cinetpay/ipn',
            'payments/cinetpay/return',

            // ✅ Paystack
            'paystack/webhook',

            // ✅ Marketplace (NOUVEAU)
            'marketplace/payment/cinetpay/notify',
            'marketplace/payment/cinetpay/return',

            // ✅ Cours (NOUVEAU)
            'paiement/cinetpay/ipn',     // POST IPN cours
            'payment/cinetpay/return',   // GET/POST return cours

            // ✅ News (n8n webhook, server-to-server, auth par clé API)
            'api/n8n/news',

            // ✅ BOC (n8n webhook, server-to-server, auth par clé API)
            'api/n8n/bocs',

            // ✅ Comparateur (n8n webhook, server-to-server, auth par clé API)
            'api/n8n/comparateur',

            // ✅ Bet (webhooks n8n coupons de paris, server-to-server, auth par token)
            'api/bet/*',
        ]);

        /**
         * 🔐 Middleware Admin (code secret) + 🛍️ Vendor mode
         */
        $middleware->alias([
            'admin.code'      => \App\Http\Middleware\AdminCodeMiddleware::class,
            'stagiaire.code'  => \App\Http\Middleware\StagiaireCodeMiddleware::class,
            'stagiaire.log'   => \App\Http\Middleware\StagiaireActivityLogger::class,
            'vendor.mode'      => \App\Http\Middleware\EnsureVendorMode::class,
            'lettreci.access'  => \App\Http\Middleware\EnsureLettreCIAccess::class,
            'affiliate.active' => \App\Http\Middleware\EnsureAffiliateActive::class,
            'n8n.key'          => \App\Http\Middleware\N8nApiKeyMiddleware::class,
        ]);

        // $middleware->append(\App\Http\Middleware\TestMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
