<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Theme;
use App\UserRole;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class LandingController extends Controller
{
    public function index(): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect(auth()->user()->role === UserRole::Admin ? '/admin' : '/user');
        }

        return view('landing', [
            'themes' => Theme::query()->active()->latest()->limit(6)->get(),
            'plansByTier' => Plan::query()->orderBy('price')->get()->groupBy('tier'),
        ]);
    }
}
