<?php

namespace Tests\Feature;

use App\Models\Media;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Regression: Curator's Glide server defaults to reading originals from a
 * *local* path (storage_path('app') + a 'public' prefix) — it has no idea
 * this app's media lives on the 'r2' disk (an S3-compatible remote), so it
 * couldn't find anything there at all. Thumbnails in the grid silently
 * failed to render, and the "view" action threw
 * League\Glide\Filesystem\FileNotFoundException — exactly the error
 * reported: "Could not find the image `public/layup/heroes/...`."
 * AppServiceProvider::configureCuratorGlideSource() points Glide's source
 * at the real disk instead.
 */
class CuratorGlideSourceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_thumbnail_renders_for_media_stored_on_the_configured_disk(): void
    {
        $disk = config('curator.default_disk');
        Storage::fake($disk);

        $file = UploadedFile::fake()->image('bg.jpg', 800, 600);
        $path = $file->storeAs('layup/heroes', 'bg.jpg', $disk);

        $media = Media::query()->create([
            'disk' => $disk,
            'visibility' => 'public',
            'name' => 'bg',
            'path' => $path,
            'type' => 'image/jpeg',
            'ext' => 'jpg',
        ]);

        $response = $this->get($media->thumbnail_url);

        $response->assertOk();
        $this->assertStringStartsWith('image/', $response->headers->get('Content-Type'));
    }
}
