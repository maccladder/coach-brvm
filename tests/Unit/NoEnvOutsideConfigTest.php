<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * env() ne doit être appelé que dans config/*.php. Ailleurs (app/, vues, routes), il renvoie
 * null dès que la config est en cache (php artisan config:cache, fait à chaque déploiement) :
 * c'est ce qui a fait disparaître le tag GA4 du 31/10 au 04/11/2026. On lit config() à la place.
 */
class NoEnvOutsideConfigTest extends TestCase
{
    /**
     * Exceptions volontaires : le flux CinetPay, abandonné au profit de Paystack et laissé tel
     * quel à la demande du propriétaire (04/11/2026). À retirer d'ici si on le remet en service.
     */
    private const ALLOWED = [
        'app/Services/CinetpayService.php',
        'app/Http/Controllers/ClientBocController.php',
    ];

    private const SCANNED = ['app', 'resources/views', 'routes'];

    /**
     * Appel PHP à env(…) : pas précédé d'un caractère de nom, d'un « -> », « :: », « $ » ou « . »
     * (méthodes, propriétés, « .env » dans un commentaire), ni la fonction CSS env(safe-area-inset-…)
     * des vues, ni « env() » vide cité dans un commentaire (un vrai appel a toujours un argument).
     */
    private const PATTERN = '/(?<![\w$>:.\\\\-])env\s*\((?!\s*(safe-area|\)))/';

    public function test_aucun_env_hors_de_config(): void
    {
        $root  = dirname(__DIR__, 2);
        $found = [];

        foreach (self::SCANNED as $dir) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $dir, RecursiveDirectoryIterator::SKIP_DOTS));

            foreach ($files as $file) {
                if (!str_ends_with($file->getFilename(), '.php')) {
                    continue;
                }

                $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
                if (in_array($relative, self::ALLOWED, true)) {
                    continue;
                }

                foreach (file($file->getPathname()) as $number => $line) {
                    if (preg_match(self::PATTERN, $line)) {
                        $found[] = $relative . ':' . ($number + 1) . '  ' . trim($line);
                    }
                }
            }
        }

        $this->assertSame([], $found, "env() hors de config/ (renvoie null sous config:cache) : passer par une clé config/*.php lue avec config().\n" . implode("\n", $found));
    }

    public function test_le_detecteur_reconnait_les_vrais_appels(): void
    {
        foreach (["\$x = env('A');", "{{ env('GA_PROPERTY_ID') }}", "(int) env ('PRICE', 1000)", "?: env(\$name)"] as $code) {
            $this->assertSame(1, preg_match(self::PATTERN, $code), $code);
        }
        // Faux positifs écartés : CSS, méthodes, commentaires « .env (…) »
        foreach (['padding-top: max(8px, env(safe-area-inset-top, 0px));', '$app->env()', 'Str::env(', '// dans .env (ADMIN_ACCESS_CODE)', 'getenv("A")', '// jamais env() dans une vue'] as $code) {
            $this->assertSame(0, preg_match(self::PATTERN, $code), $code);
        }
    }

    public function test_les_exceptions_existent_encore(): void
    {
        // Une exception devenue inutile (fichier supprimé ou corrigé) doit être retirée de la liste
        $root = dirname(__DIR__, 2);
        foreach (self::ALLOWED as $relative) {
            $this->assertFileExists($root . '/' . $relative);
            $this->assertSame(1, preg_match(self::PATTERN, file_get_contents($root . '/' . $relative)), $relative . ' ne contient plus env() : retire-le des exceptions');
        }
    }
}
