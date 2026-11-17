<?php

declare(strict_types=1);

namespace App\Core\Http;

use Illuminate\Http\Response;

/**
 * Spec 026 (CA12): /sitemap.xml with the public pages config/site.php marks as indexable, as
 * absolute addresses without a trailing slash, the same as their canonical address. Nothing of the
 * sign-in pages, the panel or the portal is listed, because only that list is read.
 */
final readonly class ShowSitemapController
{
    public function __invoke(): Response
    {
        $locations = array_map(
            fn (string $path): string => rtrim(config('site.url').'/'.ltrim($path, '/'), '/'),
            config('site.indexable', []),
        );

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"
            .implode('', array_map(fn (string $location): string => '  <url><loc>'.htmlspecialchars($location, ENT_XML1).'</loc></url>'."\n", $locations))
            .'</urlset>'."\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
