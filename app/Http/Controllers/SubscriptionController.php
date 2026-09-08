<?php

namespace App\Http\Controllers;

use App\Exceptions\MayarCheckoutException;
use App\Models\Plan;
use App\Services\SubscriptionCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function checkout(Request $request, Plan $plan, SubscriptionCheckoutService $checkout): RedirectResponse
    {
        $user = $request->user();

        if ($request->filled('phone')) {
            $user->update(['phone' => $request->string('phone')]);
        }

        try {
            $billUrl = $checkout->checkout($user, $plan);
        } catch (MayarCheckoutException $e) {
            return back()->withErrors(['checkout' => $e->getMessage()]);
        }

        return redirect()->away($billUrl);
    }
}
