<?php

namespace App\Services;

use App\Models\LettreGenerated;
use Illuminate\Support\Str;

class LetterPdfService
{
    public function generate(LettreGenerated $letter): string
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('lettre-ci.pdf-template', [
            'letter'  => $letter,
            'content' => $letter->content,
            'user'    => $letter->user,
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->output();
    }

    public function filename(LettreGenerated $letter): string
    {
        $name = $letter->title ?: ($letter->template['name'] ?? 'lettre');
        return 'lettre-' . Str::slug($name) . '-' . $letter->id . '.pdf';
    }
}
