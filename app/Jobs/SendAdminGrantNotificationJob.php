<?php

namespace App\Jobs;

use App\Mail\AdminGrantNotificationMail;
use App\Models\AdminGrant;
use App\Models\Course;
use App\Models\Document;
use App\Models\MarketplaceProduct;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAdminGrantNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public AdminGrant $grant) {}

    public function handle(): void
    {
        $this->grant->loadMissing(['user', 'grantedBy']);
        $user = $this->grant->user;

        if (!$user) {
            Log::warning('AdminGrantNotification: user not found', ['grant_id' => $this->grant->id]);
            return;
        }

        [$itemName, $itemUrl] = $this->resolveItem();

        Mail::to($user->email, $user->name)
            ->send(new AdminGrantNotificationMail(
                grant:    $this->grant,
                itemName: $itemName,
                itemUrl:  $itemUrl,
            ));
    }

    private function resolveItem(): array
    {
        $type = $this->grant->grantable_type;
        $id   = $this->grant->grantable_id;

        if ($type === 'lettreci') {
            return [
                'le module LettreCI – Générateur de lettres professionnelles',
                route('lettreci.landing'),
            ];
        }

        if (!class_exists($type)) {
            return ['un accès premium Coach BRVM', url('/dashboard')];
        }

        $model = $type::find($id);

        if (!$model) {
            return ['un accès premium Coach BRVM', url('/dashboard')];
        }

        $url = match($type) {
            Course::class             => route('courses.show', $model->slug),
            MarketplaceProduct::class => route('marketplace.show', $model->slug),
            Document::class           => route('documents.show', $model->slug),
            default                   => url('/dashboard'),
        };

        return [$model->title, $url];
    }
}
