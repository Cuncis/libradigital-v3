<?php

namespace App\Http\Controllers;

use App\Models\CustomRequest;
use App\Models\User;
use App\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CustomRequestController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'event_type' => ['required', 'in:wedding,birthday,corporate,other'],
            'event_date' => ['nullable', 'date', 'after_or_equal:today'],
            'style_notes' => ['nullable', 'string', 'max:2000'],
            'budget' => ['nullable', 'integer', 'min:0'],
        ];

        if (! auth()->check()) {
            $rules['name'] = ['required', 'string', 'max:255'];
            $rules['email'] = ['required', 'email', 'max:255'];
            $rules['phone'] = ['nullable', 'string', 'max:30'];
        }

        $data = $request->validate($rules);

        $user = auth()->user() ?? User::query()->firstOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make(Str::random(32)),
                'role' => UserRole::Customer,
            ]
        );

        CustomRequest::query()->create([
            'user_id' => $user->id,
            'event_type' => $data['event_type'],
            'event_date' => $data['event_date'] ?? null,
            'style_notes' => $data['style_notes'] ?? null,
            'budget' => $data['budget'] ?? null,
            'status' => 'new',
        ]);

        return back()->with('custom_request_submitted', true);
    }
}
