<?php

namespace Tests\Unit;

use App\Layup\Support\MediaCataloger;
use App\Models\Media;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Tests\TestCase;

/**
 * MediaCataloger::saveAndCatalog() is what App\Providers\AppServiceProvider
 * wires up as the default saveUploadedFileUsing() callback for every
 * FileUpload field app-wide — including the ~90 vendor Layup widget fields
 * (Hero background image, Gallery images, Person photos, etc.) that
 * uploaded straight to disk with no Media Library record at all before
 * this. Constructs a real TemporaryUploadedFile the same way Livewire's
 * own upload flow does (via FileUploadConfiguration::storeTemporaryFile()),
 * so this exercises the actual method Filament calls, not a stand-in.
 */
class MediaCatalogerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function tempUploadedFile(UploadedFile $fake): TemporaryUploadedFile
    {
        // ::storage() is what lazily Storage::fake()s the temp upload disk
        // on first use in tests — storeTemporaryFile() writes to it
        // directly without going through that, so it has to be called
        // first or the disk has no driver configured yet.
        FileUploadConfiguration::storage();

        $disk = FileUploadConfiguration::disk();
        $storedPath = FileUploadConfiguration::storeTemporaryFile($fake, $disk);

        // TemporaryUploadedFile's constructor re-applies the temp
        // directory prefix itself (via FileUploadConfiguration::path()),
        // so it wants the path with that prefix already stripped — same
        // as Livewire's own FileUploadController::validateAndStore().
        $relativePath = str_replace(FileUploadConfiguration::path('/'), '', $storedPath);

        return new TemporaryUploadedFile($relativePath, $disk);
    }

    public function test_it_stores_the_file_and_creates_a_matching_media_record(): void
    {
        Storage::fake('public');

        $fileUpload = FileUpload::make('background_image')
            ->disk('public')
            ->directory('layup/hero');

        $tempFile = $this->tempUploadedFile(UploadedFile::fake()->image('bg.jpg', 800, 600));

        $path = MediaCataloger::saveAndCatalog($fileUpload, $tempFile);

        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);

        $media = Media::query()->where('path', $path)->sole();
        $this->assertSame('public', $media->disk);
        $this->assertSame('layup/hero', $media->directory);
        $this->assertSame('image/jpeg', $media->type);
        $this->assertSame('jpg', $media->ext);
        $this->assertSame(800, $media->width);
        $this->assertSame(600, $media->height);
        $this->assertGreaterThan(0, $media->size);
    }

    public function test_it_stamps_the_current_user_as_the_owner(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $this->actingAs($user);

        $fileUpload = FileUpload::make('background_image')->disk('public');
        $tempFile = $this->tempUploadedFile(UploadedFile::fake()->image('bg.jpg'));

        $path = MediaCataloger::saveAndCatalog($fileUpload, $tempFile);

        $media = Media::query()->where('path', $path)->sole();
        $this->assertSame($user->id, $media->user_id);
    }

    public function test_non_image_uploads_are_cataloged_without_dimensions(): void
    {
        Storage::fake('public');

        $fileUpload = FileUpload::make('audio_file')->disk('public');
        $tempFile = $this->tempUploadedFile(UploadedFile::fake()->create('song.mp3', 500, 'audio/mpeg'));

        $path = MediaCataloger::saveAndCatalog($fileUpload, $tempFile);

        $media = Media::query()->where('path', $path)->sole();
        $this->assertSame('audio/mpeg', $media->type);
        $this->assertNull($media->width);
        $this->assertNull($media->height);
    }

    /**
     * The tests above prove MediaCataloger::saveAndCatalog() itself is
     * correct, but call it directly — they don't prove
     * AppServiceProvider::boot()'s FileUpload::configureUsing() actually
     * wires it up as the default for a plain FileUpload::make() field the
     * way a vendor Layup widget declares one. ->configure() is the same
     * method Filament calls when a field becomes part of a resolved
     * Schema; calling it directly here reaches the same
     * ComponentManager::configure() codepath without needing a full
     * Schema/Livewire form around it.
     */
    public function test_a_plain_file_upload_field_is_globally_wired_to_catalog_into_the_media_library(): void
    {
        Storage::fake('public');

        $fileUpload = FileUpload::make('background_image')
            ->disk('public')
            ->directory('layup/hero');

        // ->configure() is the same method Filament calls when a field
        // becomes part of a resolved Schema — reaches
        // ComponentManager::configure(), which runs BaseFileUpload's own
        // setUp() (setting the vendor default saveUploadedFileUsing
        // closure) and then AppServiceProvider's
        // FileUpload::configureUsing() callback, in that order, so the
        // provider's override is the one left standing. Read via
        // reflection rather than exercising the full upload flow, which
        // needs a real Schema container this standalone field doesn't have.
        $fileUpload->configure();

        $property = new \ReflectionProperty($fileUpload, 'saveUploadedFileUsing');
        $callback = $property->getValue($fileUpload);

        $this->assertInstanceOf(\Closure::class, $callback);

        $reflectedCallback = new \ReflectionFunction($callback);
        $this->assertSame(
            MediaCataloger::class,
            $reflectedCallback->getClosureScopeClass()?->getName(),
            'FileUpload::configureUsing() in AppServiceProvider should have made MediaCataloger::saveAndCatalog() the default saveUploadedFileUsing callback.',
        );
    }
}
