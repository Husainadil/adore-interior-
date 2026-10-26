<figure class="comparison-study" x-data="{position:50}">
    <div class="comparison" :style="'--comparison-position:'+position+'%'">
        <img src="{{ $comparison->secondary_image }}" alt="{{ $comparison->title }} — after" loading="lazy" width="1600" height="1000">
        <img class="before {{ $comparison->is_sample ? 'demo-monochrome' : null }}" src="{{ $comparison->image }}" :style="'clip-path:inset(0 '+(100-position)+'% 0 0)'" alt="{{ $comparison->title }} — before" loading="lazy" width="1600" height="1000">
        <span class="before-label">{{ $comparison->is_sample ? 'Monochrome · Demo' : 'Before' }}</span>
        <span class="after-label">{{ $comparison->is_sample ? 'Colour · Demo' : 'After' }}</span>
        <div class="comparison-line" :style="'left:'+position+'%'" aria-hidden="true"><span>↔</span></div>
        <input type="range" min="0" max="100" step="1" value="50" x-model.number="position" aria-label="{{ $comparison->title }}: compare before and after" :aria-valuetext="position+'% before, '+(100-position)+'% after'" aria-describedby="comparison-help-{{ $comparison->id }}">
    </div>
    <figcaption class="comparison-caption">
        <div><span class="eyebrow">{{ $comparison->title }}</span><p id="comparison-help-{{ $comparison->id }}">Drag to explore. Use arrow keys when focused.</p></div>
        <div class="comparison-actions" role="group" aria-label="Comparison views"><button type="button" @click="position=100">{{ $comparison->is_sample ? 'Monochrome' : 'Before' }}</button><button type="button" @click="position=50" aria-label="Reset comparison to halfway">50 / 50</button><button type="button" @click="position=0">{{ $comparison->is_sample ? 'Colour' : 'After' }}</button></div>
    </figcaption>
    @if($comparison->is_sample)<p class="sample-note">Interactive demo · The same stock image is shown in monochrome and colour. This is not a real renovation.</p>@endif
</figure>
