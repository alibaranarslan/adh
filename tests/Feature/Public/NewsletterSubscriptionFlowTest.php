<?php

namespace Tests\Feature\Public;

use App\Models\NewsletterSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterSubscriptionFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_newsletter_subscription_and_unsubscribe_flow_work(): void
    {
        $subscribe = $this->postJson(route('newsletter.subscribe'), [
            'email' => 'okur@example.com',
            'name' => 'Sadik Okur',
        ]);

        $subscribe->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $subscription = NewsletterSubscription::query()->firstOrFail();

        $this->assertTrue($subscription->is_active);
        $this->assertNotNull($subscription->confirmed_at);
        $this->assertNotEmpty($subscription->unsubscribe_token);

        $unsubscribe = $this->get(route('newsletter.unsubscribe', $subscription->unsubscribe_token));

        $unsubscribe->assertRedirect('/');

        $this->assertFalse($subscription->fresh()->is_active);
    }

    public function test_newsletter_subscription_route_is_rate_limited(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->postJson(route('newsletter.subscribe'), [
                'email' => "okur{$i}@example.com",
                'name' => 'Okur',
            ])->assertSuccessful();
        }

        $blocked = $this->postJson(route('newsletter.subscribe'), [
            'email' => 'okur6@example.com',
            'name' => 'Okur',
        ]);

        $blocked->assertStatus(429);
    }
}
