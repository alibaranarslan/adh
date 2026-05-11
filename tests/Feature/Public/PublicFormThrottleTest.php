<?php

namespace Tests\Feature\Public;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicFormThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_form_is_rate_limited(): void
    {
        $payload = [
            'name' => 'Okur Test',
            'email' => 'okur@example.com',
            'subject' => 'Merhaba',
            'message' => 'Bu bir test mesajidir.',
        ];

        for ($i = 1; $i <= 3; $i++) {
            $this->post(route('contact.submit'), $payload)->assertStatus(302);
        }

        $this->post(route('contact.submit'), $payload)->assertStatus(429);
    }

    public function test_contact_page_get_is_not_rate_limited_by_submit_bucket(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->get(route('contact'))->assertOk();
        }
    }

    public function test_search_route_is_rate_limited(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            $this->get(route('search', ['q' => 'adiyaman']))->assertOk();
        }

        $this->get(route('search', ['q' => 'adiyaman']))->assertStatus(429);
    }
}
