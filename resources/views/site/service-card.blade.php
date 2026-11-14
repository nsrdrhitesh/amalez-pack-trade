<article class="site-card"><a class="site-card-image" href="{{ $service->url }}">
@if($service->featuredMedia)<img src="{{ $service->featuredMedia->url }}" alt="{{ $service->featuredMedia->alt }}" width="{{ $service->featuredMedia->width }}" height="{{ $service->featuredMedia->height }}" loading="lazy">
@else<div class="site-image-empty">PackTrade / {{ $service->title }}</div>
@endif<span aria-hidden="true">↗</span></a><div class="site-card-body"><span class="pack-kicker">{{ $service->confirmed?'SERVICE':'ILLUSTRATIVE SERVICE CONCEPT' }}</span><h3><a href="{{ $service->url }}">{{ $service->title }}</a></h3>
@if($service->excerpt)<p>{{ $service->excerpt }}</p>
@endif<div class="site-card-links"><a href="{{ $service->url }}">Explore service ↗</a><a href="{{ url('/contact').'?service='.urlencode($service->slug) }}">Enquire now</a></div></div></article>
