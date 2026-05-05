<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendAdminGrantNotificationJob;
use App\Models\AdminGrant;
use App\Models\Course;
use App\Models\Document;
use App\Models\MarketplaceProduct;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminGrantController extends Controller
{
    /** Types exposés dans l'UI admin → classe Eloquent ou 'lettreci' */
    private const TYPE_MAP = [
        'course'      => Course::class,
        'marketplace' => MarketplaceProduct::class,
        'document'    => Document::class,
        'lettreci'    => 'lettreci',
    ];

    private const TYPE_LABELS = [
        'course'      => 'Formation',
        'marketplace' => 'Produit marketplace',
        'document'    => 'Document',
        'lettreci'    => 'LettreCI',
    ];

    /* ================================================================
     | Index
     ================================================================ */

    public function index(Request $request)
    {
        $q = AdminGrant::query()
            ->with(['user:id,name,email', 'grantedBy:id,name', 'revokedBy:id,name'])
            ->when($request->filled('status'), function ($query) use ($request) {
                match ($request->status) {
                    'active'  => $query->active(),
                    'revoked' => $query->whereNotNull('revoked_at'),
                    'expired' => $query->expired(),
                    default   => null,
                };
            })
            ->when($request->filled('type') && isset(self::TYPE_MAP[$request->type]), function ($query) use ($request) {
                $query->where('grantable_type', self::TYPE_MAP[$request->type]);
            })
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = $request->q;
                $query->whereHas('user', function ($sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%");
                });
            });

        $grants = $q->latest()->paginate(25)->withQueryString();

        // Résolution des noms d'items sans déclencher la relation morphTo
        $itemNames = AdminGrant::resolveItemNames($grants->getCollection());

        $typeLabels = self::TYPE_LABELS;

        return view('admin.grants.index', compact('grants', 'itemNames', 'typeLabels'));
    }

    /* ================================================================
     | Store
     ================================================================ */

    public function store(Request $request)
    {
        $request->validate([
            'user_id'    => ['required', 'integer', 'exists:users,id'],
            'type'       => ['required', 'in:course,marketplace,document,lettreci'],
            'item_id'    => ['required_unless:type,lettreci', 'nullable', 'integer'],
            'reason'     => ['nullable', 'string', 'max:500'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $type          = $request->type;
        $grantableType = self::TYPE_MAP[$type];
        $grantableId   = $type === 'lettreci' ? 0 : (int) $request->item_id;

        // Vérification de l'item si pas LettreCI
        if ($type !== 'lettreci') {
            $item = $grantableType::find($grantableId);
            if (!$item) {
                return back()->withErrors(['item_id' => 'Item introuvable.']);
            }
        }

        // Doublon : refuser si un grant actif existe déjà
        $existing = AdminGrant::where('user_id', $request->user_id)
            ->where('grantable_type', $grantableType)
            ->where('grantable_id', $grantableId)
            ->active()
            ->first();

        if ($existing) {
            return back()->with('error', 'Cet utilisateur a déjà un accès actif à cet item.');
        }

        $adminId = session('admin_user_id') ?? User::first()->id;

        $grant = AdminGrant::create([
            'user_id'        => $request->user_id,
            'grantable_type' => $grantableType,
            'grantable_id'   => $grantableId,
            'reason'         => $request->reason,
            'granted_by'     => $adminId,
            'granted_at'     => now(),
            'expires_at'     => $request->expires_at,
        ]);

        Log::info('AdminGrant created', [
            'grant_id'       => $grant->id,
            'user_id'        => $grant->user_id,
            'grantable_type' => $grantableType,
            'grantable_id'   => $grantableId,
            'by_admin'       => $adminId,
        ]);

        // Notification email en queue
        try {
            SendAdminGrantNotificationJob::dispatch($grant);
        } catch (\Throwable $e) {
            Log::error('AdminGrant notification dispatch failed', ['error' => $e->getMessage()]);
        }

        return back()->with('success', 'Attribution créée avec succès. Un email de notification a été envoyé.');
    }

    /* ================================================================
     | Revoke
     ================================================================ */

    public function revoke(Request $request, AdminGrant $grant)
    {
        $request->validate([
            'revoke_reason' => ['required', 'string', 'max:500'],
        ]);

        if (!$grant->isActive()) {
            return back()->with('error', 'Ce grant n\'est pas actif.');
        }

        $adminId = session('admin_user_id') ?? User::first()->id;
        $admin   = User::find($adminId);

        $grant->revoke($admin, $request->revoke_reason);

        Log::info('AdminGrant revoked', [
            'grant_id'  => $grant->id,
            'user_id'   => $grant->user_id,
            'by_admin'  => $adminId,
            'reason'    => $request->revoke_reason,
        ]);

        return back()->with('success', 'Attribution révoquée. L\'accès est immédiatement coupé.');
    }

    /* ================================================================
     | Restore
     ================================================================ */

    public function restore(AdminGrant $grant)
    {
        if ($grant->isActive()) {
            return back()->with('error', 'Ce grant est déjà actif.');
        }

        $grant->restore();

        $adminId = session('admin_user_id') ?? User::first()->id;

        Log::info('AdminGrant restored', [
            'grant_id' => $grant->id,
            'user_id'  => $grant->user_id,
            'by_admin' => $adminId,
        ]);

        return back()->with('success', 'Attribution restaurée.');
    }

    /* ================================================================
     | AJAX : recherche d'utilisateurs
     ================================================================ */

    public function userSearch(Request $request)
    {
        $q = trim((string) $request->get('q'));

        $users = User::query()
            ->when($q, fn ($query) =>
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%")
            )
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name', 'email']);

        return response()->json($users);
    }

    /* ================================================================
     | AJAX : items par type
     ================================================================ */

    public function items(Request $request)
    {
        $type = $request->get('type');

        if ($type === 'lettreci') {
            return response()->json([]);
        }

        if (!isset(self::TYPE_MAP[$type]) || self::TYPE_MAP[$type] === 'lettreci') {
            return response()->json([]);
        }

        $class = self::TYPE_MAP[$type];

        $items = match ($type) {
            'course'      => Course::where('is_active', true)->orderBy('title')->get(['id', 'title']),
            'marketplace' => MarketplaceProduct::published()->orderBy('title')->get(['id', 'title', 'type']),
            'document'    => Document::active()->orderBy('title')->get(['id', 'title', 'type']),
            default       => collect(),
        };

        return response()->json($items);
    }
}
