@extends('site.layout') @section('content')
@if($content->template==='home')
<section class="pack-hero"><div class="pack-container pack-hero-grid"><div class="pack-hero-copy"><span class="pack-kicker"><i class="orange-line"></i> {{ $settings['branding']['hero_kicker']??'' }}</span><h1>{{ $content->title }}</h1>
@if($content->excerpt)<p>{{ $content->excerpt }}</p>
@endif<div class="pack-hero-actions"><a class="pack-button pack-button-orange" href="{{ url('/services') }}">Explore our services <x-frontend::arrow/></a><a class="pack-text-link" href="{{ url('/contact') }}">Request a quote ↗</a></div><div class="pack-hero-footnote">{{ $settings['branding']['hero_footnote']??'' }}</div></div>
@if($content->featuredMedia)<figure class="pack-hero-visual"><img src="{{ $content->featuredMedia->url }}" alt="{{ $content->featuredMedia->alt }}" width="{{ $content->featuredMedia->width }}" height="{{ $content->featuredMedia->height }}" fetchpriority="high"><figcaption><span>PACKAGING / PURPOSE / PRESENTATION</span><span>Illustrative packaging concepts ↗</span></figcaption></figure>
@endif</div></section>
@else<header class="site-page-hero pack-container"><span class="pack-kicker"><i class="orange-line"></i> AMALEZ PACK TRADE</span><h1>{{ $content->title }}</h1>
@if($content->excerpt)<p>{{ $content->excerpt }}</p>
@endif</header>
@endif
@if(filled(strip_tags($content->body??'')))<section class="pack-container site-content-section"><div class="site-prose {{ $content->template==='home'?'site-prose-wide':'' }}">{!! $content->body !!}</div></section>
@endif
@if($content->template!=='home'&&$content->featuredMedia)<figure class="pack-container site-featured"><img src="{{ $content->featuredMedia->url }}" alt="{{ $content->featuredMedia->alt }}" width="{{ $content->featuredMedia->width }}" height="{{ $content->featuredMedia->height }}">
@if($content->featuredMedia->caption)<figcaption>{{ $content->featuredMedia->caption }}</figcaption>
@endif</figure>
@endif
@include('site.sections')
@if($content->template==='contact')
@include('site.contact-form')
@endif
@endsection
