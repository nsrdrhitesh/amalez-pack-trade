@extends('site.layout') @section('content')<header class="site-page-hero pack-container"><span class="pack-kicker">{{ $content->confirmed?'PACKAGING & PRINTING SOLUTIONS':'ILLUSTRATIVE SERVICE CONCEPT' }}</span><h1>{{ $content->title }}</h1><p>{{ $content->excerpt }}</p><a class="pack-button pack-button-orange" href="{{ url('/contact').'?service='.urlencode($content->slug) }}">Enquire about this service <x-frontend::arrow/></a></header><section class="pack-container site-detail-grid">
@if($content->featuredMedia)<figure><img src="{{ $content->featuredMedia->url }}" alt="{{ $content->featuredMedia->alt }}" width="{{ $content->featuredMedia->width }}" height="{{ $content->featuredMedia->height }}">
@if($content->featuredMedia->caption)<figcaption>{{ $content->featuredMedia->caption }}</figcaption>
@endif</figure>
@endif<div class="site-prose">
@unless($content->confirmed)<p class="site-notice">Presented for discussion. Availability and specifications require confirmation for your product.</p>
@endunless{!! $content->body !!}</div></section>
@include('site.sections')
@unless($content->sections->contains(fn($s)=>$s->enabled&&$s->type==='gallery'))
@include('site.gallery')
@endunless
@if($children->isNotEmpty())<section class="pack-section pack-container"><div class="site-section-heading"><span class="pack-kicker">EXPLORE THE POSSIBILITIES</span><h2>More within {{ $content->title }}.</h2></div><div class="site-card-grid">
@foreach($children  as $service)
@include('site.service-card')
@endforeach</div></section>
@endif
@if($related->isNotEmpty())<section class="pack-section pack-container"><div class="site-section-heading"><h2>Related services</h2></div><div class="site-card-grid">
@foreach($related  as $service)
@include('site.service-card')
@endforeach</div></section>
@endif @endsection
