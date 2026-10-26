@extends('layouts.studio')
@section('content')
@if($page==='about')
@include('frontend.about')
@else
@php
    $hero=$banners->first();
    $defaultHero = !$hero || ($hero->slug==='home-hero' && $hero->title==='Where spaces' && $hero->content==='become stories.');
    $heroImage=($defaultHero && (!$hero || $hero->image==='/images/living.jpg')) ? '/images/indian-living.jpg' : ($hero?->image ?? '/images/indian-living.jpg');
@endphp
@if($page==='home')
<section class="hero cinematic-hero">
    <x-editorial-image :src="$heroImage" :alt="$heroImage==='/images/indian-living.jpg' ? 'AI-generated Indian luxury living-room concept with walnut joinery and marble' : ($hero?->title ?? 'Interior design inspiration')" :eager="true" sizes="100vw" class="hero-image" />
    <div class="hero-shade"></div>
    <div class="hero-content wrap">
        <div class="hero-kicker"><span class="short-line"></span><span class="eyebrow">{{ $defaultHero ? 'ADORE INTERIOR · AHMEDABAD' : $hero->description }}</span></div>
        <h1 data-word-reveal>{{ $defaultHero ? 'Beautiful Homes.' : $hero->title }}<br><em>{{ $defaultHero ? 'Thoughtful Interiors.' : $hero->content }}</em></h1>
        <p class="hero-description">From modular kitchens to complete luxury homes, we create interiors designed around your lifestyle.</p>
        <div class="button-row hero-actions"><a href="/contact" class="button bronze">Book a Consultation <span aria-hidden="true">↗</span></a><a href="{{ route('calculator.index') }}" class="button glass">Estimate Interior Cost <span aria-hidden="true">↗</span></a></div>
        <div class="hero-assurance"><span>Personalized for your lifestyle</span><span>Residential & commercial</span></div>
    </div>
    <div class="hero-bottom wrap"><a href="#expertise"><span class="scroll-track" aria-hidden="true">↓</span>Discover your possibilities</a><span>DESIGNED AROUND YOU.<br>ROOTED IN AHMEDABAD.</span></div>
</section>
<div class="home-benefits wrap"><div><span class="benefit-symbol" aria-hidden="true">01</span><span><strong>Your home. Your way.</strong>Design around your everyday life</span></div><div><span class="benefit-symbol" aria-hidden="true">02</span><span><strong>From ideas to interiors</strong>Planning, materials & execution</span></div><a href="/contact"><span class="benefit-symbol" aria-hidden="true">↗</span><span><strong>Meet us in Ahmedabad</strong>Science City · Let's talk interiors</span></a></div>
@endif

@if(in_array($page,['home','about']))
@if($page==='about')<section class="page-title story-title wrap"><span class="eyebrow">THE STUDIO / AHMEDABAD</span><h1 data-word-reveal>Good design begins<br>with <em>your story.</em></h1><p>An Ahmedabad interior studio creating comfortable homes and purposeful workplaces, with your lifestyle at the heart of every decision.</p><span class="title-index" aria-hidden="true">01 — THE ADORE STORY</span></section>@endif
<section class="intro editorial-intro section wrap" id="introduction">
    <div class="intro-visual"><div class="intro-image" data-mask><x-editorial-image src="/images/story.jpg" alt="Layered natural textures and sculptural details in a contemporary interior reference" /><span class="image-caption">A DIALOGUE BETWEEN SPACE, LIGHT & LIFE</span></div><div class="intro-detail" data-mask><x-editorial-image src="/images/craft.jpg" alt="Sculptural furniture reference — the detail within the whole" /><span>THE DETAIL WITHIN THE WHOLE.</span></div></div>
    <div class="intro-copy reveal"><span class="eyebrow">WELCOME TO ADORE INTERIOR</span><h2>A home that looks beautiful.<br><em>And lives beautifully.</em></h2><p class="lead">From a first apartment to a family villa, thoughtful interiors make room for the life you want to live.</p><p>{{ $copy?->description ?? 'We shape complete interiors around the people who live and work within them. A thoughtful balance of natural warmth, quiet confidence and beautifully resolved details.' }}</p>@if($copy?->content)<div class="preserve studio-story">{{ $copy->content }}</div>@endif<a class="text-link" href="{{ $page==='about'?'/contact':'/about' }}">{{ $page==='about'?'Come meet the studio':'Discover our studio' }} <span>↗</span></a></div>
</section>
@if($page==='about') @include('components.studio-approach') <figure class="studio-panorama wrap"><x-editorial-image src="/images/living.jpg" alt="Contemporary living-room reference with warm natural materials" sizes="100vw" /><figcaption>RESIDENTIAL DESIGN INSPIRATION · REFERENCE PHOTOGRAPHY</figcaption></figure> @endif
@endif

@if(in_array($page,['home','services'])&&!$record)
@if($page==='services')
<section class="services-hero" aria-labelledby="services-hero-heading"><div class="wrap services-hero-layout">
    <div class="services-hero-copy"><div class="services-hero-kicker"><span aria-hidden="true"></span><span class="eyebrow">OUR EXPERTISE / YOUR POSSIBILITIES</span></div><h1 id="services-hero-heading" data-word-reveal>Beautifully designed.<br><span>Completely yours.</span></h1><p>From a thoughtfully planned kitchen to an entire home, we bring space, materials and everyday comfort together. Designed around your life. Rooted in Indian warmth.</p><div class="services-hero-actions"><a class="button" href="{{ url('/contact') }}">Book a Consultation <span aria-hidden="true">↗</span></a><a class="services-explore-link" href="#expertise">Explore Our Services <span aria-hidden="true">↓</span></a></div><div class="services-hero-signature"><span class="services-signature-mark" aria-hidden="true">ai</span><span>MODERN LUXURY. INDIAN WARMTH.<small>Residential & commercial interiors · Ahmedabad</small></span></div></div>
    <div class="services-hero-visual"><figure class="services-hero-room" data-mask><x-editorial-image src="/images/indian-living.jpg" alt="AI-generated luxury Indian living room concept with walnut panels, marble and warm neutral furnishings" :eager="true" sizes="(max-width: 767px) 100vw, 60vw" /><span class="services-room-label">A CONSIDERED APPROACH TO LIVING</span><figcaption>Your space.<br>A beautiful new perspective.</figcaption></figure><figure class="services-hero-detail" data-mask><x-editorial-image src="/images/indian-kitchen.jpg" alt="AI-generated contemporary Indian modular kitchen concept" sizes="(max-width: 767px) 45vw, 20vw" /><figcaption>FUNCTION, BEAUTIFULLY RESOLVED.</figcaption></figure></div>
</div><div class="services-hero-bottom wrap"><span>FROM THE FIRST IDEA TO THE FINISHING DETAILS</span><div><span>Space planning</span><i aria-hidden="true"></i><span>Material guidance</span><i aria-hidden="true"></i><span>Design coordination</span></div><a href="{{ route('calculator.index') }}">Estimate Interior Cost <span aria-hidden="true">↗</span></a></div></section>
@endif
@include('components.service-editorial')
@endif

@if(in_array($page,['home','projects'])&&!$record)
@if($page==='projects')
@include('frontend.projects-hero')
@endif
@include('components.project-collection')
@endif

@if($record)
<section class="detail-hero case-hero"><x-editorial-image :src="$record->image" :alt="$record->title" :eager="true" sizes="100vw" /><div class="wrap"><span class="eyebrow">{{ $record->is_sample?'DESIGN CONCEPT / ILLUSTRATIVE INSPIRATION':strtoupper($page).' / ADORE INTERIOR' }}</span><h1 data-word-reveal>{{ $record->title }}</h1><div class="case-hero-meta"><span>{{ $record->category?->title ?? 'INTERIOR DESIGN' }}</span><span>{{ $record->location ?: 'A CONSIDERED PERSPECTIVE' }}</span><a href="#design-story">Discover the story ↓</a></div></div></section>
<section class="section wrap detail-intro" id="design-story"><div><span class="eyebrow">THE SPACE, IN DETAIL</span><h2>Designed for life.<br><em>Considered in detail.</em></h2>@if($record->is_sample)<p class="sample-note">An illustrative concept using reference photography. This is not a completed Adore Interior client project.</p>@endif</div><div><p class="lead">{{ $record->description }}</p><p class="preserve">{{ $record->content }}</p><div class="case-facts">@if($record->category)<div><span>SPACE</span><strong>{{ $record->category->title }}</strong></div>@endif @if($record->location)<div><span>LOCATION</span><strong>{{ $record->location }}</strong></div>@endif<div><span>STUDIO</span><strong>Adore Interior</strong></div></div><a class="button dark" href="/contact?{{ $page==='projects'?'project':'service' }}={{ urlencode($record->title) }}">Let’s discuss your space <span>↗</span></a></div></section>
@if($page==='projects')<section class="wrap detail-gallery case-gallery">@foreach($record->images as $photo)<figure data-mask><x-editorial-image :src="$photo->path" :alt="$photo->alt" /><figcaption><span>{{ str_pad($loop->iteration,2,'0',STR_PAD_LEFT) }} / THE DESIGN STUDY</span><span>{{ $photo->alt }}</span></figcaption></figure>@endforeach</section><section class="section wrap related-projects"><div class="section-heading"><div><span class="eyebrow">CONTINUE THE CONVERSATION</span><h2>Another <em>perspective.</em></h2></div><a class="text-link" href="/projects">The complete collection ↗</a></div><div class="project-grid">@foreach($projects->where('id','!=',$record->id)->take(2) as $project)@include('components.project-card')@endforeach</div></section>@else<section class="wrap section"><span class="eyebrow">THE WHOLE PICTURE</span><h2>More ways to<br><em>make it yours.</em></h2><div class="service-links">@foreach($services->where('id','!=',$record->id) as $service)<a href="/services/{{ $service->slug }}">{{ $service->title }} <span>↗</span></a>@endforeach</div></section>@endif
@endif

@if(in_array($page,['home','about']))
<section class="philosophy editorial-philosophy"><div class="philosophy-image" data-mask><x-editorial-image src="/images/materials.jpg" alt="Material, light and texture study for considered interiors" /><span class="photograph-note">REFERENCE STUDY / TEXTURE & TONE</span></div><div class="philosophy-copy"><span class="eyebrow">THE WAY WE DESIGN</span><h2>Beautiful choices.<br><em>Thoughtfully brought together.</em></h2><p>Luxury lives in the things you feel. A material’s warmth. A well-placed light. A room that simply works.</p><div class="principles-list">@foreach($principles as $heading=>$text)<details class="principle editorial-accordion" @if($loop->first) open @endif><summary><span class="principle-num">0{{ $loop->iteration }}</span><h3>{{ $heading }}</h3><span class="accordion-mark" aria-hidden="true">+</span></summary><div class="accordion-body"><div><p>{{ $text }}</p></div></div></details>@endforeach</div></div></section>
@endif

@if($page==='home'&&$comparisons->where('is_sample', false)->filter(fn ($item) => $item->image && $item->secondary_image && $item->image !== $item->secondary_image)->isNotEmpty())
<section class="transformation section wrap"><div class="section-heading reveal"><div><span class="eyebrow">05 / A CHANGE IN PERSPECTIVE</span><h2>The same space.<br><em>A different feeling.</em></h2></div><p>Explore the difference with a simple gesture.<br>Drag the line. Discover the details.</p></div>@foreach($comparisons->where('is_sample', false)->filter(fn ($item) => $item->image && $item->secondary_image && $item->image !== $item->secondary_image) as $comparison) @include('components.comparison') @endforeach</section>
@endif
@if(in_array($page,['home','design-process']))
@if($page==='design-process')
@include('frontend.process-hero')
@endif
@include('components.process')
@endif

@if(in_array($page,['home','about']))
<section class="why editorial-why section" aria-labelledby="why-heading">
    <div class="wrap why-composition">
        <div class="why-heading"><span class="eyebrow">THE DIFFERENCE IS PERSONAL / THE ADORE WAY</span><h2 id="why-heading">Considered spaces.<br><em>Extraordinary everyday.</em></h2><p>More than a beautiful room. A thoughtful experience, shaped around you.</p></div>
        <div class="why-moodboard">
            <figure class="why-main-photo" data-mask><x-editorial-image src="/images/indian-living.jpg" alt="Warm living room with walnut finishes, marble and soft natural light" sizes="(max-width: 767px) 100vw, 45vw" /><figcaption>A FEELING. BEFORE A FINISH.</figcaption></figure>
            <figure class="why-detail-photo" data-mask><x-editorial-image src="/images/craft.jpg" alt="Sculptural furniture and carefully considered interior details" sizes="(max-width: 767px) 40vw, 18vw" /><figcaption>The beauty is in the detail.</figcaption></figure>
            <div class="why-materials"><span>THE PALETTE OF HOME</span><div aria-hidden="true"><i></i><i></i><i></i><i></i></div><small>Warmth / Texture / Balance</small></div>
            <span class="why-seal" aria-hidden="true">Made<br><em>personal.</em></span>
        </div>
        <div class="why-grid">@foreach($features as $heading=>$text)<article class="reveal"><span class="feature-number">{{ str_pad($loop->iteration,2,'0',STR_PAD_LEFT) }}</span><div><h3>{{ $heading }}</h3><p>{{ $text }}</p></div></article>@endforeach</div>
        <div class="why-signoff"><span>YOUR STORY. OUR ATTENTION TO EVERY DETAIL.</span><a class="text-link" href="/contact">Let's create something personal <span aria-hidden="true">?</span></a></div>
    </div>
</section>
@endif

@if($page==='home')
@include('components.materials')
@include('components.studio-visit')
@if($testimonials->count())<section class="testimonials section wrap"><span class="eyebrow">STORIES BEHIND THE SPACES</span><h2>In their <em>words.</em></h2><div class="swiper"><div class="swiper-wrapper">@foreach($testimonials as $testimonial)<figure class="swiper-slide"><blockquote>“{{ $testimonial->content }}”</blockquote><figcaption>{{ $testimonial->title }} · {{ $testimonial->description }}</figcaption></figure>@endforeach</div><div class="swiper-pagination"></div><button class="swiper-button-prev" aria-label="Previous testimonial"></button><button class="swiper-button-next" aria-label="Next testimonial"></button></div></section>@endif
@endif

@if($page==='contact')
@include('frontend.contact')
@endif
@if($page==='about') @include('components.materials') @include('components.studio-visit') @endif
@if(in_array($page,['privacy','terms']))
<section class="page-title wrap"><span class="eyebrow">ADORE INTERIOR</span><h1>{{ $page==='privacy'?'Privacy':'Terms &' }} <em>{{ $page==='privacy'?'policy.':'conditions.' }}</em></h1></section><article class="legal wrap section">@if($copy)<p class="lead">{{ $copy->description }}</p><div class="preserve">{{ $copy->content }}</div>@elseif($page==='privacy')<h2>Your information</h2><p>When you submit an inquiry, we collect the contact and project information you provide to respond and discuss your design needs. Please avoid sending sensitive personal information.</p><h2>How we use and retain it</h2><p>We use inquiry information for project communication and administration. Access is restricted to authorized studio staff and service providers required to operate this website. Contact us to request access, correction or deletion of your inquiry information.</p><h2>Cookies and third parties</h2><p>This website uses essential session and security cookies. Google Maps and externally hosted fonts may receive your IP address when loaded. WhatsApp links take you to a third-party service governed by its own policies.</p><h2>Contact</h2><p>For privacy requests, email <a href="mailto:Adoreinterior4@gmail.com">Adoreinterior4@gmail.com</a>.</p>@else<h2>Using this website</h2><p>This website introduces Adore Interior’s design services. Submitting an inquiry does not create a service contract or confirm project availability.</p><h2>Designs and imagery</h2><p>Sample concepts and stock imagery are labeled for inspiration. They do not represent completed Adore Interior projects. Website content may not be reproduced without permission from the relevant rights holder.</p><h2>Project engagements</h2><p>Scope, fees, timelines, materials, payment terms and responsibilities will be agreed separately in a written project agreement. Website information is general and may change.</p><h2>Contact</h2><p>Please direct questions to <a href="mailto:Adoreinterior4@gmail.com">Adoreinterior4@gmail.com</a>.</p>@endif</article>
@endif
@if(in_array($page,['home','services','projects'])) @include('components.cost-calculator-cta') @endif
@endif
@endsection
