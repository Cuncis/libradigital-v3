<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RsvpSubmissionController extends Controller
{
    public function store(Request $request, Invitation $invitation): RedirectResponse
    {
        abort_unless($invitation->isPublished(), 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'attending' => ['required', 'in:attending,not_attending,maybe'],
            'party_size' => ['nullable', 'integer', 'min:1', 'max:20'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $invitation->guests()->create([
            'name' => $data['name'],
            'attending' => $data['attending'],
            'party_size' => $data['party_size'] ?? 1,
            'message' => $data['message'] ?? null,
            'responded_at' => now(),
        ]);

        return back()->with('rsvp_submitted', true);
    }
}
