<?php

namespace App\Http\Controllers;

use App\Models\AdminGrant;
use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $q      = trim((string) $request->query('q', ''));
        $sort   = $request->query('sort', 'created_at');
        $dir    = $request->query('dir', 'desc');
        $filter = $request->query('filter', '');

        $allowedSorts = ['name', 'email', 'created_at', 'marketplace_count', 'marketplace_total', 'course_count'];
        if (!in_array($sort, $allowedSorts, true)) $sort = 'created_at';
        $dir = strtolower($dir) === 'asc' ? 'asc' : 'desc';

        $allowedFilters = ['paying', 'has_phone', 'phone_verified', 'unpaid_attempt', 'never_bought', 'reward_claimed', 'new30'];
        if (!in_array($filter, $allowedFilters, true)) $filter = '';

        $users = User::query()
            ->withCount([
                'marketplacePurchases as marketplace_count' => fn($q) => $q->where('status', 'paid'),
                'coursePurchases as course_count'           => fn($q) => $q->whereNotNull('paid_at'),
                'adminGrants as grants_count'               => fn($q) => $q->whereNull('revoked_at'),
            ])
            ->withSum(
                ['marketplacePurchases as marketplace_total' => fn($q) => $q->where('status', 'paid')],
                'amount'
            )
            // Recherche texte
            ->when($q !== '', fn($query) =>
                $query->where(fn($qq) =>
                    $qq->where('name', 'like', "%{$q}%")
                       ->orWhere('email', 'like', "%{$q}%")
                       ->orWhere('phone', 'like', "%{$q}%")
                )
            )
            // Filtres rapides
            ->when($filter === 'paying', fn($query) =>
                $query->where(fn($qq) =>
                    $qq->whereHas('marketplacePurchases', fn($q) => $q->where('status', 'paid'))
                       ->orWhereHas('coursePurchases', fn($q) => $q->whereNotNull('paid_at'))
                )
            )
            ->when($filter === 'has_phone',       fn($query) => $query->whereNotNull('phone'))
            ->when($filter === 'phone_verified',  fn($query) => $query->whereNotNull('phone_verified_at'))
            ->when($filter === 'reward_claimed',  fn($query) => $query->where('phone_reward_claimed', true))
            ->when($filter === 'unpaid_attempt',  fn($query) =>
                $query->whereHas('coursePurchases', fn($q) => $q->whereNull('paid_at'))
            )
            ->when($filter === 'never_bought', fn($query) =>
                $query->whereDoesntHave('marketplacePurchases', fn($q) => $q->where('status', 'paid'))
                      ->whereDoesntHave('coursePurchases', fn($q) => $q->whereNotNull('paid_at'))
                      ->whereDoesntHave('adminGrants', fn($q) => $q->whereNull('revoked_at'))
            )
            ->when($filter === 'new30', fn($query) =>
                $query->where('created_at', '>=', now()->subDays(30))
            )
            ->orderBy($sort, $dir)
            ->paginate(30)
            ->withQueryString();

        $stats = [
            'total'          => User::count(),
            'new7'           => User::where('created_at', '>=', now()->subDays(7))->count(),
            'phone_verified' => User::whereNotNull('phone_verified_at')->count(),
            'paying'         => User::where(fn($q) =>
                                    $q->whereHas('marketplacePurchases', fn($q) => $q->where('status', 'paid'))
                                      ->orWhereHas('coursePurchases', fn($q) => $q->whereNotNull('paid_at'))
                                )->count(),
            'has_phone'      => User::whereNotNull('phone')->count(),
            'unpaid_attempt' => User::whereHas('coursePurchases', fn($q) => $q->whereNull('paid_at'))->count(),
            'never_bought'   => User::whereDoesntHave('marketplacePurchases', fn($q) => $q->where('status', 'paid'))
                                    ->whereDoesntHave('coursePurchases', fn($q) => $q->whereNotNull('paid_at'))
                                    ->whereDoesntHave('adminGrants', fn($q) => $q->whereNull('revoked_at'))
                                    ->count(),
            'reward_claimed' => User::where('phone_reward_claimed', true)->count(),
        ];

        return view('admin.users.index', compact('users', 'q', 'sort', 'dir', 'filter', 'stats'));
    }

    public function show(User $user)
    {
        $user->load([
            'marketplacePurchases.product',
            'coursePurchases.course',
            'adminGrants',
        ]);

        $marketplaceTotal = $user->marketplacePurchases->where('status', 'paid')->sum('amount');
        $courseTotal      = $user->coursePurchases->whereNotNull('paid_at')->sum('amount_fcfa');

        $grantNames = AdminGrant::resolveItemNames($user->adminGrants);

        return view('admin.users.show', compact(
            'user', 'marketplaceTotal', 'courseTotal', 'grantNames'
        ));
    }
}
