<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MachineActivation;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $subscriptions = Subscription::query()
            ->withCount(['activations', 'activeActivations'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('license_key', 'like', "%{$q}%")
                        ->orWhere('customer_email', 'like', "%{$q}%")
                        ->orWhere('customer_name', 'like', "%{$q}%")
                        ->orWhere('organization_name', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => Subscription::query()->count(),
            'active' => Subscription::query()
                ->where('status', Subscription::STATUS_ACTIVE)
                ->where('expires_at', '>', now())
                ->count(),
            'expired' => Subscription::query()
                ->where('expires_at', '<=', now())
                ->count(),
        ];

        return view('admin.dashboard', compact('subscriptions', 'stats', 'q'));
    }

    public function show(Subscription $subscription): View
    {
        $subscription->load(['activations' => fn ($q) => $q->orderByDesc('activated_at')]);

        return view('admin.subscription', compact('subscription'));
    }

    public function extend(Request $request, Subscription $subscription): RedirectResponse
    {
        $validated = $request->validate([
            'months' => 'required|integer|min:1|max:60',
        ]);

        $base = $subscription->expires_at->isFuture() ? $subscription->expires_at : now();
        $subscription->expires_at = $base->copy()->addMonths((int) $validated['months']);
        $subscription->status = Subscription::STATUS_ACTIVE;
        $subscription->save();

        return back()->with('status', "Vigencia extendida hasta {$subscription->expires_at->format('d/m/Y')}.");
    }

    public function updateSlots(Request $request, Subscription $subscription): RedirectResponse
    {
        $validated = $request->validate([
            'machine_slots' => 'required|integer|min:1|max:99',
        ]);

        $subscription->machine_slots = (int) $validated['machine_slots'];
        $subscription->save();

        return back()->with('status', 'Cupos de equipo actualizados.');
    }

    public function deactivateMachine(MachineActivation $activation): RedirectResponse
    {
        $activation->deactivated_at = now();
        $activation->save();

        return back()->with('status', 'Equipo desvinculado.');
    }
}
