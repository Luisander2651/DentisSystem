@props([
	'as' => 'h1',
])

@php
	$base = 'text-3xl sm:text-4xl lg:text-5xl font-semibold tracking-tight text-[#E91E63]';
@endphp

<{{ $as }} {{ $attributes->merge(['class' => $base]) }}>
	{{ $slot }}
</{{ $as }}>
