<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WelcomePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests(); // l'accueil ne doit appeler aucun service externe
    }

    public function test_response_starts_with_doctype_without_bom(): void
    {
        $html = $this->get('/welcome')->assertOk()->getContent();

        $this->assertStringStartsWith('<!doctype html>', ltrim($html));
        $this->assertStringNotContainsString("\u{FEFF}", $html);
    }
}
