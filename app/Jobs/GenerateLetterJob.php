<?php

namespace App\Jobs;

use App\Models\LettreGenerated;
use App\Services\ClaudeLetterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateLetterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;
    public int $tries   = 3;
    public int $backoff = 10;

    public function __construct(public int $letterId) {}

    public function handle(ClaudeLetterService $claude): void
    {
        $letter = LettreGenerated::findOrFail($this->letterId);

        // Évite de retraiter un job déjà complété (idempotence)
        if ($letter->status === 'completed') {
            return;
        }

        $letter->update(['status' => 'processing']);

        try {
            $content = $claude->generate($letter->template_slug, $letter->inputs);

            $letter->update([
                'content_generated' => $content,
                'status'            => 'completed',
            ]);
        } catch (\Throwable $e) {
            $letter->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            // Re-throw pour que tries/backoff fonctionnent correctement
            throw $e;
        }
    }
}
