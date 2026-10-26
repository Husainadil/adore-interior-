<section class="materials-section section"><div class="wrap">
    <div class="section-heading reveal"><div><span class="eyebrow">THE DETAILS MAKE THE DIFFERENCE</span><h2>Materials you see.<br><em>Quality you feel.</em></h2></div><div><p>Surface, texture and light shape a room’s character. We help you explore the right balance of finish, everyday practicality and budget.</p></div></div>
    <div class="materials-grid">
        @foreach([
            ['finish-palette','Wood & designer laminates','Natural warmth, grain and considered joinery bring depth to your interior.','WOOD / TEXTURE'],
            ['kitchen','Marble & quartz surfaces','Explore surface options with maintenance, usage and the chosen specification in mind.','STONE / SURFACE'],
            ['materials','Lighting, fabrics & hardware','Layered illumination, upholstery and finishing details bring the whole palette together.','LIGHT / FINISH']
        ] as [$image,$heading,$description,$label])
            <article class="material-card reveal"><div class="material-image" data-mask><x-editorial-image :src="'/images/'.$image.'.jpg'" :alt="$heading.' — illustrative material and interior reference'" sizes="(max-width: 767px) 100vw, 33vw" /><span>{{ $label }}</span></div><h3>{{ $heading }}</h3><p>{{ $description }}</p></article>
        @endforeach
    </div>
    <div class="material-guidance"><div class="material-swatches" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></div><p>Material specifications, measured quantities and finish choices influence your estimate. Final selections are discussed during consultation.</p><a class="text-link" href="{{ route('calculator.index') }}">Explore your budget <span aria-hidden="true">↗</span></a></div>
</div></section>
