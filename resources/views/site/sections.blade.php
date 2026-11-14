@foreach($content->sections  as $section)
@if($section->visible())
@if($section->type==='services')
@if(($featuredServices??collect())->isNotEmpty())<section class="pack-section pack-container"><div class="site-section-heading"><span class="pack-kicker">FORM / FUNCTION / POSSIBILITIES</span><h2>{{ $section->title }}</h2><div class="site-prose">{!! $section->body !!}</div></div><div class="site-card-grid">
@foreach($featuredServices  as $service)
@include('site.service-card')
@endforeach</div><a class="pack-text-link" href="{{ url('/services') }}">All services <x-frontend::arrow/></a></section>
@endif
@elseif($section->type==='posts')
@if(($recentPosts??collect())->isNotEmpty())<section class="pack-section site-section-neutral"><div class="pack-container"><div class="site-section-heading"><span class="pack-kicker">THE PACKTRADE JOURNAL</span><h2>{{ $section->title }}</h2><div class="site-prose">{!! $section->body !!}</div></div><div class="site-card-grid">
@foreach($recentPosts  as $post)
@include('site.post-card')
@endforeach</div></div></section>
@endif
@elseif($section->type==='faq')<section class="pack-container site-faq"><details><summary>{{ $section->title }}</summary><div class="site-prose">{!! $section->body !!}</div></details></section>
@elseif($section->type==='cta')<section class="pack-container site-cta"><span class="pack-kicker">YOUR NEXT PACKAGING CONVERSATION</span><h2>{{ $section->title }}</h2><div class="site-prose">{!! $section->body !!}</div><a class="pack-button pack-button-orange" href="{{ url('/contact') }}">Discuss your requirement <x-frontend::arrow/></a></section>
@elseif($section->type==='gallery')
@include('site.gallery',['galleryTitle'=>$section->title])
@else<section class="pack-section pack-container {{ $section->media?'site-split':'' }}">
@if($section->media)<figure><img src="{{ $section->media->url }}" alt="{{ $section->media->alt }}" width="{{ $section->media->width }}" height="{{ $section->media->height }}" loading="lazy">
@if($section->media->caption)<figcaption>{{ $section->media->caption }}</figcaption>
@endif</figure>
@endif<div><span class="pack-kicker">A CONSIDERED APPROACH</span>
@if($section->title)<h2 class="site-section-title">{{ $section->title }}</h2>
@endif<div class="site-prose">{!! $section->body !!}</div></div></section>
@endif
@endif @endforeach
