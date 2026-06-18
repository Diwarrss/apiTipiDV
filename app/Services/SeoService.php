<?php

declare(strict_types=1);

namespace App\Services;

final class SeoService
{
    /** @return array<int, array<string, mixed>> */
    public function graphForPage(string $page, string $description, ?float $lowPriceCop = null): array
    {
        $graph = [
            $this->organizationSchema(),
            $this->webSiteSchema(),
        ];

        if ($page === 'home') {
            $graph[] = $this->softwareApplicationSchema($description, $lowPriceCop);
            $graph = array_merge($graph, $this->faqSchemas());
        }

        if ($page === 'comprar') {
            $graph[] = $this->productOfferSchema($description, $lowPriceCop);
            $graph[] = $this->breadcrumbSchema([
                ['name' => 'Inicio', 'url' => url('/')],
                ['name' => 'Comprar licencia', 'url' => url('/comprar')],
            ]);
        }

        return $graph;
    }

    public function ogImageUrl(?string $override = null): string
    {
        $path = $override ?? (string) config('marketing.seo.og_image', 'images/tipidv-og.png');

        return $this->absoluteAsset($path);
    }

    public function absoluteAsset(string $path): string
    {
        $path = ltrim($path, '/');

        return str_starts_with($path, 'http') ? $path : url($path);
    }

    /** @return array<string, mixed> */
    public function organizationSchema(): array
    {
        $siteName = (string) config('marketing.site_name', 'TipiDV');

        return [
            '@type' => 'Organization',
            '@id' => url('/#organization'),
            'name' => $siteName,
            'url' => url('/'),
            'logo' => $this->absoluteAsset((string) config('marketing.seo.logo', 'images/tipidv-logo.png')),
            'email' => config('marketing.contact_email'),
            'telephone' => config('marketing.contact_phone'),
            'address' => [
                '@type' => 'PostalAddress',
                'addressCountry' => 'CO',
            ],
            'founder' => [
                '@type' => 'Person',
                'name' => config('marketing.author'),
                'url' => config('marketing.author_url'),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function webSiteSchema(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => url('/#website'),
            'name' => config('marketing.site_name', 'TipiDV'),
            'url' => url('/'),
            'description' => config('marketing.seo.description'),
            'inLanguage' => 'es-CO',
            'publisher' => ['@id' => url('/#organization')],
        ];
    }

    /** @return array<string, mixed> */
    public function softwareApplicationSchema(string $description, ?float $lowPriceCop): array
    {
        $schema = [
            '@type' => 'SoftwareApplication',
            '@id' => url('/#software'),
            'name' => config('marketing.site_name', 'TipiDV'),
            'applicationCategory' => 'BusinessApplication',
            'applicationSubCategory' => 'DocumentManagementApplication',
            'operatingSystem' => 'Windows 10, Windows 11',
            'description' => $description,
            'url' => url('/'),
            'downloadUrl' => url('/descargar'),
            'screenshot' => $this->ogImageUrl(),
            'author' => [
                '@type' => 'Person',
                'name' => config('marketing.author'),
                'url' => config('marketing.author_url'),
            ],
            'publisher' => ['@id' => url('/#organization')],
            'offers' => $this->offerNode($lowPriceCop, url('/comprar')),
        ];

        return $schema;
    }

    /** @return array<string, mixed> */
    public function productOfferSchema(string $description, ?float $lowPriceCop): array
    {
        return [
            '@type' => 'Product',
            'name' => config('marketing.site_name', 'TipiDV').' — Licencia',
            'description' => $description,
            'brand' => ['@type' => 'Brand', 'name' => config('marketing.site_name', 'TipiDV')],
            'offers' => $this->offerNode($lowPriceCop, url('/comprar')),
        ];
    }

    /** @return array<string, mixed> */
    private function offerNode(?float $lowPriceCop, string $offerUrl): array
    {
        $offer = [
            '@type' => 'Offer',
            'priceCurrency' => 'COP',
            'availability' => 'https://schema.org/InStock',
            'url' => $offerUrl,
        ];

        if ($lowPriceCop !== null && $lowPriceCop > 0) {
            $offer['price'] = number_format($lowPriceCop, 0, '.', '');
            $offer['lowPrice'] = number_format($lowPriceCop, 0, '.', '');
        }

        return $offer;
    }

    /** @param array<int, array{name: string, url: string}> $items */
    public function breadcrumbSchema(array $items): array
    {
        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(
                static fn (array $item, int $i) => [
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'name' => $item['name'],
                    'item' => $item['url'],
                ],
                $items,
                array_keys($items),
            ),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function faqSchemas(): array
    {
        $items = config('marketing.faq', []);
        if (! is_array($items) || $items === []) {
            return [];
        }

        $mainEntity = [];
        foreach ($items as $item) {
            if (! is_array($item) || empty($item['question']) || empty($item['answer'])) {
                continue;
            }
            $mainEntity[] = [
                '@type' => 'Question',
                'name' => (string) $item['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => strip_tags((string) $item['answer']),
                ],
            ];
        }

        if ($mainEntity === []) {
            return [];
        }

        return [[
            '@type' => 'FAQPage',
            '@id' => url('/#faq'),
            'mainEntity' => $mainEntity,
        ]];
    }
}
