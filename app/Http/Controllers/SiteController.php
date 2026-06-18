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
            'Disallow: /admin/',
            'Disallow: /api',
            'Disallow: /api/',
            'Disallow: /gracias',
            '',
            'User-agent: GPTBot',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /api',
            '',
            'Sitemap: '.url('/sitemap.xml'),
        ];

        return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(): Response
    {
        $now = now()->toAtomString();
        $urls = [
            ['loc' => url('/'), 'priority' => '1.0', 'lastmod' => $now],
            ['loc' => url('/comprar'), 'priority' => '0.9', 'lastmod' => $now],
            ['loc' => route('site.download'), 'priority' => '0.8', 'lastmod' => $now],
        ];

        $xml = view('site.sitemap', compact('urls'))->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function llms(): Response
    {
        $content = view('site.llms')->render();

        return response($content, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
