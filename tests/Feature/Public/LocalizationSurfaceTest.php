<?php

namespace Tests\Feature\Public;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationSurfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_locale_prefixed_home_uses_english_public_labels(): void
    {
        $this->get('/en/')
            ->assertOk()
            ->assertSee('Today&#039;s Brief', false)
            ->assertSee('Today&#039;s Edition', false)
            ->assertSee('All Cities', false)
            ->assertDontSee('/tr/en', false);
    }

    public function test_query_locale_keeps_public_shell_in_selected_language(): void
    {
        $this->get('/?locale=en')
            ->assertOk()
            ->assertSee('Today&#039;s Brief', false)
            ->assertSee('Language options', false);
    }

    public function test_unprefixed_home_returns_to_turkish_after_prefixed_locale_visit(): void
    {
        $this->get('/en/')
            ->assertOk()
            ->assertSee('<html lang="en"', false);

        $this->get('/')
            ->assertOk()
            ->assertSee('<html lang="tr"', false)
            ->assertSee('Günün Seçkisi')
            ->assertDontSee('Today&#039;s Brief', false);
    }
}
