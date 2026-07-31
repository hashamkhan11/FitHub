<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingHomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_renders_with_active_plans_and_hardware_pitch(): void
    {
        SubscriptionPlan::create([
            'name' => 'Starter',
            'slug' => 'starter-'.uniqid(),
            'monthly_price' => 29,
            'yearly_price' => 290,
            'is_active' => true,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Starter');
        $response->assertSee('Smart Lock');
        $response->assertSee('Fingerprint Check-in');
    }
}
