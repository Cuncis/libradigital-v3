<?php

namespace App\Http\Controllers;

use App\Models\Theme;
use App\UserRole;
use Crumbls\Layup\Http\Controllers\AbstractController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Themes are admin-managed starter templates, not published pages — there's
 * no customer-facing route for them, so this is admin-only rather than
 * reusing InvitationPolicy's owner-or-admin shape.
 */
class ThemePreviewController extends AbstractController
{
    protected function getRecord(Request $request): Model
    {
        return Theme::query()->findOrFail($request->route('theme'));
    }

    protected function authorize(Request $request, Model $record): void
    {
        abort_unless($request->user()->role === UserRole::Admin, 403);
    }

    protected function getLayout(Request $request, Model $record): string
    {
        return 'layouts.theme-preview';
    }
}
