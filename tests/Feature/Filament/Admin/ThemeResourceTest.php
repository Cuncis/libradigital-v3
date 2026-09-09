<?php

namespace Tests\Feature\Filament\Admin;

use App\Models\Media;
use App\Models\Theme;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ThemeResourceTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * preview_image holds a Media Library record id now (picked via
     * CuratorPicker), not a storage path — see ThemeForm and the
     * ImageColumn::getStateUsing() override in ThemesTable.
     */
    public function test_the_catalog_resolves_the_preview_image_from_the_media_library(): void
    {
        Storage::shouldReceive('disk')->with('r2')->andReturnSelf();
        Storage::shouldReceive('url')->with('theme-previews/elegant.jpg')->andReturn('https://cdn.example.test/theme-previews/elegant.jpg');

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $media = Media::query()->create([
            'disk' => 'r2',
            'visibility' => 'public',
            'name' => 'elegant',
            'path' => 'theme-previews/elegant.jpg',
            'type' => 'image/jpeg',
            'ext' => 'jpg',
        ]);
        Theme::factory()->create(['preview_image' => $media->id]);

        $response = $this->actingAs($admin)->get('/admin/themes');

        $response->assertOk();
        $response->assertSee('https://cdn.example.test/theme-previews/elegant.jpg', false);
    }

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

    public function test_theme_list_shows_a_preview_link_for_each_theme(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $theme = Theme::factory()->create();

        $response = $this->actingAs($admin)->get('/admin/themes');

        $response->assertOk();
        $response->assertSee(route('themes.preview', $theme), false);
    }

    public function test_theme_edit_page_shows_a_preview_link(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $theme = Theme::factory()->create();

        $response = $this->actingAs($admin)->get("/admin/themes/{$theme->id}/edit");

        $response->assertOk();
        $response->assertSee(route('themes.preview', $theme), false);
    }
}
