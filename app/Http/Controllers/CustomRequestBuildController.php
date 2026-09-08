<?php

namespace App\Http\Controllers;

use App\CustomRequestStatus;
use App\Filament\Resources\Invitations\InvitationResource;
use App\Models\CustomRequest;
use App\Models\Invitation;
use App\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CustomRequestBuildController extends Controller
{
    public function __invoke(Request $request, CustomRequest $customRequest): RedirectResponse
    {
        abort_unless($request->user()->role === UserRole::Admin, 403);

        if (! $customRequest->invitation_id) {
            $title = trim("{$customRequest->user->name}'s ".ucfirst($customRequest->event_type));

            $invitation = Invitation::query()->create([
                'user_id' => $customRequest->user_id,
                'title' => $title,
                'slug' => Str::slug($title).'-'.Str::lower(Str::random(6)),
                'content' => ['rows' => []],
                'status' => 'draft',
                'is_custom_build' => true,
            ]);

            $customRequest->update([
                'invitation_id' => $invitation->id,
                'status' => $customRequest->status === CustomRequestStatus::New
                    ? CustomRequestStatus::InReview
                    : $customRequest->status,
            ]);
        }

        return redirect(InvitationResource::getUrl('edit', ['record' => $customRequest->invitation_id]));
    }
}
