<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AffiliateActivatedMail;
use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\Course;
use App\Models\MarketplaceProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AffiliateAdminController extends Controller
{
    // ── Liste des apporteurs ──────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = Affiliate::query()->with('user')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $affiliates = $query->paginate(20)->withQueryString();

        return view('admin.affiliates.index', compact('affiliates'));
    }

    // ── Approuver un apporteur ────────────────────────────────────────────────

    public function approve(Request $request, Affiliate $affiliate)
    {
        if ($affiliate->status !== 'pending') {
            return back()->with('warning', 'Statut invalide.');
        }

        $affiliate->activate($request->user()->id);

        // Email activation à l'apporteur (non bloquant)
        try {
            $affiliate->load('user');
            if ($affiliate->user?->email) {
                Mail::to($affiliate->user->email)->send(new AffiliateActivatedMail($affiliate));
            }
        } catch (\Throwable $e) {
            Log::error('AffiliateAdminController: email activation échec', [
                'affiliate_id' => $affiliate->id, 'error' => $e->getMessage(),
            ]);
        }

        return back()->with('success', '✅ Apporteur activé.');
    }

    // ── Suspendre un apporteur ────────────────────────────────────────────────

    public function suspend(Request $request, Affiliate $affiliate)
    {
        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        $affiliate->suspend($data['admin_note'] ?? null);

        return back()->with('success', '⛔ Apporteur suspendu.');
    }

    // ── Toggle éligibilité d'un produit marketplace ───────────────────────────

    public function toggleProduct(Request $request, MarketplaceProduct $product)
    {
        // Le garde-fou ownership est dans AffiliateCommissionService ;
        // on permet quand même de cocher le flag même sur un produit vendeur
        // (la commission sera bloquée au moment de la création de toute façon).
        $product->affiliate_eligible = !$product->affiliate_eligible;
        $product->save();

        $state = $product->affiliate_eligible ? 'activée' : 'désactivée';

        return back()->with('success', "Éligibilité affiliation {$state} pour « {$product->title} ».");
    }

    // ── Toggle éligibilité d'un cours ─────────────────────────────────────────

    public function toggleCourse(Request $request, Course $course)
    {
        $course->affiliate_eligible = !$course->affiliate_eligible;
        $course->save();

        $state = $course->affiliate_eligible ? 'activée' : 'désactivée';

        return back()->with('success', "Éligibilité affiliation {$state} pour « {$course->title} ».");
    }

    // ── Liste des commissions (pour l'annulation manuelle) ────────────────────

    public function commissions(Request $request, Affiliate $affiliate)
    {
        $commissions = AffiliateCommission::where('affiliate_id', $affiliate->id)
            ->with('payment')
            ->latest()
            ->paginate(20);

        return view('admin.affiliates.commissions', compact('affiliate', 'commissions'));
    }
}
