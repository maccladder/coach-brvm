<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $sort = $request->query('sort', 'created_at');
        $dir  = $request->query('dir', 'desc');

        // whitelist anti injection
        $allowedSorts = ['name', 'email', 'created_at'];
        if (!in_array($sort, $allowedSorts, true)) $sort = 'created_at';

        $dir = strtolower($dir) === 'asc' ? 'asc' : 'desc';

        $users = User::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('name', 'like', "%{$q}%")
                       ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->orderBy($sort, $dir)
            ->paginate(20)
            ->withQueryString();

        $totalUsers = User::count();
        $newLast7Days = User::where('created_at', '>=', now()->subDays(7))->count();

        return view('admin.users.index', compact('users', 'q', 'sort', 'dir', 'totalUsers', 'newLast7Days'));
    }
}
