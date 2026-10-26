<section class="process-journey section wrap" id="process">
    <div class="section-heading reveal"><div><span class="eyebrow">FROM FIRST IDEAS TO FINAL DETAILS</span><h2>Your space.<br><em>A considered journey.</em></h2></div><div><p>Know what comes next. A collaborative approach to planning, material choices and bringing your design to life.</p><a class="text-link" href="/contact">Start with a consultation <span aria-hidden="true">↗</span></a></div></div>
    <div class="journey-stages"><div class="journey-track" aria-hidden="true"><span class="journey-progress"></span></div>
        @foreach(($page==='home' ? $processSteps->take(3) : $processSteps) as $step=>$text)
            <article class="journey-stage"><div class="journey-photo" data-mask><x-editorial-image :src="'/images/'.(['story','living','bedroom','craft','studio','dining'][$loop->index % 6]).'.jpg'" :alt="$step.' — illustrative interior reference'" sizes="(max-width: 767px) 100vw, 45vw" /></div><div class="journey-stage-body reveal"><span class="journey-number">{{ str_pad($loop->iteration,2,'0',STR_PAD_LEFT) }}</span><span class="eyebrow">YOUR DESIGN JOURNEY</span><h3>{{ $step }}</h3><p>{{ $text }}</p></div></article>
        @endforeach
    </div>
    @if($page==='home')<a class="text-link process-more" href="/design-process">Explore the complete design journey <span aria-hidden="true">↗</span></a>@endif
</section>
