<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Services\LicenseService;
use App\Support\LicenseSupport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class LicenseAdminController extends Controller
{
    public function __construct(private readonly LicenseService $licenseService)
    {
    }

    public function create(): View
    {
        return view('admin.license-create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_email' => 'required|email|max:255',
            'customer_name' => 'nullable|string|max:255',
            'organization_name' => 'nullable|string|max:255',
            'machine_slots' => 'required|integer|min:1|max:99',
            'months' => 'required|integer|min:1|max:60',
            'billing_period' => 'nullable|string|in:annual,monthly',
            'send_email' => 'nullable|boolean',
            'admin_note' => 'nullable|string|max:500',
        ], [
            'customer_email.required' => 'El correo del destinatario es obligatorio.',
            'customer_email.email' => 'Ingresa un correo válido.',
            'machine_slots.min' => 'Debe permitir al menos 1 equipo.',
            'months.min' => 'La vigencia mínima es 1 mes.',
        ]);

        $sendEmail = $request->has('send_email');

        $result = $this->licenseService->provisionManual(
            email: $validated['customer_email'],
            customerName: $validated['customer_name'] ?? null,
            organizationName: $validated['organization_name'] ?? null,
            machineSlots: (int) $validated['machine_slots'],
            months: (int) $validated['months'],
            billingPeriod: $validated['billing_period'] ?? LicenseSupport::PERIOD_ANNUAL,
            sendEmail: $sendEmail,
            adminNote: $validated['admin_note'] ?? null,
        );

        /** @var Subscription $subscription */
        $subscription = $result['subscription'];

        $message = "Licencia {$subscription->license_key} creada.";
        if ($sendEmail) {
            $message .= $result['email_sent']
                ? ' Correo enviado al destinatario.'
                : ' No se pudo enviar el correo (revise la configuración de mail).';
        }

        return redirect()
            ->route('admin.subscriptions.show', $subscription)
            ->with('status', $message);
    }

    public function resendEmail(Subscription $subscription): RedirectResponse
    {
        $sent = $this->licenseService->resendLicenseEmail($subscription);

        return back()->with(
            $sent ? 'status' : 'error',
            $sent
                ? "Correo de licencia reenviado a {$subscription->customer_email}."
                : 'No se pudo enviar el correo. Revise logs y configuración SMTP.'
        );
    }
}
