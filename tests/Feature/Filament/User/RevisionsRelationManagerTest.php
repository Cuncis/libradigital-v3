<?php

namespace Tests\Feature\Filament\User;

use App\Filament\RelationManagers\RevisionsRelationManager;
use App\Filament\User\Resources\Invitations\Pages\EditInvitation;
use App\Models\Invitation;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RevisionsRelationManagerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('user'));
    }

    public function test_restoring_a_revision_replaces_the_current_content(): void
    {
        $customer = User::factory()->create();
        $invitation = Invitation::factory()->for($customer)->create(['content' => ['rows' => ['v1']]]);

        // No revision on the initial create (nothing to diff against yet) —
        // each subsequent save snapshots the content *as of that save*.
        $invitation->update(['content' => ['rows' => ['v2']]]);
        $v2Revision = $invitation->revisions()->sole();
        $invitation->update(['content' => ['rows' => ['v3']]]);

        $this->assertSame(['rows' => ['v3']], $invitation->fresh()->content);

        Livewire::actingAs($customer)
            ->test(RevisionsRelationManager::class, [
                'ownerRecord' => $invitation,
                'pageClass' => EditInvitation::class,
            ])
            ->callTableAction('restore', $v2Revision);

        $this->assertSame(['rows' => ['v2']], $invitation->fresh()->content);
    }
}
