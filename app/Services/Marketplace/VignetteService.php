<?php

namespace App\Services\Marketplace;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Miniatures légères des couvertures marketplace (accueil, connexions lentes).
 *
 * Les couvertures d'origine pèsent jusqu'à plusieurs Mo : on génère une
 * vignette 400×300 (recadrage centré 4:3) en WebP, ou en JPEG si le serveur
 * ne gère pas le WebP, dans marketplace/covers/thumbs/ sur le disque public.
 * L'original n'est jamais modifié.
 */
class VignetteService
{
    public const LARGEUR = 400;
    public const HAUTEUR = 300;
    private const DOSSIER = 'marketplace/covers/thumbs';

    public function chemin(string $cover): string
    {
        $nom = pathinfo($cover, PATHINFO_FILENAME);

        return self::DOSSIER . '/' . $nom . '-' . self::LARGEUR . '.' . $this->extension();
    }

    /** URL publique de la vignette, ou null si elle n'a pas encore été générée. */
    public function url(?string $cover): ?string
    {
        if (!$cover) {
            return null;
        }

        $vignette = $this->chemin($cover);

        return Storage::disk('public')->exists($vignette) ? asset('storage/' . $vignette) : null;
    }

    /**
     * Génère la vignette d'une couverture.
     *
     * @return string|null chemin de la vignette (disque public), null en cas d'échec
     */
    public function generer(string $cover, bool $forcer = false): ?string
    {
        $disque   = Storage::disk('public');
        $vignette = $this->chemin($cover);

        if (!$forcer && $disque->exists($vignette)) {
            return $vignette;
        }

        if (!function_exists('imagecreatefromstring') || !$disque->exists($cover)) {
            Log::warning('VignetteService: couverture absente ou GD indisponible', ['cover' => $cover]);
            return null;
        }

        $source = @imagecreatefromstring($disque->get($cover));
        if (!$source) {
            Log::warning('VignetteService: image illisible', ['cover' => $cover]);
            return null;
        }

        // Recadrage centré au format 4:3, puis réduction
        [$w, $h] = [imagesx($source), imagesy($source)];
        $ratio   = self::LARGEUR / self::HAUTEUR;
        if ($w / $h > $ratio) {
            $cw = (int) round($h * $ratio); $ch = $h; $cx = (int) (($w - $cw) / 2); $cy = 0;
        } else {
            $cw = $w; $ch = (int) round($w / $ratio); $cx = 0; $cy = (int) (($h - $ch) / 2);
        }

        $cible = imagecreatetruecolor(self::LARGEUR, self::HAUTEUR);
        // Fond papier de la charte sous les zones transparentes
        imagefill($cible, 0, 0, imagecolorallocate($cible, 0xF5, 0xF1, 0xE8));
        imagecopyresampled($cible, $source, 0, 0, $cx, $cy, self::LARGEUR, self::HAUTEUR, $cw, $ch);

        ob_start();
        $this->extension() === 'webp' ? imagewebp($cible, null, 72) : imagejpeg($cible, null, 78);
        $octets = ob_get_clean();

        imagedestroy($source);
        imagedestroy($cible);

        $disque->put($vignette, $octets);

        return $vignette;
    }

    private function extension(): string
    {
        return function_exists('imagewebp') ? 'webp' : 'jpg';
    }
}
