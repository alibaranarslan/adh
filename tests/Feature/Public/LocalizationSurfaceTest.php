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
            ->assertSee('Today&#039;s Paper', false)
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
}
