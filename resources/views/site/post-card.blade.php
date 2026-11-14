<article class="site-card site-post-card">
@if($post->featuredMedia)<a class="site-card-image" href="{{ $post->url }}"><img src="{{ $post->featuredMedia->url }}" alt="{{ $post->featuredMedia->alt }}" width="{{ $post->featuredMedia->width }}" height="{{ $post->featuredMedia->height }}" loading="lazy"><span aria-hidden="true">↗</span></a>
@endif<div class="site-card-body"><span class="pack-kicker">{{ $post->categories->first()?->name??'PACKTRADE JOURNAL' }} · {{ $post->reading_minutes }} MIN READ</span><h3><a href="{{ $post->url }}">{{ $post->title }}</a></h3>
@if($post->excerpt)<p>{{ $post->excerpt }}</p>
@endif<a class="pack-text-link" href="{{ $post->url }}">Read the article <x-frontend::arrow/></a></div></article>
