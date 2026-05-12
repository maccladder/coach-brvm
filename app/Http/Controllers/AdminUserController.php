<?php

namespace App\Http\Controllers;

use App\Models\AdminGrant;
use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $q    = trim((string) $request->query('q', ''));
        $sort = $request->query('sort', 'created_at');
        $dir  = $request->query('dir', 'desc');

        $allowedSorts = ['name', 'email', 'created_at', 'marketplace_count', 'marketplace_total'];
        if (!in_array($sort, $allowedSorts, true)) $sort = 'created_at';
        $dir = strtolower($dir) === 'asc' ? 'asc' : 'desc';

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
            ->when($q !== '', fn($query) =>
                $query->where(fn($qq) =>
                    $qq->where('name', 'like', "%{$q}%")
                       ->orWhere('email', 'like', "%{$q}%")
                       ->orWhere('phone', 'like', "%{$q}%")
                )
            )
            ->orderBy($sort, $dir)
            ->paginate(30)
            ->withQueryString();

        $stats = [
            'total'       => User::count(),
            'new7'        => User::where('created_at', '>=', now()->subDays(7))->count(),
            'verified'    => User::whereNotNull('phone_verified_at')->count(),
            'paying'      => User::whereHas('marketplacePurchases', fn($q) => $q->where('status', 'paid'))->count(),
        ];

        return view('admin.users.index', compact('users', 'q', 'sort', 'dir', 'stats'));
    }

    public function show(User $user)
    {
        $user->load([
            'marketplacePurchases.product',
            'coursePurchases.course',
            'adminGrants',
        ]);

        $marketplaceTotal = $user->marketplacePurchases->where('status', 'paid')->sum('amount');
        $courseTotal      = $user->coursePurchases->sum('amount_fcfa');

        // Résoudre les noms des grants
        $grantNames = AdminGrant::resolveItemNames($user->adminGrants);

        return view('admin.users.show', compact(
            'user', 'marketplaceTotal', 'courseTotal', 'grantNames'
        ));
    }
}
