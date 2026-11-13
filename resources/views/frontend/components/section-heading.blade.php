@props(['number', 'eyebrow', 'title', 'description' => null])
<div {{ $attributes->class(['pack-section-heading']) }}><div><span class="pack-kicker"><span>{{ $number }} /</span> {{ $eyebrow }}</span><h2>{{ $title }}</h2></div>@if($description)<p>{{ $description }}</p>@endif</div>
