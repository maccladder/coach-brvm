<?php

namespace App\Http\Controllers;

use App\Mail\AffiliateActivatedMail;
use App\Mail\AffiliateRequestAdminMail;
use App\Mail\AffiliateRequestConfirmMail;
use App\Models\AdminNotification;
use App\Models\Affiliate;
use App\Models\AffiliateClick;
use App\Models\AffiliateCommission;
use App\Models\AffiliatePayoutRequest;
use App\Models\Course;
use App\Models\MarketplaceProduct;
use App\Services\Affiliate\AffiliateCodeService;
use App\Services\Affiliate\AffiliateCommissionService;
use App\Services\Affiliate\AffiliateEarningsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AffiliateController extends Controller
{
    // ─── Page "Devenir apporteur" ─────────────────────────────────────────────

    public function landing(Request $request)
    {
        $user = $request->user();

        if ($user && $user->affiliate) {
            if ($user->affiliate->status === 'active') {
                return redirect()->route('affiliate.dashboard');
            }
        }

        return view('affiliate.landing');
    }

    // ─── Soumettre la demande ─────────────────────────────────────────────────

    public function store(Request $request, AffiliateCodeService $codeService)
    {
        $user = $request->user();

        if ($user->affiliate) {
            return redirect()->route('affiliate.dashboard')
                ->with('warning', 'Tu as déjà un compte apporteur.');
        }

        $useCustom  = $request->boolean('use_custom_code');
        $rawCode    = strtoupper(trim((string) $request->input('custom_code', '')));

        if ($useCustom && $rawCode !== '') {
            $errors = $codeService->validateCustom($rawCode);
            if (!empty($errors)) {
                return back()
                    ->withErrors(['custom_code' => $errors])
                    ->withInput();
            }
            $code     = $rawCode;
            $isCustom = true;
        } else {
            $code     = $codeService->generate();
            $isCustom = false;
        }

        $requireApproval = (bool) config('affiliate.require_approval', true);
        $status          = $requireApproval ? 'pending' : 'active';

        try {
            $affiliate = Affiliate::create([
                'user_id'      => $user->id,
                'code'         => $code,
                'custom_code'  => $isCustom,
                'status'       => $status,
                'requested_at' => now(),
                'activated_at' => $requireApproval ? null : now(),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Race condition : code pris entre validation et INSERT
            if (str_contains($e->getMessage(), 'Duplicate entry')
                || str_contains($e->getMessage(), 'UNIQUE constraint')) {
                return back()
                    ->withErrors(['custom_code' => ['Ce code est déjà pris. Choisis-en un autre.']])
                    ->withInput();
            }
            throw $e;
        }

        // Mise à jour du cache sur users (affiliates.status est la source de vérité)
        $user->is_affiliate     = true;
        $user->affiliate_status = $status;
        $user->save();

        $adminEmails = config('affiliate.admin_emails', []);

        if ($requireApproval) {
            // ── Notification in-app admin ─────────────────────────────────────
            AdminNotification::create([
                'type'    => 'affiliate_requested',
                'title'   => '🤝 Nouvelle demande apporteur',
                'message' => ($user->name ?? 'Un utilisateur') . ' — code souhaité : ' . $code,
                'url'     => route('admin.affiliates.index'),
                'read_at' => null,
            ]);

            // ── Email admin (non bloquant) ────────────────────────────────────
            try {
                Mail::to($adminEmails)->send(new AffiliateRequestAdminMail($user, $code));
            } catch (\Throwable $e) {
                Log::error('AffiliateController: email admin échec', [
                    'user_id' => $user->id, 'error' => $e->getMessage(),
                ]);
            }

            // ── Email confirmation au demandeur (non bloquant) ────────────────
            try {
                Mail::to($user->email)->send(new AffiliateRequestConfirmMail($user));
            } catch (\Throwable $e) {
                Log::error('AffiliateController: email demandeur échec', [
                    'user_id' => $user->id, 'error' => $e->getMessage(),
                ]);
            }

            return redirect()->route('affiliate.landing')
                ->with('success', '✅ Demande envoyée ! Ton code apporteur sera activé après validation par l\'équipe.');
        }

        // Auto-activation (require_approval = false) — email activation immédiat
        try {
            $affiliate->load('user');
            Mail::to($user->email)->send(new AffiliateActivatedMail($affiliate));
        } catch (\Throwable $e) {
            Log::error('AffiliateController: email activation auto échec', [
                'affiliate_id' => $affiliate->id, 'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('affiliate.dashboard')
            ->with('success', '🎉 Bienvenue ! Ton code apporteur est actif : ' . $code);
    }

    // ─── Endpoint JSON : validation code en temps réel (aperçu checkout) ────────
    // Même logique que le checkout réel via checkCode() — jamais de divergence possible.

    public function validateCode(Request $request, AffiliateCommissionService $service): JsonResponse
    {
        $validated = $request->validate([
            'code'         => ['required', 'string', 'max:20'],
            'product_type' => ['required', 'in:pack_brvm,course,marketplace'],
            'product_id'   => ['nullable', 'integer', 'min:1'],
        ]);

        $code      = strtoupper(trim($validated['code']));
        $purpose   = $validated['product_type'];
        $productId = isset($validated['product_id']) ? (int) $validated['product_id'] : null;

        // ── Résolution du prix de référence ──────────────────────────────────
        $basePrice = match($purpose) {
            'pack_brvm'   => (int) config('affiliate.pack_brvm_price', 30000),
            'course'      => $productId
                                ? (int) Course::where('id', $productId)->value('price_fcfa')
                                : 0,
            'marketplace' => $productId
                                ? (int) MarketplaceProduct::where('id', $productId)->value('price')
                                : 0,
            default       => 0,
        };

        if ($basePrice <= 0) {
            return response()->json([
                'applicable'     => false,
                'reason'         => 'product_not_found',
                'original_price' => 0,
                'new_price'      => 0,
                'discount_amount'=> 0,
                'discount_rate'  => 0,
                'affiliate_label'=> null,
                'message'        => 'Produit introuvable.',
            ], 404);
        }

        // ── Validation via le même noyau que le checkout ──────────────────────
        $result = $service->checkCode($code, (int) $request->user()->id, $purpose, $productId, $basePrice);

        $discountRate = (float) config('affiliate.discount_rate', 0.10);

        $message = match(true) {
            $result['eligible']             => null, // rempli côté JS
            $result['reason'] === 'self_referral' => 'Ce code est le vôtre — il ne s\'applique pas à vos propres achats.',
            $result['reason'] === 'not_eligible'  => 'Ce code ne s\'applique pas à ce produit.',
            default                               => 'Code invalide ou expiré.',
        };

        return response()->json([
            'applicable'      => $result['eligible'],
            'reason'          => $result['reason'],
            'original_price'  => $basePrice,
            'new_price'       => $result['amount'],
            'discount_amount' => $basePrice - $result['amount'],
            'discount_rate'   => $discountRate,
            'affiliate_label' => $result['affiliate_label'], // prénom uniquement
            'message'         => $message,
        ]);
    }

    // ─── Route publique /r/{code} — pose le cookie + redirige ───────────────
    // Paramètres de destination optionnels (whitelist serveur — pas d'open redirect) :
    //   ?p=pack                → page Pack BRVM
    //   ?course={slug}         → page formation (affiliate_eligible = true requis)
    //   ?product={slug}        → page produit marketplace Boursiv éligible
    // Toute valeur inconnue ou invalide → fallback accueil.

    public function redirect(Request $request, string $code)
    {
        $code      = strtoupper(trim($code));
        $affiliate = Affiliate::where('code', $code)->where('status', 'active')->first();

        if (!$affiliate) {
            return redirect('/');
        }

        // Enregistrer le clic (commun quelle que soit la destination)
        AffiliateClick::create([
            'affiliate_id' => $affiliate->id,
            'ip'           => $request->ip(),
            'user_agent'   => $request->userAgent(),
            'created_at'   => now(),
        ]);

        $affiliate->increment('click_count');

        Cookie::queue('affiliate_code', $code, 60 * 24 * 30);

        // ── Résolution de destination (whitelist serveur) ─────────────────────
        $destination = $this->resolveDestination($request);

        return redirect($destination);
    }

    /**
     * Résout la destination depuis les paramètres de la requête.
     * Whitelist stricte — aucune URL libre acceptée.
     */
    private function resolveDestination(Request $request): string
    {
        // ?p=pack → page Pack BRVM (toujours éligible)
        if ($request->query('p') === 'pack') {
            return route('pack.show');
        }

        // ?course={slug} → page formation éligible uniquement
        if ($request->has('course')) {
            $slug   = (string) $request->query('course');
            $course = Course::where('slug', $slug)
                ->where('affiliate_eligible', true)
                ->where('is_active', true)
                ->first();
            if ($course) {
                return route('courses.show', $course->slug);
            }
        }

        // ?product={slug} → produit marketplace Boursiv éligible (user_id IS NULL)
        if ($request->has('product')) {
            $slug    = (string) $request->query('product');
            $product = MarketplaceProduct::where('slug', $slug)
                ->where('affiliate_eligible', true)
                ->whereNull('user_id')
                ->where('status', 'published')
                ->first();
            if ($product) {
                return route('marketplace.show', $product->slug);
            }
        }

        return '/';
    }

    // ─── Dashboard apporteur ──────────────────────────────────────────────────

    public function dashboard(Request $request, AffiliateEarningsService $earningsService)
    {
        $user      = $request->user();
        $affiliate = $user->affiliate;

        $totals = $earningsService->totalsForAffiliate($affiliate->id);

        $commissions = AffiliateCommission::where('affiliate_id', $affiliate->id)
            ->latest()
            ->paginate(10, ['*'], 'comm_page');

        $payouts = AffiliatePayoutRequest::where('affiliate_id', $affiliate->id)
            ->latest()
            ->paginate(5, ['*'], 'payout_page');

        $affiliateUrl = url('/r/' . $affiliate->code);

        $qrSvg = QrCode::format('svg')
            ->size(180)
            ->margin(1)
            ->generate($affiliateUrl);

        // ── Raccourcis produits éligibles (whitelist serveur) ─────────────────
        // Pack BRVM toujours éligible (pas de modèle en base)
        $eligibleCourses = config('affiliate.pack_brvm_eligible', true)
            ? Course::where('affiliate_eligible', true)->where('is_active', true)->get()
            : collect();

        $eligibleProducts = MarketplaceProduct::where('affiliate_eligible', true)
            ->whereNull('user_id')           // garde-fou ownership : Boursiv uniquement
            ->where('status', 'published')
            ->orderBy('title')
            ->get();

        return view('affiliate.dashboard', compact(
            'affiliate',
            'totals',
            'commissions',
            'payouts',
            'affiliateUrl',
            'qrSvg',
            'eligibleCourses',
            'eligibleProducts'
        ));
    }
}
