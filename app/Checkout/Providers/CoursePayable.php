<?php

namespace App\Checkout\Providers;

use App\Checkout\CheckoutRedirect;
use App\Checkout\Payable;
use App\Models\Course;
use App\Models\CoursePurchase;
use App\Models\Payment;
use App\Models\User;
use App\Services\Affiliate\AffiliateCommissionService;
use App\Services\Payments\PaystackCourseService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CoursePayable extends BasePayable
{
    public function __construct(
        private AffiliateCommissionService $affiliateService,
        private PaystackCourseService $courseService,
    ) {}

    public function resolve(Request $request, ?User $user): Payable
    {
        $course = Course::find((int) $request->input('course'));

        if (!$course) {
            throw CheckoutRedirect::to(route('courses.index'), 'error', 'Formation introuvable.');
        }

        $already = CoursePurchase::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->whereNotNull('paid_at')
            ->exists();

        if ($already) {
            throw CheckoutRedirect::to(route('courses.show', $course->slug), 'success', 'Cours déjà acheté ✅');
        }

        $checkout = $this->affiliateService->resolveForCheckout($request, 'course', $course->id, (int) $course->price_fcfa);

        return new Payable(
            type: 'course',
            itemKey: 'course:' . $course->id,
            title: $course->title,
            amount: (int) $checkout['amount'],
            email: $user->email,
            successUrl: route('courses.my'),
            successLabel: 'Commencer la formation',
            badges: ['Accès à vie', 'Vidéos et supports'],
            params: array_filter([
                'course'         => $course->id,
                'affiliate_code' => $request->input('affiliate_code'),
            ]),
            icon: '🎓',
            context: [
                'course'         => $course,
                'affiliate_code' => $checkout['code'] ?? null,
                'metadata'       => ['course_id' => $course->id],
            ],
        );
    }

    public function createPending(Payable $payable, ?User $user): string
    {
        /** @var Course $course */
        $course        = $payable->context['course'];
        $affiliateCode = $payable->context['affiliate_code'];

        // Identique à CoursePaymentController::buyPaystack
        $purchase = CoursePurchase::firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            ['amount_fcfa' => (int) $course->price_fcfa]
        );

        $reference = 'COURSE-' . $course->id . '-' . $user->id . '-' . Str::upper(Str::random(10));

        Payment::create([
            'user_id'               => $user->id,
            'transaction_id'        => $reference,
            'amount_paid'           => $payable->amount,
            'amount_virtual'        => 0,
            'purpose'               => 'course',
            'status'                => 'PENDING',
            'credited_at'           => null,
            'affiliate_code'        => $affiliateCode,
            'affiliate_base_amount' => $affiliateCode ? (int) $course->price_fcfa : null,
            'meta'                  => [
                'type'        => 'course',
                'course_id'   => $course->id,
                'purchase_id' => $purchase->id,
                'provider'    => 'paystack',
                'channel'     => 'mobile_money_checkout',
            ],
        ]);

        return $reference;
    }

    public function validate(array $data): void
    {
        $this->courseService->handleChargeSuccess($data);
    }

    public function isPaid(string $reference): bool
    {
        return $this->paymentIsPaid($reference);
    }

    public function classicForm(Payable $payable): array
    {
        return [
            'action' => route('courses.buy.paystack', $payable->context['course']),
            'fields' => array_filter([
                'classic'        => 1,
                'affiliate_code' => $payable->params['affiliate_code'] ?? null,
            ]),
        ];
    }
}
