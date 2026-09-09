<?php

namespace Tests\Feature;

use App\Models\Theme;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ThemePreviewControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $theme = Theme::factory()->create();

        $response = $this->get("/themes/{$theme->id}/preview");

        $response->assertRedirect('/user/login');
    }

    public function test_admin_can_preview_a_theme_and_see_its_content(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $theme = Theme::factory()->create([
            'name' => 'Elegant Wedding',
            'content' => [
                'rows' => [[
                    'id' => 'row_1',
                    'settings' => [],
                    'columns' => [[
                        'id' => 'col_1',
                        'span' => ['sm' => 12, 'md' => 12, 'lg' => 12, 'xl' => 12],
                        'settings' => [],
                        'widgets' => [[
                            'id' => 'widget_1',
                            'type' => 'heading',
                            'data' => ['content' => 'Save The Date', 'level' => 'h2'],
                        ]],
                    ]],
                ]],
            ],
        ]);

        $response = $this->actingAs($admin)->get("/themes/{$theme->id}/preview");

        $response->assertOk();
        $response->assertSee('<title>Elegant Wedding</title>', false);
        $response->assertSee('Save The Date');
        $response->assertSee('Theme preview', false);
    }

    public function test_customer_cannot_preview_a_theme(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $theme = Theme::factory()->create();

        $response = $this->actingAs($customer)->get("/themes/{$theme->id}/preview");

        $response->assertForbidden();
    }

    public function test_unknown_theme_returns_404(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->get('/themes/999999/preview');

        $response->assertNotFound();
    }
}
