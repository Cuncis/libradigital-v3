<?php

namespace App\Layup\Support;

use App\Models\Media;
use Filament\Forms\Components\BaseFileUpload;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Every plain FileUpload field in the app — including the ~90 vendor Layup
 * widget fields (Hero background image, Gallery images, Person photos,
 * etc.) that can't be swapped for CuratorPicker without forking vendor PHP
 * classes — uploaded straight to disk with no Media Library record at all,
 * so a file uploaded through a widget rendered correctly in the invitation
 * but was invisible in /admin/media or /user/media.
 *
 * Registered globally via FileUpload::configureUsing() in
 * AppServiceProvider::boot(), saveAndCatalog() replaces the field's default
 * saveUploadedFileUsing() callback: it still stores the file exactly as
 * before (same disk, same directory, same returned path — nothing about
 * how a widget stores or renders its data changes), but also creates a
 * matching Media row so the file shows up in the library too.
 */
class MediaCataloger
{
    public static function saveAndCatalog(BaseFileUpload $component, TemporaryUploadedFile $file): ?string
    {
        // Captured before saveUploadedFile() runs — it may move the
        // temporary file (same-disk fast path), after which reading its
        // size/mime type again isn't reliable.
        $mimeType = $file->getMimeType();
        $size = $file->getSize();
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension() ?: pathinfo($originalName, PATHINFO_EXTENSION);

        $dimensions = null;

        if ($mimeType && str_starts_with($mimeType, 'image/')) {
            $dimensions = @getimagesize($file->getRealPath()) ?: null;
        }

        $path = $component->saveUploadedFile($file);

        if ($path === null) {
            return null;
        }

        Media::query()->create([
            'disk' => $component->getDiskName(),
            'directory' => $component->getDirectory(),
            'visibility' => $component->getVisibility(),
            'name' => pathinfo((string) $originalName, PATHINFO_FILENAME) ?: pathinfo($path, PATHINFO_FILENAME),
            'path' => $path,
            'width' => $dimensions[0] ?? null,
            'height' => $dimensions[1] ?? null,
            'size' => $size,
            'type' => $mimeType,
            'ext' => $extension,
        ]);

        return $path;
    }
}
