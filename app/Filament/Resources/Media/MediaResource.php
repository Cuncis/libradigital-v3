<?php

namespace App\Filament\Resources\Media;

use App\Filament\Resources\Media\Pages\CreateMedia;
use App\Filament\Resources\Media\Pages\EditMedia;
use App\Filament\Resources\Media\Pages\ListMedia;
use App\Models\Media;
use App\Models\User;
use App\UserRole;
use Awcodes\Curator\Resources\Media\MediaResource as CuratorMediaResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Js;

/**
 * Every user's Media Library is scoped to their own uploads — except
 * admins, who see everything by default and can narrow to just their own
 * via the radio filter added to the table below. Customers never see the
 * filter at all (their query is hard-scoped regardless, so it would do
 * nothing for them).
 */
class MediaResource extends CuratorMediaResource
{
    public static function getModel(): string
    {
        return Media::class;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        /** @var (Authenticatable&User)|null $user */
        $user = Auth::user();

        if ($user && $user->role !== UserRole::Admin) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    public static function table(Table $table): Table
    {
        $table = parent::table($table);

        // Appended rather than passed to ->recordActions() — that setter
        // replaces the array outright, and MediaTable::configure() (the
        // parent call above) already put Edit/Delete there.
        $table->pushRecordActions([
            static::copyLinkAction(),
        ]);

        /** @var (Authenticatable&User)|null $user */
        $user = Auth::user();

        if ($user && $user->role === UserRole::Admin) {
            $table->filters([
                Filter::make('owner_scope')
                    ->label('Show')
                    ->form([
                        Radio::make('value')
                            ->label('Show')
                            ->options([
                                'all' => 'All users\' media',
                                'self' => 'Just my own',
                            ])
                            ->default('all')
                            ->inline()
                            ->inlineLabel(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        // Resolved fresh here rather than captured from the
                        // outer $user — this closure is serialized as part
                        // of the table's filter definition across
                        // Livewire's request/response cycle, and a
                        // captured Eloquent model doesn't reliably survive
                        // that round trip the same way a facade call does.
                        if (($data['value'] ?? 'all') === 'self') {
                            $query->where('user_id', Auth::id());
                        }

                        return $query;
                    })
                    ->indicateUsing(fn (array $data): ?string => ($data['value'] ?? 'all') === 'self' ? 'Just my own' : null),
            ]);
        }

        return $table;
    }

    /**
     * A plain click — writeText() to the clipboard is a browser API, not
     * something a server round trip can do — reusing the same Alpine
     * pattern Filament's own TextInput\Actions\CopyAction uses (that one
     * reads a form input's value from the DOM; this one already has the
     * record's URL server-side, so it's embedded directly rather than
     * queried at click time).
     */
    protected static function copyLinkAction(): Action
    {
        return Action::make('copyLink')
            ->label('Copy Link')
            ->icon(Heroicon::OutlinedLink)
            ->color('gray')
            ->alpineClickHandler(function (Media $record): string {
                $urlJs = Js::from($record->url);
                $messageJs = Js::from('Copied!');

                return <<<JS
                    window.navigator.clipboard.writeText({$urlJs})
                    \$tooltip({$messageJs}, {
                        theme: \$store.theme,
                        timeout: 2000,
                    })
                    JS;
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMedia::route('/'),
            'create' => CreateMedia::route('/create'),
            'edit' => EditMedia::route('/{record}/edit'),
        ];
    }
}
