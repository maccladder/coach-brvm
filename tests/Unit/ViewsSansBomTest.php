<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Un BOM UTF-8 en tête d'une vue Blade est envoyé tel quel dans la réponse
 * (avant le <!doctype>, ou au début d'un e-mail / PDF).
 */
class ViewsSansBomTest extends TestCase
{
    public function test_no_blade_view_starts_with_utf8_bom(): void
    {
        $dossier = dirname(__DIR__, 2) . '/resources/views';
        $avecBom = [];

        $fichiers = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dossier, RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($fichiers as $f) {
            if (str_ends_with($f->getFilename(), '.php')
                && file_get_contents($f->getPathname(), false, null, 0, 3) === "\xEF\xBB\xBF") {
                $avecBom[] = substr($f->getPathname(), strlen($dossier) + 1);
            }
        }

        $this->assertSame([], $avecBom, 'Vues avec BOM UTF-8 : ' . implode(', ', $avecBom));
    }
}
