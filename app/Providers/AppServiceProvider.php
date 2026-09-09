<?php

namespace App\Providers;

use App\Layup\Support\CuratorGlideManager;
use App\Layup\Support\MediaCataloger;
use Awcodes\Curator\Config\GlideManager;
use Filament\Forms\Components\FileUpload;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Replaces Curator's own GlideManager binding (registered by its
        // package provider, which — as an auto-discovered package
        // provider — always registers before this app provider does) with
        // one that knows our media lives on the 'r2' disk, not locally.
        // See CuratorGlideManager's docblock.
        $this->app->scoped(GlideManager::class, fn (): CuratorGlideManager => new CuratorGlideManager);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Plain (non-Filament) routes behind `auth` middleware — e.g. /subscribe —
        // have no bare `login` named route to fall back on; both panels define
        // their own (filament.admin.auth.login / filament.user.auth.login).
        Authenticate::redirectUsing(fn () => '/user/login');

        // Applies to every FileUpload field app-wide, including the ~90
        // vendor Layup widget fields we can't otherwise touch — see
        // MediaCataloger's docblock.
        FileUpload::configureUsing(function (FileUpload $fileUpload): void {
            $fileUpload->saveUploadedFileUsing(MediaCataloger::saveAndCatalog(...));
        });
    }
}
