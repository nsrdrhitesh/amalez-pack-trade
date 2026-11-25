<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $meta['title'] }}</title><meta name="description" content="{{ $meta['description'] }}"><meta name="robots" content="{{ $meta['robots'] }}"><meta name="theme-color" content="#233032"><link rel="canonical" href="{{ $meta['canonical'] }}"><link rel="icon" href="{{ $favicon?->url??asset('images/branding/mark.svg') }}">
<meta property="og:type" content="{{ $content instanceof \App\Models\Post?'article':'website' }}"><meta property="og:site_name" content="{{ config('cms.brand') }}"><meta property="og:title" content="{{ $seoData['og_title']??$meta['title'] }}"><meta property="og:description" content="{{ $seoData['og_description']??$meta['description'] }}"><meta property="og:url" content="{{ $meta['canonical'] }}"><meta property="og:image" content="{{ url($meta['image']) }}"><meta name="twitter:card" content="summary_large_image"><meta name="twitter:title" content="{{ $seoData['twitter_title']??$meta['title'] }}"><meta name="twitter:description" content="{{ $seoData['twitter_description']??$meta['description'] }}"><meta name="twitter:image" content="{{ url($meta['image']) }}">
@foreach($schema  as $entity)<script type="application/ld+json">{!! json_encode($entity,JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR) !!}</script>
@endforeach
@vite(['resources/css/packaging.css','resources/js/app.js'])</head><body class="pack-site" x-data="siteExperience(
@js($settings['analytics_id']??null),
@js($settings['analytics_position']??'header'))"><a class="pack-skip" href="#main">Skip to content</a>
<div class="pack-announcement"><div class="pack-container"><span>PACKAGING / PRINT / POSSIBILITIES</span><span>{{ config('cms.brand') }} <i></i> Thoughtfully connected</span></div></div>
<header class="pack-header" x-data="{open:false}" @keydown.escape.window="open=false"><div class="pack-container pack-nav"><a href="{{ route('home') }}" class="pack-brand" aria-label="{{ config('cms.brand') }} home">
@if($logo)<img src="{{ $logo->url }}" alt="{{ config('cms.brand') }}" width="180" height="48">
@else<img src="{{ asset('images/branding/mark.svg') }}" width="36" height="40" alt=""><span>{{ config('cms.brand') }}<small class="brand-sub">{{ $settings['branding']['tagline']??'PACKAGING & PRINTING SOLUTIONS' }}</small></span>
@endif</a><nav class="pack-desktop-nav" aria-label="Main navigation">
@include('site.navigation',['items'=>$navigation['header']??collect(),'parent'=>null])</nav><div class="site-nav-actions"><a class="pack-button pack-button-orange" href="{{ url('/contact') }}">Get a quote <x-frontend::arrow/></a><button class="pack-menu-toggle" @click="open=!open" :aria-expanded="open" aria-controls="site-mobile-nav"><span x-text="open?'Close':'Menu'">Menu</span><svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="1.5"/></svg></button></div></div><nav id="site-mobile-nav" class="pack-mobile-nav" x-show="open" x-cloak aria-label="Mobile navigation">
@include('site.navigation',['items'=>$navigation['header']??collect(),'parent'=>null])</nav></header>
<main id="main">
@if($breadcrumbs)<nav class="site-breadcrumb pack-container" aria-label="Breadcrumb"><ol>
@foreach($breadcrumbs  as $crumb)<li>
@if(!$loop->last)<a href="{{ $crumb['url'] }}">{{ $crumb['title'] }}</a><span aria-hidden="true">/</span>
@else<span aria-current="page">{{ $crumb['title'] }}</span>
@endif</li>
@endforeach</ol></nav>
@endif @yield('content')</main>
<footer class="site-footer"><div class="pack-container site-footer-grid"><div><a class="site-footer-brand" href="{{ route('home') }}">
@if($footerLogo)<img src="{{ $footerLogo->url }}" alt="{{ config('cms.brand') }}" width="200" height="48">
@else PackTrade<span>{{ config('cms.brand') }}</span>
@endif</a><p>{{ $settings['branding']['footer_description']??'' }}</p><div class="site-social">
@foreach($settings['social_links']??[] as $link)<a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer">{{ $link['label'] }} ↗</a>
@endforeach</div></div><div><h2>Explore</h2>
@include('site.navigation',['items'=>$navigation['footer']??collect(),'parent'=>null])</div><div><h2>Services</h2>
@foreach($footerServices  as $service)<a href="{{ $service->url }}">{{ $service->title }}</a>
@endforeach</div><div><h2>Start a conversation</h2>
@if(!empty($business['email']))<a href="mailto:{{ $business['email'] }}">{{ $business['email'] }}</a>
@endif @if(!empty($business['telephone']))<a href="tel:{{ preg_replace('/[^+0-9]/','',$business['telephone']) }}">{{ $business['telephone'] }}</a>
@endif @if(!empty($business['address']))<p>{{ $business['address'] }}</p>
@endif<a href="{{ url('/contact') }}">Discuss your requirement ↗</a><button type="button" @click="showConsent=true">Cookie preferences</button></div></div><div class="pack-container site-footer-bottom"><span>© {{ date('Y') }} {{ config('cms.brand') }}</span><span>Designed, Developed & Maintained by the <a href="https://hiteshahire.in" target="_blank" rel="noopener noreferrer">Hitesh Ahire</a></span></div></footer>
<section class="site-cookie" x-show="showConsent" x-cloak aria-label="Cookie preferences"><div><strong>A considered approach to cookies.</strong><p>Essential cookies support secure forms. Optional analytics loads only with your permission. <a href="{{ url('/cookie-policy') }}">Cookie policy</a></p></div><div class="site-cookie-actions"><button class="pack-button pack-button-dark" @click="setConsent('essential')">Essential only</button><button class="pack-button pack-button-orange" @click="setConsent('all')">Allow optional analytics</button></div></section></body></html>
