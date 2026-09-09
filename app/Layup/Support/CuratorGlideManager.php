<?php

namespace App\Layup\Support;

use Awcodes\Curator\Config\GlideManager;
use Awcodes\Curator\Glide\SymfonyResponseFactory;
use Illuminate\Support\Facades\Storage;
use League\Glide\Server;
use League\Glide\ServerFactory;

/**
 * Curator's own GlideManager::getDefaultServerConfig() (private, so it
 * can't be overridden directly) points Glide's source filesystem at a
 * *local* path (storage_path('app') + a 'public' prefix) — it has no idea
 * this app's media actually lives on the 'r2' disk (an S3-compatible
 * remote), so it can't find anything there: thumbnails silently failed to
 * render, and the "view" action threw
 * League\Glide\Filesystem\FileNotFoundException — "Could not find the
 * image `public/layup/heroes/...`."
 *
 * Bound in place of the vendor class (AppServiceProvider::register()).
 * Resolves the disk's real Flysystem adapter fresh on every getServer()
 * call rather than caching it once — the disk can legitimately change
 * between requests (e.g. Storage::fake() in tests, which runs after the
 * app has already booted), so baking it in eagerly at boot time would be
 * stale exactly when it matters most for verifying this fix.
 */
class CuratorGlideManager extends GlideManager
{
    public function getServer(): Server
    {
        $disk = config('curator.default_disk', 'public');

        return ServerFactory::create([
            'response' => new SymfonyResponseFactory(app('request')),
            'source' => Storage::disk($disk)->getDriver(),
            'cache' => storage_path('app/curator-cache'),
            'cache_path_prefix' => '.cache',
            'max_image_size' => 2000 * 2000,
            'base_url' => $this->getBasePath(),
        ]);
    }
}
