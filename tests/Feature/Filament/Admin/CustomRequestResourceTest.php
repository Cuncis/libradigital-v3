<?php

namespace Tests\Feature\Filament\Admin;

use App\Filament\Resources\CustomRequests\Pages\ListCustomRequests;
use App\Models\CustomRequest;
use App\Models\User;
use App\UserRole;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomRequestResourceTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_admin_sees_all_custom_requests(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        CustomRequest::factory()->create(['event_type' => 'wedding']);

        $response = $this->actingAs($admin)->get('/admin/custom-requests');

        $response->assertOk();
        $response->assertSee('wedding');
    }

    public function test_customer_cannot_access_custom_requests(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $response = $this->actingAs($customer)->get('/admin/custom-requests');

        $response->assertForbidden();
    }

    public function test_assign_to_me_action_sets_the_assigned_admin(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $customRequest = CustomRequest::factory()->create(['assigned_admin_id' => null]);

        Livewire::actingAs($admin)
            ->test(ListCustomRequests::class)
            ->callTableAction('assignToMe', $customRequest);

        $this->assertSame($admin->id, $customRequest->fresh()->assigned_admin_id);
    }
}
