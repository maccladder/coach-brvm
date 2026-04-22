<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ClaudeLetterService
{
    public function generate(string $templateSlug, array $inputs): string
    {
        $template = config("lettreci_templates.types.{$templateSlug}");
        abort_if(!$template, 404, 'Template introuvable');

        $systemPrompt = config('lettreci_templates.system_prompt');
        $userPrompt   = $this->buildUserPrompt($template['prompt_template'], $inputs);

        $response = Http::withHeaders([
            'x-api-key'         => config('services.claude.api_key'),
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])
        ->timeout(60)
        ->post(config('services.claude.url'), [
            'model'      => config('services.claude.model'),
            'max_tokens' => 2000,
            'system'     => $systemPrompt,
            'messages'   => [
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Claude API error: ' . $response->body());
        }

        $text = $response->json('content.0.text');

        if (empty($text)) {
            throw new \RuntimeException('Claude API returned an empty response.');
        }

        return trim($text);
    }

    private function buildUserPrompt(string $template, array $inputs): string
    {
        $inputs['date_jour'] = now()->locale('fr')->isoFormat('D MMMM YYYY');

        return preg_replace_callback('/\{(\w+[-\w]*)\}/', function ($m) use ($inputs) {
            $key = str_replace('-', '_', $m[1]);
            return $inputs[$key] ?? '';
        }, $template);
    }
}
