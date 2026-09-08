<?php

namespace Tests\Feature\Filament\Admin;

use App\Filament\Widgets\AdminOverview;
use App\Models\CustomRequest;
use App\Models\Invitation;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminOverviewWidgetTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_dashboard_shows_correct_counts(): void
    {
        $admin = User::factory()->create();

        $monthlyPlan = Plan::factory()->create(['billing_interval' => 'monthly', 'price' => 100_000]);
        Subscription::factory()->active()->create(['plan_id' => $monthlyPlan->id]);

        CustomRequest::factory()->create(['status' => 'new']);
        CustomRequest::factory()->create(['status' => 'delivered']);

        Invitation::factory()->published()->create(['published_at' => now()]);

        // Widgets are lazy-loaded by default (Filament\Support\Concerns\CanBeLazy),
        // so their content never appears in a plain GET /admin response — it loads
        // via a follow-up Livewire request. Test the component directly instead.
        Livewire::actingAs($admin)
            ->test(AdminOverview::class)
            ->assertSee('Active subscriptions')
            ->assertSee('Estimated MRR: Rp 100.000')
            ->assertSee('Custom request backlog')
            ->assertSee('Published this week');
    }
}
