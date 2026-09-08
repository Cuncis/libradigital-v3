<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use Crumbls\Layup\Http\Controllers\AbstractController;
use Crumbls\Layup\View\Row;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class InvitationPageController extends AbstractController
{
    protected function getRecord(Request $request): Model
    {
        return Invitation::published()
            ->where('slug', $request->route('slug'))
            ->firstOrFail();
    }

    protected function getLayout(Request $request, Model $record): string
    {
        return 'layouts.invitation';
    }

    /**
     * @param  array<int, array{settings: array<string, mixed>, rows: array<Row>}>  $sections
     * @return array<string, mixed>
     */
    protected function getViewData(Request $request, Model $record, array $sections): array
    {
        // Guest-name personalization (?to=Jane) — not a Layup feature, and the
        // custom layout below isn't reachable from parent-view scope, so this
        // mirrors AbstractController's own `layupPage` share for the same reason.
        view()->share('guestName', $request->query('to'));

        return [];
    }
}
