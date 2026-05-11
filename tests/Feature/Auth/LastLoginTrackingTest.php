<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class LastLoginTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_event_updates_last_login_timestamp(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'last_login_at' => null,
        ]);

        Event::dispatch(new Login('web', $user, false));

        $this->assertNotNull($user->refresh()->last_login_at);
    }
}
