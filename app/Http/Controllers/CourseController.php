<?php

namespace App\Http\Controllers;

use App\Models\Course;
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

    $courses = Course::query()
        ->whereHas('purchases', function ($q) use ($userId) {
            $q->where('user_id', $userId)
              ->whereNotNull('paid_at');
        })
        ->latest()
        ->get();

    return view('courses.my', compact('courses'));
}

    public function show(string $slug, Request $request, CloudflareStream $cf)
    {
        $course = Course::where('slug', $slug)->firstOrFail();

        $hasAccess = $request->user()->coursePurchases()
            ->where('course_id', $course->id)
            ->whereNotNull('paid_at')
            ->exists();

        abort_unless($hasAccess, 403, "Cours non acheté.");

        $exp = config('services.cloudflare_stream.signed_exp', 3600);
        $token = $cf->createPlaybackToken($course->cf_uid, $exp);

        $iframe = "https://" . config('services.cloudflare_stream.customer_subdomain') .
            "/{$course->cf_uid}/iframe?token={$token}";

        return view('courses.show', compact('course', 'iframe'));
    }
}
