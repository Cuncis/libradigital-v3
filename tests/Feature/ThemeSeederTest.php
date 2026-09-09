<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\Theme;
use App\Models\User;
use Database\Seeders\ThemeSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * Regression: every seeded theme's Hero widget used `title`/`subtitle`,
 * but HeroWidget's actual fields are `heading`/`subheading` — so every
 * seeded theme's hero text has rendered blank since Phase 2. Caught while
 * building a demo invitation and checking widget field names against the
 * source rather than assuming the Phase 2 seeder got them right.
 */
class ThemeSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_seeded_wedding_theme_hero_text_actually_renders(): void
    {
        $this->seed(ThemeSeeder::class);

        $theme = Theme::query()->where('name', 'Elegant Wedding')->sole();
        $user = User::factory()->create();
        $invitation = Invitation::factory()->for($user)->published()->create([
            'slug' => 'seeded-wedding-preview',
            'content' => $theme->content,
        ]);

        $response = $this->get('/i/seeded-wedding-preview');

        $response->assertOk();
        $response->assertSee('The Wedding Of');
        $response->assertSee('Bride &amp; Groom', false);
    }
}
