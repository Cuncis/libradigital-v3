<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RsvpExportController extends Controller
{
    public function export(Request $request): StreamedResponse
    {
        $guests = Guest::query()
            ->whereHas('invitation', fn ($query) => $query->where('user_id', $request->user()->id))
            ->with('invitation')
            ->orderByDesc('responded_at')
            ->get();

        return response()->streamDownload(function () use ($guests): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Invitation', 'Name', 'Attending', 'Party Size', 'Message', 'Responded At']);

            foreach ($guests as $guest) {
                fputcsv($handle, [
                    $guest->invitation->title,
                    $guest->name,
                    $guest->attending?->value,
                    $guest->party_size,
                    $guest->message,
                    $guest->responded_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, 'rsvps.csv', ['Content-Type' => 'text/csv']);
    }
}
