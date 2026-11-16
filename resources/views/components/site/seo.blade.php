{{--
    The <head> of the public site for search engines and social networks (spec 026, CA11, CA13,
    CA15): the page's title and description (from its own @section, the only source), the
    canonical address without a trailing slash or query string, Open Graph, the browser bar colour
    (--color-primary) and, on the pages config/site.php marks, the clinic's structured data
    (Dentist) without any value that is still provisional. The JSON is encoded with JSON_HEX_TAG so
    no value can close its script (TM2). It also preloads the two fonts of the first screen.
--}}
@props(['title', 'description' => ''])
@php
    $path = '/'.trim(request()->path(), '/');
    $canonical = rtrim(config('site.url').$path, '/');
    $page = config('site.pages')[$path] ?? [];
    $structuredData = null;

    if ($page['structured_data'] ?? false) {
        $provisional = config('site.provisional', []);
        $contact = config('site.contact');
        $structuredData = ['@context' => 'https://schema.org', '@type' => 'Dentist', 'name' => config('site.name'), 'url' => config('site.url')];

        if (! ($provisional['address'] ?? true)) {
            $structuredData['address'] = ['@type' => 'PostalAddress', 'streetAddress' => $contact['address'], 'addressLocality' => config('site.city'), 'addressCountry' => 'MX'];
        }

        if (! ($provisional['phone'] ?? true)) {
            $structuredData['telephone'] = $contact['phone'];
        }

        if (! ($provisional['schedule'] ?? true)) {
            $dayNames = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
            $structuredData['openingHoursSpecification'] = collect($contact['schedule'])
                ->map(fn (array $hours, int $day): array => ['day' => $dayNames[$day], 'opens' => $hours[0], 'closes' => $hours[1]])
                ->groupBy(fn (array $entry): string => $entry['opens'].'-'.$entry['closes'])
                ->map(fn ($entries): array => ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => $entries->pluck('day')->all(), 'opens' => $entries->first()['opens'], 'closes' => $entries->first()['closes']])
                ->values()
                ->all();
        }
    }
@endphp
<title>{{ $title }}</title>
@if ($description !== '')
    <meta name="description" content="{{ $description }}">
@endif
<link rel="canonical" href="{{ $canonical }}">
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ config('site.name') }}">
<meta property="og:locale" content="es_MX">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:title" content="{{ $title }}">
@if ($description !== '')
    <meta property="og:description" content="{{ $description }}">
@endif
<meta name="theme-color" content="{{ config('site.theme_color') }}">
<link rel="preload" href="{{ Vite::asset('resources/fonts/inter-tight-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="{{ Vite::asset('resources/fonts/instrument-serif-italic-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
@if ($structuredData)
    <script type="application/ld+json">{!! json_encode($structuredData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!}</script>
@endif
