<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LicenseIssuedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Subscription $subscription)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('Tu licencia TipiDV está lista')
            ->view('emails.license-issued')
            ->with([
                'subscription' => $this->subscription,
                'portalUrl' => config('licensing.portal_url'),
            ]);
    }
}
