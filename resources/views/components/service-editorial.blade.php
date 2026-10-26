@php
    $serviceOrder = ['complete-home-interior-design', 'modular-kitchen-design', 'luxury-bedroom-interiors', 'living-dining-spaces', 'wardrobe-storage-solutions', 'office-commercial-interiors', 'false-ceiling-lighting-design', 'turnkey-interior-solutions'];
    $displayServices = $services->sortBy(fn ($service) => array_search($service->slug, $serviceOrder) === false ? 99 : array_search($service->slug, $serviceOrder))->values();
    $serviceReferences = [
        'modular-kitchen-design' => ['/images/kitchen.jpg', '/images/indian-kitchen.jpg'],
        'wardrobe-storage-solutions' => ['/images/detail.jpg', '/images/indian-wardrobe.jpg'],
        'false-ceiling-lighting-design' => ['/images/living.jpg', '/images/materials.jpg'],
        'turnkey-interior-solutions' => ['/images/kitchen.jpg', '/images/story.jpg'],
    ];
@endphp
<section class="service-editorial section" id="expertise">
    <div class="wrap">
        <div class="section-heading reveal"><div><span class="eyebrow">DESIGNED FOR YOUR WAY OF LIVING</span><h2>Everything Your<br><em>Dream Space Needs.</em></h2></div><div><p>From a single room to your complete home, bring every detail together with a considered design approach.</p>@if($page==='home')<a class="text-link" href="/services">Explore all services <span aria-hidden="true">↗</span></a>@endif</div></div>
        <div class="service-composition">
            @foreach($page==='home' ? $displayServices->take(8) : $displayServices as $service)
                @php
                    $reference = $serviceReferences[$service->slug] ?? null;
                    $serviceImage = $reference && $service->image === $reference[0] ? $reference[1] : $service->image;
                @endphp
                <a class="service-feature service-feature-{{ $loop->iteration }}" href="/services/{{ $service->slug }}"><div class="image-frame" data-mask><x-editorial-image :src="$serviceImage" :alt="$service->title.' — interior inspiration'" sizes="(max-width: 767px) 100vw, (max-width: 1199px) 50vw, 33vw" /><div class="service-overlay"><span class="eyebrow">{{ str_pad($loop->iteration,2,'0',STR_PAD_LEFT) }} / DESIGN SOLUTIONS</span><h3>{{ $service->title }}</h3><span class="round-arrow" aria-hidden="true">↗</span></div></div><p>{{ $service->description }}</p></a>
            @endforeach
        </div>
        
    </div>
</section>
