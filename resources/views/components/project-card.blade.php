@php
    $projectImage=$project->image;
    $referenceImages=['the-still-residence'=>['/images/living.jpg','/images/villa.jpg'],'a-quiet-retreat'=>['/images/bedroom.jpg','/images/retreat.jpg'],'the-contemporary-kitchen'=>['/images/kitchen.jpg','/images/kitchen-study.jpg'],'room-to-create'=>['/images/office.jpg','/images/studio.jpg']];
    if($project->is_sample && isset($referenceImages[$project->slug]) && $projectImage===$referenceImages[$project->slug][0]){$projectImage=$referenceImages[$project->slug][1];}
    if ($page === 'projects' && !$record && $project->is_sample) {
        $indianConceptImages = ['the-still-residence' => ['/images/living.jpg', '/images/indian-living.jpg'], 'the-contemporary-kitchen' => ['/images/kitchen.jpg', '/images/indian-kitchen.jpg']];
        if (isset($indianConceptImages[$project->slug]) && $project->image === $indianConceptImages[$project->slug][0]) {
            $projectImage = $indianConceptImages[$project->slug][1];
        }
    }
@endphp
<article class="project-card" data-category="{{ $project->project_category_id }}"><a href="/projects/{{ $project->slug }}"><div class="image-frame" data-mask><x-editorial-image :src="$projectImage" :alt="$project->title.($project->is_sample?' — interior design concept':'')" />@if($project->is_sample)<span class="image-tag">DESIGN CONCEPT</span>@endif<span class="project-image-number" aria-hidden="true">{{ $projectNumber??'↗' }}</span><span class="image-arrow" aria-hidden="true">↗</span></div><div class="card-meta"><div><span class="eyebrow">{{ $project->category?->title ?? 'INTERIOR DESIGN' }} @if($project->location) / {{ $project->location }} @endif</span><h3>{{ $project->title }}</h3><p class="project-description">{{ $project->description }}</p><span class="project-view">View Project <span aria-hidden="true">↗</span></span></div><span aria-hidden="true">↗</span></div></a></article>
