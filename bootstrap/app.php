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
        /**
         * Upload au-delà de post_max_size (PHP) : retour au formulaire avec un message
         * clair plutôt qu'une page 413 brute. La session n'est pas encore démarrée à ce
         * stade, d'où le paramètre d'URL lu par le gestionnaire de fichiers.
         */
        $exceptions->render(function (\Illuminate\Http\Exceptions\PostTooLargeException $e, \Illuminate\Http\Request $request) {
            $message = "Envoi trop volumineux : il dépasse la limite du serveur (" . ini_get('post_max_size')
                . "). Envoie les gros fichiers un par un.";

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 413);
            }

            $referer = (string) $request->headers->get('referer');
            if ($referer !== '' && parse_url($referer, PHP_URL_HOST) === $request->getHost()) {
                $url = strtok($referer, '#');
                return redirect()->to($url . (str_contains($url, '?') ? '&' : '?') . 'upload_trop_gros=1#fichiers');
            }

            return response(
                '<!doctype html><meta charset="utf-8"><title>Envoi trop volumineux</title>'
                . '<p style="font-family:sans-serif;max-width:560px;margin:40px auto;line-height:1.6">'
                . e($message) . '<br><a href="javascript:history.back()">← Revenir au formulaire</a></p>',
                413
            );
        });
    })
    ->create();
