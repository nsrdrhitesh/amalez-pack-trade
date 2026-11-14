@if($content->gallery->isNotEmpty())<section class="pack-section pack-container site-gallery" x-data="imageGallery({{ $content->gallery->count() }})" @mouseenter="paused=true" @mouseleave="paused=false" @focusin="paused=true" @focusout="paused=false" aria-roledescription="carousel" aria-label="{{ $content->title }} image gallery"><div class="site-section-heading"><span class="pack-kicker">FORM / DETAIL / PRESENTATION</span><h2>{{ $galleryTitle??'A closer look.' }}</h2></div><div class="site-gallery-frame">
@foreach($content->gallery  as $slide)<figure x-show="index==={{ $loop->index }}" @if(!$loop->first)x-cloak
@endif><img src="{{ $slide->media->url }}" alt="{{ $slide->media->alt }}" width="{{ $slide->media->width }}" height="{{ $slide->media->height }}" loading="lazy">
@if($slide->caption??$slide->media->caption)<figcaption>{{ $slide->caption??$slide->media->caption }}</figcaption>
@endif</figure>
@endforeach</div>
@if($content->gallery->count()>1)<div class="site-gallery-controls"><button @click="previous()" aria-label="Previous image">←</button><span x-text="(index+1)+' / '+count" aria-live="polite"></span><button @click="next()" aria-label="Next image">→</button><button @click="playing=!playing" :aria-pressed="playing" x-text="playing?'Pause slideshow':'Play slideshow'"></button></div>
@endif</section>
@endif
