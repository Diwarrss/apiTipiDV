<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\LicensePricingService;
use App\Services\PlanCatalogService;
use Illuminate\Http\Response;
use Illuminate\View\View;

final class SiteController extends Controller
{
    public function __construct(
        private readonly PlanCatalogService $plans,
        private readonly LicensePricingService $pricing,
    ) {
    }

    public function home(): View
    {
        return view('site.home', [
            'plans' => $this->plans->plans(),
            'maxQuantity' => $this->pricing->maxQuantity(),
            'volumeDiscounts' => $this->pricing->volumeDiscounts(),
        ]);
    }

    public function comprar(): View
    {
        return view('site.comprar', [
            'plans' => $this->plans->plans(),
            'maxQuantity' => $this->pricing->maxQuantity(),
            'volumeDiscounts' => $this->pricing->volumeDiscounts(),
        ]);
    }

    public function gracias(): View
    {
        return view('site.gracias');
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /api',
            'Sitemap: '.url('/sitemap.xml'),
        ];

        return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain']);
    }

    public function sitemap(): Response
    {
        $urls = [
            ['loc' => url('/'), 'priority' => '1.0'],
            ['loc' => url('/comprar'), 'priority' => '0.9'],
            ['loc' => url('/gracias'), 'priority' => '0.3'],
        ];

        $xml = view('site.sitemap', compact('urls'))->render();

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
