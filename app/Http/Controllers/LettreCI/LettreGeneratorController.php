<?php

namespace App\Http\Controllers\LettreCI;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateLetterJob;
use App\Models\LettreGenerated;
use App\Services\LetterPdfService;
use Illuminate\Http\Request;

class LettreGeneratorController extends Controller
{
    public function chooseType()
    {
        $categories = config('lettreci_templates.categories');
        $types      = config('lettreci_templates.types');

        return view('lettre-ci.types', compact('categories', 'types'));
    }

    public function showForm(string $slug)
    {
        $template = config("lettreci_templates.types.{$slug}");
        abort_if(!$template, 404, 'Type de lettre introuvable.');

        return view('lettre-ci.form', compact('template', 'slug'));
    }

    public function submitForm(Request $request, string $slug)
    {
        $template = config("lettreci_templates.types.{$slug}");
        abort_if(!$template, 404, 'Type de lettre introuvable.');

        // Validation dynamique selon les champs du template
        $rules = [];
        foreach ($template['fields'] as $field) {
            $rule = $field['required'] ? 'required' : 'nullable';

            if ($field['type'] === 'number') {
                $rule .= '|numeric|min:0';
            } elseif ($field['type'] === 'date') {
                $rule .= '|date';
            } else {
                $rule .= '|string|max:2000';
            }

            $rules[$field['name']] = $rule;
        }

        $validated = $request->validate($rules);

        $letter = LettreGenerated::create([
            'user_id'      => $request->user()->id,
            'template_slug'=> $slug,
            'title'        => $template['name'],
            'inputs'       => $validated,
            'status'       => 'pending',
        ]);

        GenerateLetterJob::dispatch($letter->id);

        return redirect()->route('lettreci.generating', $letter->id);
    }

    public function generating(LettreGenerated $letter, Request $request)
    {
        abort_unless($letter->user_id === $request->user()->id, 403);

        return view('lettre-ci.generating', compact('letter'));
    }

    public function status(LettreGenerated $letter, Request $request)
    {
        abort_unless($letter->user_id === $request->user()->id, 403);

        $letter->refresh();

        return response()->json([
            'status'      => $letter->status,
            'ready'       => $letter->status === 'completed',
            'failed'      => $letter->status === 'failed',
            'error'       => $letter->error_message,
            'preview_url' => $letter->status === 'completed'
                ? route('lettreci.preview', $letter->id)
                : null,
        ]);
    }

    public function preview(LettreGenerated $letter, Request $request)
    {
        abort_unless($letter->user_id === $request->user()->id, 403);
        abort_unless(in_array($letter->status, ['completed', 'failed']), 404);

        return view('lettre-ci.preview', compact('letter'));
    }

    public function update(Request $request, LettreGenerated $letter)
    {
        abort_unless($letter->user_id === $request->user()->id, 403);

        $request->validate([
            'content_final' => 'required|string|max:20000',
        ]);

        $letter->update(['content_final' => $request->input('content_final')]);

        return back()->with('success', 'Lettre mise à jour avec succès.');
    }

    public function downloadPdf(LettreGenerated $letter, Request $request, LetterPdfService $pdfService)
    {
        abort_unless($letter->user_id === $request->user()->id, 403);
        abort_unless($letter->status === 'completed', 404, 'La lettre n\'est pas encore prête.');

        $letter->load('user');

        $pdfContent = $pdfService->generate($letter);
        $filename   = $pdfService->filename($letter);

        return response($pdfContent, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
