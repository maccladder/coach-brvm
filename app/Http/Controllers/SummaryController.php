<?php

namespace App\Http\Controllers;

use App\Models\Analysis;
use App\Models\DailySummary;
use App\Models\FinancialStatement;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Services\AiInterpreter;
use App\Services\AiVoiceService;
use App\Services\AvatarService;

class SummaryController extends Controller
{
    public function index()
    {
        $summaries = DailySummary::orderBy('for_date', 'desc')->take(30)->get();
        return view('summaries.index', compact('summaries'));
    }

    /** ✅ “Résumé du jour” => redirige vers la date du jour */
    public function showToday()
    {
        $today = Carbon::today()->toDateString();
        return redirect()->route('summaries.show', ['date' => $today]);
    }

    /** ✅ Affiche un résumé pour n’importe quelle date */
    public function showDate(string $date, AiVoiceService $voiceService)
    {
        $day     = Carbon::parse($date)->startOfDay();
        $summary = DailySummary::whereDate('for_date', $day)->first();
        $today   = $day;

        // 🆕 chemin audio généré par AiVoiceService (ou null si erreur)
        $audioPath = null;

        if ($summary) {
            try {
                $audioPath = $voiceService->makeAudioForSummary($summary);
            } catch (\Throwable $e) {
                \Log::warning('Erreur génération audio summary', [
                    'message' => $e->getMessage(),
                    'date'    => $date,
                ]);
                $audioPath = null;
            }
        }

        return view('summaries.today', compact('summary', 'today', 'audioPath'));
    }

    public function generateForm()
    {
        return view('summaries.generate');
    }

    /**
     * ✅ Génère le résumé (données brutes + interprétation IA + avatar vidéo) pour une date donnée
     */
    public function generateForDate(
        Request $request,
        AiInterpreter $ai,
        AvatarService $avatarService
    ) {
        $request->validate(['date' => 'required|date']);

        $target = Carbon::parse($request->input('date'))->toDateString();

        $analysesModels   = Analysis::whereDate('as_of_date', $target)->get();
        $statementsModels = FinancialStatement::whereDate('published_at', $target)->get();

        // 1) Markdown “données brutes”
        $txt  = "### Résumé du {$target}\n\n";

        $txt .= "**Analyses importées :**\n";
        if ($analysesModels->isEmpty()) {
            $txt .= "- Aucune analyse importée aujourd’hui.\n";
        } else {
            foreach ($analysesModels as $a) {
                $txt .= "- {$a->title} ({$a->as_of_date->format('d/m/Y')})\n";
            }
        }

        $txt .= "\n**États financiers publiés :**\n";
        if ($statementsModels->isEmpty()) {
            $txt .= "- Aucun état financier publié aujourd’hui.\n";
        } else {
            foreach ($statementsModels as $s) {
                $label = match ($s->statement_type) {
                    'income'   => 'Compte de résultat',
                    'balance'  => 'Bilan',
                    'cashflow' => 'Flux de trésorerie',
                    default    => ucfirst($s->statement_type),
                };
                $txt .= "- {$s->issuer} ({$s->period}) [{$label}]\n";
            }
        }

        // 2) Données pour l’IA texte
        $analyses = $analysesModels->map(fn($a) => [
            'title'     => $a->title,
            'file_path' => $a->file_path,
            'notes'     => $a->notes,
        ])->values()->all();

        $statements = $statementsModels->map(fn($s) => [
            'issuer'         => $s->issuer,
            'period'         => $s->period,
            'statement_type' => $s->statement_type,
            'file_path'      => $s->file_path,
        ])->values()->all();

        // 3) Interprétation IA (texte)
        $interpretation = $ai->interpret($analyses, $statements, $target);
        $txt           .= "\n\n---\n\n### Interprétation (IA)\n\n{$interpretation}\n";

        /**
         * 4) Préparer un texte COURT et propre pour l’avatar
         */
        $interpretationPlain = $interpretation ?? '';

        // enlever les titres "### Titre"
        $interpretationPlain = preg_replace('/^\s*#+\s*/m', '', $interpretationPlain);
        // enlever les puces "- " ou "* "
        $interpretationPlain = preg_replace('/^\s*[-*]\s+/m', '', $interpretationPlain);
        // enlever **, *, _, ` (gras, italique, code)
        $interpretationPlain = str_replace(['**', '*', '_', '`'], '', $interpretationPlain);
        // liens markdown [texte](url) -> texte
        $interpretationPlain = preg_replace('/\[(.*?)\]\((.*?)\)/', '$1', $interpretationPlain);
        // compacter les lignes
        $interpretationPlain = preg_replace("/\n{2,}/", "\n", $interpretationPlain);
        $interpretationPlain = trim($interpretationPlain);

        // on prend seulement quelques lignes pour que ce soit court
        $lines       = array_filter(array_map('trim', explode("\n", $interpretationPlain)));
        $mainLines   = array_slice($lines, 0, 4);
        $mainSummary = implode(' ', $mainLines);

        // Texte final pour l’avatar
        $textForAvatar = <<<TXT
Bonjour, ici ton coach BRVM.

Voici l’essentiel de la séance du {$target} :
{$mainSummary}

N’oublie pas : ceci n’est pas un conseil d’investissement personnalisé.
Analyse toujours toi-même les entreprises et n’investis que l’argent que tu peux te permettre de perdre.
TXT;

        // sécurité : on coupe si c’est trop long pour la TTS
        $textForAvatar = mb_substr($textForAvatar, 0, 900);

        // 5) Générer la vidéo via AvatarService (peut retourner null en cas d’erreur)
        $avatarVideoUrl = $avatarService->generateTalkingHead($textForAvatar);

        // 6) Upsert DailySummary
        $summary = DailySummary::whereDate('for_date', $target)->first();
        if (!$summary) {
            $summary           = new DailySummary();
            $summary->for_date = $target;
        }

        $summary->summary_markdown = $txt;
        $summary->signals          = []; // à remplir plus tard si tu veux

        // On ne remplace l’URL que si on en a une nouvelle
        if ($avatarVideoUrl) {
            $summary->avatar_video_url = $avatarVideoUrl;
        }

        $summary->save();

        return redirect()
            ->route('summaries.show', ['date' => $target])
            ->with('success', "Résumé + interprétation générés pour le {$target} !");
    }
}
