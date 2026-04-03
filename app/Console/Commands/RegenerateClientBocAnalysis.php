<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ClientBoc;
use App\Services\AiInterpreter;
use App\Services\AiVoiceService;
use App\Services\AvatarService;

class RegenerateClientBocAnalysis extends Command
{
    protected $signature = 'boc:regen {id} {--reset : Reset fields before regenerating}';
    protected $description = 'Regenerate IA interpretation/audio/avatar for a ClientBoc';

    public function handle(AiInterpreter $ai, AiVoiceService $voice, AvatarService $avatar)
    {
        $id = (int) $this->argument('id');

        /** @var ClientBoc $boc */
        $boc = ClientBoc::find($id);

        if (!$boc) {
            $this->error("ClientBoc #{$id} introuvable.");
            return self::FAILURE;
        }

        if ($this->option('reset')) {
            $boc->interpreted_markdown = null;
            $boc->avatar_video_url = null;
            $boc->audio_path = null;
            $boc->save();
        }

        // --- même logique que dans ton controller ---
        $bocDate = $boc->boc_date->toDateString();

        $analyses = [[
            'title'     => $boc->title,
            'file_path' => $boc->stored_path,
            'notes'     => null,
        ]];

        $statements = [];

        $interpretation = $ai->interpret($analyses, $statements, $bocDate);
        $boc->interpreted_markdown = $interpretation;

        // texte avatar court
        $plain = $interpretation ?? '';
        $plain = preg_replace('/^\s*#+\s*/m', '', $plain);
        $plain = preg_replace('/^\s*[-*]\s+/m', '', $plain);
        $plain = str_replace(['**', '*', '_', '`'], '', $plain);
        $plain = preg_replace('/\[(.*?)\]\((.*?)\)/', '$1', $plain);
        $plain = preg_replace("/\n{2,}/", "\n", $plain);
        $plain = trim($plain);

        $lines       = array_filter(array_map('trim', explode("\n", $plain)));
        $mainLines   = array_slice($lines, 0, 4);
        $mainSummary = implode(' ', $mainLines);

        $textForAvatar = "Bonjour, je suis ton coach BRVM.\n\n"
            ."Voici les principaux enseignements de ton BOC du {$bocDate} :\n"
            ."{$mainSummary}\n\n"
            ."N'oublie pas : ceci n'est pas un conseil d'investissement personnalisé.\n"
            ."Analyse toujours toi-même les entreprises et n'investis que l'argent que tu peux te permettre de perdre.";

        $textForAvatar = mb_substr($textForAvatar, 0, 900);

        $avatarUrl = $avatar->generateTalkingHead($textForAvatar);
        if ($avatarUrl) {
            $boc->avatar_video_url = $avatarUrl;
        }

        $audioPath = $voice->makeAudioFromMarkdown(
            $boc->interpreted_markdown ?? '',
            'clientboc-' . $boc->id
        );

        $boc->audio_path = $audioPath;

        $boc->save();

        $this->info("✅ OK: interpretation/audio/avatar générés pour ClientBoc #{$boc->id}");
        return self::SUCCESS;
    }
}
