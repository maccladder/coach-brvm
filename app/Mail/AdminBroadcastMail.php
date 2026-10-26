<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminBroadcastMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $plainText = '';

    public function __construct(
        public string $emailSubject,
        public string $htmlContent,
        public string $recipientName,
        public bool $fullHtml = false,
    ) {
        if ($this->fullHtml) {
            $this->plainText = $this->toPlainText($this->htmlContent);
        }
    }

    public function build()
    {
        if ($this->fullHtml) {
            return $this
                ->subject($this->emailSubject)
                ->view('emails.admin_broadcast_raw')
                ->text('emails.admin_broadcast_raw_text');
        }

        return $this
            ->subject($this->emailSubject)
            ->view('emails.admin_broadcast')
            ->text('emails.admin_broadcast_text');
    }

    /**
     * Version texte d'un template HTML complet (newsletter collée telle quelle).
     */
    private function toPlainText(string $html): string
    {
        // <head>, <style>, <title> et pré-header caché (display:none)
        $text = preg_replace('#<head\b[^>]*>.*?</head\s*>#is', '', $html);
        $text = preg_replace('#<(style|title)\b[^>]*>.*?</\1\s*>#is', '', $text);
        $text = preg_replace('#<div\b[^>]*display\s*:\s*none[^>]*>.*?</div\s*>#is', '', $text);

        // Liens : "texte (url)"
        $text = preg_replace_callback(
            '#<a\b[^>]*href\s*=\s*(["\'])(.*?)\1[^>]*>(.*?)</a\s*>#is',
            function ($m) {
                $label = trim(strip_tags($m[3]));
                $url = trim($m[2]);
                if ($url === '' || $url === '#' || $label === $url) {
                    return $label !== '' ? $label : $url;
                }
                return $label !== '' ? "{$label} ({$url})" : $url;
            },
            $text
        );

        // Retours ligne
        $text = preg_replace('#<br\s*/?>#i', "\n", $text);
        $text = preg_replace('#</(p|tr|div)\s*>#i', "\n", $text);

        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Espaces et lignes vides multiples
        $text = str_replace("\u{00A0}", ' ', $text);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/ *\n */', "\n", $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }
}
