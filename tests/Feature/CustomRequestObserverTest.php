<?php

namespace Tests\Feature;

use App\Models\CustomRequest;
use App\Notifications\CustomRequestStatusChanged;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * The observer is model-level, so it fires regardless of which caller
 * changes the status — not just the one path (CustomRequestBuildController)
 * covered elsewhere. This proves that directly, e.g. an admin editing the
 * status via CustomRequestResource's inline column.
 */
class CustomRequestObserverTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_any_status_update_notifies_the_requester(): void
    {
        Notification::fake();

        $customRequest = CustomRequest::factory()->create(['status' => 'new']);

        $customRequest->update(['status' => 'delivered']);

        Notification::assertSentTo($customRequest->user, CustomRequestStatusChanged::class);
    }

    public function test_updating_other_fields_does_not_notify(): void
    {
        Notification::fake();

        $customRequest = CustomRequest::factory()->create(['status' => 'new']);

        $customRequest->update(['style_notes' => 'Updated notes.']);

        Notification::assertNothingSent();
    }
}
