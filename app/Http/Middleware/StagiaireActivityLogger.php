<?php

namespace App\Http\Middleware;

use App\Models\StagiaireLog;
use Closure;
use Illuminate\Http\Request;

class StagiaireActivityLogger
{
    // Routes GET à logger (les vues importantes, pas toutes)
    private const WATCHED_GET_ROUTES = [
        'admin.marketplace.show'     => 'view_product',
        'admin.marketplace.index'    => 'view_marketplace_list',
        'admin.documents.index'      => 'view_documents',
        'admin.announcements.index'  => 'view_announcements',
        'admin.users.index'          => 'view_users',
        'admin.dashboard'            => 'view_dashboard',
        'admin.courses.index'        => 'view_courses',
        'admin.topups.index'         => 'view_topups',
        'admin.bocs.index'           => 'view_bocs',
    ];

    // Labels lisibles pour les actions POST/PUT/DELETE
    private const ACTION_LABELS = [
        'admin.marketplace.approve'  => ['approve_product',  'A approuvé un produit marketplace'],
        'admin.marketplace.update'   => ['update_product',   'A modifié un produit marketplace'],
        'admin.marketplace.store'    => ['create_product',   'A créé un produit marketplace'],
        'admin.announcements.store'  => ['create_announce',  'A créé une annonce'],
        'admin.announcements.update' => ['update_announce',  'A modifié une annonce'],
        'admin.documents.store'      => ['create_document',  'A créé un document'],
        'admin.documents.update'     => ['update_document',  'A modifié un document'],
        'admin.documents.approve'    => ['approve_document', 'A approuvé un document'],
        'admin.bocs.store'           => ['upload_boc',       'A uploadé une BOC'],
    ];

    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    public function terminate(Request $request, $response): void
    {
        // Ne logger que les sessions stagiaire (pas admin)
        if (!session('is_stagiaire') || session('is_admin')) {
            return;
        }

        // Réponses échouées (4xx/5xx) : ignorer
        if ($response->getStatusCode() >= 400) {
            return;
        }

        $routeName = $request->route()?->getName() ?? '';
        $method    = $request->method();

        // POST / PUT / PATCH / DELETE → toujours logger si dans la liste
        if ($method !== 'GET') {
            if (isset(self::ACTION_LABELS[$routeName])) {
                [$action, $label] = self::ACTION_LABELS[$routeName];
                StagiaireLog::record($action, $label, $routeName, $method, $request->fullUrl());
            }
            return;
        }

        // GET → logger uniquement les routes surveillées
        if (isset(self::WATCHED_GET_ROUTES[$routeName])) {
            $action = self::WATCHED_GET_ROUTES[$routeName];
            StagiaireLog::record($action, null, $routeName, 'GET', $request->fullUrl());
        }
    }
}
