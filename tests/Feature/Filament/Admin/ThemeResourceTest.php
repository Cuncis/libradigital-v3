<?php

namespace Tests\Feature\Filament\Admin;

use App\Models\Theme;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ThemeResourceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_view_the_theme_catalog_including_inactive_themes(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Theme::factory()->inactive()->create(['name' => 'Retired Theme']);

        $response = $this->actingAs($admin)->get('/admin/themes');

        $response->assertOk();
        $response->assertSee('Retired Theme');
    }

    public function test_customer_cannot_access_the_theme_catalog(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $response = $this->actingAs($customer)->get('/admin/themes');

        $response->assertForbidden();
    }
}
