@props(['src', 'alt', 'eager' => false, 'sizes' => '(max-width: 767px) 100vw, 60vw'])
<img src="{{ $src }}" srcset="{{ \App\Support\StudioImage::srcset($src) }}" sizes="{{ $sizes }}" alt="{{ $alt }}" loading="{{ $eager ? 'eager' : 'lazy' }}" @if($eager) fetchpriority="high" @endif width="1600" height="1200" {{ $attributes }}>
