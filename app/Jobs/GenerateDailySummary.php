<?php
namespace App\Jobs;

use App\Models\Analysis;
use App\Models\DailySummary;
use App\Models\FinancialStatement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Symfony\Component\Process\Process;

class GenerateDailySummary implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $today = Carbon::today();

        $analyses = Analysis::whereDate('as_of_date',$today)->get();
        $recentFS = FinancialStatement::where('published_at','>=',$today->clone()->subDays(30))->get();

        // 1) Prépare un payload JSON minimal pour le micro-service Python
        $payload = [
            'date'       => $today->toDateString(),
            'analyses'   => $analyses->map(fn($a)=>[
                'title'=>$a->title, 'notes'=>$a->notes, 'file'=>$a->file_path
            ])->values(),
            'statements' => $recentFS->map(fn($f)=>[
                'issuer'=>$f->issuer,'period'=>$f->period,'type'=>$f->statement_type,
                'file'=>$f->file_path,'published_at'=>optional($f->published_at)->toDateString()
            ])->values(),
        ];
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);

        // 2) Appel Python local (mode CLI) -> retourne JSON {summary_markdown, signals}
        $process = Process::fromShellCommandline('python3 ml/service_cli.py');
        $process->setInput($json);
        $process->setTimeout(120);
        $process->run();

        if ($process->isSuccessful()) {
            $out = json_decode($process->getOutput(), true);
            $md = $out['summary_markdown'] ?? "# Résumé du {$today->toDateString()}\n- (aucune donnée)";
            $signals = $out['signals'] ?? [];
        } else {
            // Fallback si le service Python n'est pas prêt
            $md  = "# Résumé du {$today->toDateString()}\n";
            $md .= $analyses->isEmpty()
                ? "- Aucune analyse importée aujourd’hui.\n"
                : "## Analyses du jour\n". $analyses->pluck('title')->map(fn($t)=>"- **$t**")->implode("\n");
            $signals = [];
        }

        DailySummary::updateOrCreate(
            ['for_date'=>$today],
            ['summary_markdown'=>$md,'signals'=>$signals]
        );
    }
}
