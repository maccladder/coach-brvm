<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Course;
use Illuminate\Http\Request;
use App\Models\CoursePurchase;

class AdminCourseController extends Controller
{
    public function dashboard()
{
    $stats = [
        'courses' => Course::count(),

        'purchases' => CoursePurchase::whereNotNull('paid_at')->count(),

        // ✅ UTILISATEURS ACHETEURS
        'buyers' => User::whereHas('coursePurchases', function ($q) {
            $q->whereNotNull('paid_at');
        })->count(),

        // ✅ CHIFFRE D'AFFAIRES
        'revenue' => CoursePurchase::whereNotNull('paid_at')
            ->sum('amount_fcfa'),
    ];

    return view('admin.courses.dashboard', compact('stats'));
}

    public function users()
    {
        $users = User::withCount('coursePurchases')
            ->latest()
            ->paginate(20);

        return view('admin.courses.users', compact('users'));
    }

    public function index()
    {
        $courses = Course::withCount('purchases')->get();
        return view('admin.courses.index', compact('courses'));
    }

    // 💳 Liste des achats
    public function purchases()
    {
        $purchases = CoursePurchase::with(['user', 'course'])
            ->whereNotNull('paid_at')
            ->latest()
            ->paginate(20);

        return view('admin.courses.purchases', compact('purchases'));
    }

    // 👥 Utilisateurs acheteurs
    public function buyers()
    {
        $users = User::whereHas('coursePurchases', fn ($q) =>
            $q->whereNotNull('paid_at')
        )
        ->withCount(['coursePurchases as purchases_count' => fn ($q) =>
            $q->whereNotNull('paid_at')
        ])
        ->paginate(20);

        return view('admin.courses.buyers', compact('users'));
    }

    // 🎓 Gestion des cours
    public function courses()
    {
        $courses = Course::withCount([
            'purchases as buyers_count' => fn ($q) =>
                $q->whereNotNull('paid_at')
        ])->get();

        return view('admin.courses.index', compact('courses'));
    }

}

