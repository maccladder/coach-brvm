<?php

namespace App\Http\Controllers;

use App\Models\AdminGrant;
use App\Models\Course;
use App\Models\CoursePurchase;
use App\Services\CloudflareStream;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index()
    {
        $courses = Course::where('is_active', true)->latest()->get();
        return view('courses.index', compact('courses'));
    }

    public function myCourses(Request $request)
{
    $userId = $request->user()->id;

    $purchasedIds = CoursePurchase::where('user_id', $userId)
        ->whereNotNull('paid_at')
        ->pluck('course_id');

    $grantedIds = AdminGrant::where('user_id', $userId)
        ->where('grantable_type', Course::class)
        ->active()
        ->pluck('grantable_id');

    $allIds = $purchasedIds->merge($grantedIds)->unique();

    $courses = Course::whereIn('id', $allIds)->latest()->get();

    return view('courses.my', compact('courses'));
}

    public function show(string $slug, Request $request, CloudflareStream $cf)
    {
        $course = Course::where('slug', $slug)->firstOrFail();

        $user = $request->user();

        $hasAccess = $user->coursePurchases()
                ->where('course_id', $course->id)
                ->whereNotNull('paid_at')
                ->exists()
            || $course->isGrantedTo($user);

        abort_unless($hasAccess, 403, "Cours non acheté.");

        $exp = config('services.cloudflare_stream.signed_exp', 3600);
        $token = $cf->createPlaybackToken($course->cf_uid, $exp);

        $iframe = "https://" . config('services.cloudflare_stream.customer_subdomain') .
            "/{$course->cf_uid}/iframe?token={$token}";

        return view('courses.show', compact('course', 'iframe'));
    }
}
