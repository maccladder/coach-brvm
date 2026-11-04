<?php

namespace App\Checkout\Providers;

use App\Checkout\CheckoutRedirect;
use App\Checkout\Payable;
use App\Checkout\StartsClassicFlow;
use App\Http\Controllers\DocumentPaymentController;
use App\Models\Document;
use App\Models\User;
use App\Services\Payments\PaystackDocumentService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Études & Business plans dans le checkout Mobile Money (CHECKOUT_MM_DOCUMENT).
 * Même création « en attente » et même validation que la page Paystack classique.
 */
class DocumentPayable extends BasePayable implements StartsClassicFlow
{
    public function __construct(private PaystackDocumentService $documents) {}

    public function resolve(Request $request, ?User $user): Payable
    {
        $document = Document::active()->find((int) $request->input('document'));

        if (!$document) {
            throw CheckoutRedirect::to(route('docs.public.index'), 'error', 'Document introuvable.');
        }

        if ($document->isBoughtBy($user)) {
            throw CheckoutRedirect::to(route('docs.public.show', $document->slug), 'success', '✅ Document déjà acheté. Tu peux le télécharger.');
        }

        if ((int) $document->price <= 0) {
            throw CheckoutRedirect::to(route('docs.public.show', $document->slug), 'error', 'Ce document ne peut pas être acheté en ligne.');
        }

        return new Payable(
            type: 'document',
            itemKey: 'document:' . $document->id,
            title: $document->title,
            amount: (int) $document->price,   // prix en base, jamais celui de la requête
            email: $user->email,
            successUrl: route('documents.download', $document->id),
            successLabel: 'Télécharger le document',
            badges: ['Accès à vie', 'PDF téléchargeable'],
            params: ['document' => $document->id],
            icon: '📄',
            context: [
                'document' => $document,
                'metadata' => ['document_id' => $document->id],
            ],
        );
    }

    public function createPending(Payable $payable, ?User $user): string
    {
        return $this->documents->createPending($payable->context['document'], $user, 'mobile_money_checkout');
    }

    public function validate(array $data): void
    {
        $this->documents->handleChargeSuccess($data);
    }

    public function isPaid(string $reference): bool
    {
        return $this->paymentIsPaid($reference);
    }

    public function classicForm(Payable $payable): array
    {
        return [
            'action' => route('paystack.documents.buy', $payable->context['document']),
            'fields' => ['classic' => 1],
        ];
    }

    /** Ancien flux Paystack (page hébergée), ex. Wave tant que son Charge API n'est pas activé */
    public function startClassic(Request $request, ?User $user): Response
    {
        $document = Document::findOrFail((int) $request->input('document'));
        $request->merge(['classic' => 1]);

        return app()->call([app(DocumentPaymentController::class), 'buyPaystack'], ['document' => $document]);
    }
}
