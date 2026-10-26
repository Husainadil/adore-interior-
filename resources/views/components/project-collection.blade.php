<section class="portfolio-section section wrap {{ $page==='projects' ? 'indian-collection' : '' }}" x-data="{category:'all'}" x-init="$watch('category', () => $nextTick(() => window.dispatchEvent(new Event('portfolio:filter'))))" id="selected-work">
    @if($page==='projects')
    <div class="collection-masthead"><span>THE ADORE DESIGN JOURNAL</span><span>AHMEDABAD / A CONSIDERED PERSPECTIVE</span></div>
    <div class="collection-intro"><div><span class="eyebrow">INSPIRED BY THE WAY INDIA LIVES</span><h2>Contemporary spaces.<br><em>An Indian sensibility.</em></h2><p>Warm materials. Meaningful details. Room for the way you live. Explore a collection of ideas for your next chapter.</p></div><figure class="collection-detail"><x-editorial-image src="/images/indian-jaali.jpg" alt="AI-generated Indian home concept with a wooden jaali screen and marble pooja niche" sizes="(max-width: 767px) 100vw, 28vw" /><figcaption>FAMILIAR ROOTS. A FRESH EXPRESSION.</figcaption></figure></div>
    @else
    <div class="section-heading reveal"><div><span class="eyebrow">{{ $page==='projects' ? 'THE DESIGN EDIT / EXPLORE BY SPACE' : 'SPACES TO INSPIRE YOUR NEXT CHAPTER' }}</span><h2>Ideas you’ll love.<br><em>Spaces you’ll imagine.</em></h2></div><div><p>Explore our interior concept collection. From welcoming living rooms to thoughtfully planned kitchens, find your starting point.</p>@if($page==='home')<a class="text-link" href="/projects">Explore the collection <span>↗</span></a>@endif</div></div>
    @endif
    @if($projects->contains('is_sample',true))@endif
    @if($page==='projects')<div class="filters" role="group" aria-label="Filter projects"><button @click="category='all'" :class="{'selected':category==='all'}" :aria-pressed="category==='all'">All spaces <sup>{{ $projects->count() }}</sup></button>@foreach($categories->filter(fn ($category) => $projects->contains('project_category_id', $category->id)) as $cat)<button @click="category='{{ $cat->id }}'" :class="{'selected':category==='{{ $cat->id }}'}" :aria-pressed="category==='{{ $cat->id }}'">{{ $cat->title }}</button>@endforeach</div><p class="filter-feedback" aria-live="polite" x-text="category==='all'?'Showing all spaces':'Showing '+(({{ Js::from($projects->groupBy('project_category_id')->map->count()) }})[category]||0)+' spaces'"></p>@endif
    <div class="project-grid editorial-projects" :class="{'is-filtered':category!=='all'}">
        @foreach($page==='home'?$projects->take(4):$projects as $project)
        <div class="project-slot" x-show="category==='all'||category==='{{ $project->project_category_id }}'" x-transition:enter="project-enter" x-transition:enter-start="project-enter-start" x-transition:enter-end="project-enter-end">
            @include('components.project-card', ['projectNumber'=>str_pad($loop->iteration,2,'0',STR_PAD_LEFT)])
        </div>
        @endforeach
    </div>
</section>
