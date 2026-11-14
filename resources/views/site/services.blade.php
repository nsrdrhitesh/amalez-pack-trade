@extends('site.layout') @section('content')<header class="site-page-hero pack-container"><span class="pack-kicker">SERVICES / PACKAGING & PRINT</span><h1>{{ $content->title }}</h1><p>{{ $content->excerpt }}</p></header>
@if($content->body)<div class="pack-container site-content-section site-prose">{!! $content->body !!}</div>
@endif<section class="pack-container site-listing"><div class="site-card-grid">
@forelse($services  as $service)
@include('site.service-card')
@empty<div class="site-empty"><h2>A new collection is taking shape.</h2><p>Tell us about your packaging requirement while service details are being prepared.</p><a href="{{ url('/contact') }}">Start a conversation ↗</a></div>
@endforelse</div><div class="site-pagination">{{ $services->links() }}</div></section>
@include('site.sections')
@endsection
