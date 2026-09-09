<?php

namespace Tests\Feature\Filament\Admin;

use App\Filament\Resources\Media\MediaResource;
use App\Filament\Resources\Media\Pages\ListMedia;
use App\Models\Media;
use App\Models\User;
use App\UserRole;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Js;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The Media Library (awcodes/filament-curator): every user's uploads are
 * scoped to themselves, except admins, who see everyone's by default and
 * can narrow to just their own via the radio filter in
 * App\Filament\Resources\Media\MediaResource.
 */
class MediaResourceTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function mediaAttributes(int $userId, string $name): array
    {
        return [
            'user_id' => $userId,
            'disk' => 'public',
            'visibility' => 'public',
            'name' => $name,
            'path' => "media/{$name}.jpg",
            'type' => 'image/jpeg',
            'ext' => 'jpg',
        ];
    }

    public function test_admin_sees_media_from_every_user_by_default(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        Media::query()->create($this->mediaAttributes($admin->id, 'admin-photo'));
        Media::query()->create($this->mediaAttributes($customer->id, 'customer-photo'));

        $response = $this->actingAs($admin)->get('/admin/media');

        $response->assertOk();
        $response->assertSee('admin-photo');
        $response->assertSee('customer-photo');
    }

    public function test_admin_query_includes_every_users_media_before_the_filter_is_applied(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $mine = Media::query()->create($this->mediaAttributes($admin->id, 'admin-photo'));
        $theirs = Media::query()->create($this->mediaAttributes($customer->id, 'customer-photo'));

        $this->actingAs($admin);

        $visible = MediaResource::getEloquentQuery()->pluck('id');
        $this->assertTrue($visible->contains($mine->id));
        $this->assertTrue($visible->contains($theirs->id));
    }

    public function test_admin_can_narrow_to_just_their_own_media_via_the_radio_filter(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        Media::query()->create($this->mediaAttributes($admin->id, 'admin-photo'));
        Media::query()->create($this->mediaAttributes($customer->id, 'customer-photo'));

        // Not assertCanSeeTableRecords()/assertSee() — Curator's default
        // grid layout renders rows via its own custom View::make() column,
        // and in Livewire's test snapshot that content doesn't reliably
        // reflect a filter-triggered re-render (confirmed the underlying
        // query itself is correct via a direct debug log during
        // development: the filter's own where() ran with the right user
        // id both before and after toggling). assertCountTableRecords()
        // reads the query directly rather than parsing rendered HTML.
        $component = Livewire::actingAs($admin)->test(ListMedia::class);
        $component->assertCountTableRecords(2);

        $component->filterTable('owner_scope', ['value' => 'self'])
            ->assertCountTableRecords(1);
    }

    /**
     * Regression coverage for App\Observers\MediaOwnerObserver: a media
     * record created without an explicit user_id (as CuratorPicker/the
     * MultiUploadAction do) gets stamped with the current user.
     */
    public function test_uploading_stamps_the_current_user_as_the_owner(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin);

        $media = Media::query()->create([
            'disk' => 'public',
            'visibility' => 'public',
            'name' => 'auto-owned',
            'path' => 'media/auto-owned.jpg',
            'type' => 'image/jpeg',
            'ext' => 'jpg',
        ]);

        $this->assertSame($admin->id, $media->user_id);
    }

    public function test_the_copy_link_action_embeds_the_medias_actual_url(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $media = Media::query()->create($this->mediaAttributes($admin->id, 'admin-photo'));

        $response = $this->actingAs($admin)->get('/admin/media');

        $response->assertOk();
        $response->assertSee('Copy Link');
        // The Alpine click handler embeds the URL directly (see
        // MediaResource::copyLinkAction()) rather than reading it from the
        // DOM at click time, so the real resolved URL must appear in the
        // page — not a stand-in like the record's path or id. Js::from()
        // escapes slashes (json_encode's default), so the needle has to
        // match what's actually rendered, not the plain URL string.
        $response->assertSee('navigator.clipboard.writeText', false);
        $response->assertSee(Js::from($media->url)->toHtml(), false);
    }

    public function test_customer_cannot_access_the_admin_media_library(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $response = $this->actingAs($customer)->get('/admin/media');

        $response->assertForbidden();
    }
}
