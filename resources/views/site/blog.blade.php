@extends('site.layout') @section('content')<header class="site-page-hero pack-container"><span class="pack-kicker">THE PACKTRADE JOURNAL</span><h1>{{ $content->title }}</h1><p>{{ $content->excerpt }}</p></header><section class="pack-container site-listing"><form method="GET" class="site-search"><label><span class="site-form-label">Search the journal</span><input type="search" name="q" value="{{ request('q') }}" maxlength="100" placeholder="Materials, print, packaging…"></label><label><span class="site-form-label">Category</span><select name="category"><option value="">All categories</option>
@foreach($categories  as $category)<option value="{{ $category->slug }}" @selected(request('category')===$category->slug)>{{ $category->name }}</option>
@endforeach</select></label>
@if(request('tag'))<input type="hidden" name="tag" value="{{ request('tag') }}">
@endif<button class="pack-button pack-button-dark">Search articles <x-frontend::arrow/></button><a href="{{ url('/blog') }}">Clear filters</a></form>
@if($tags->isNotEmpty())<nav class="site-tags" aria-label="Article tags">
@foreach($tags  as $tag)<a href="{{ url('/blog').'?tag='.urlencode($tag->slug) }}">{{ $tag->name }}</a>
@endforeach</nav>
@endif<div class="site-card-grid">
@forelse($posts  as $post)
@include('site.post-card')
@empty<div class="site-empty"><h2>No articles match this search.</h2><p>Try another topic or clear your filters.</p><a href="{{ url('/blog') }}">Explore all articles ↗</a></div>
@endforelse</div><div class="site-pagination">{{ $posts->links() }}</div></section>
@endsection
