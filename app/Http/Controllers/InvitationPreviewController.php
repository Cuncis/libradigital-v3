<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Same rendering as the public InvitationPageController, but reachable
 * regardless of publish status (drafts included) and gated by ownership
 * instead of "must be published" — for the owner/admin previewing a
 * design while building it, not for guests.
 */
class InvitationPreviewController extends InvitationPageController
{
    protected function getRecord(Request $request): Model
    {
        return Invitation::query()->findOrFail($request->route('invitation'));
    }

    protected function authorize(Request $request, Model $record): void
    {
        Gate::authorize('view', $record);
    }
}
