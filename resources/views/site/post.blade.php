@extends('site.layout') @section('content')<article><header class="site-page-hero pack-container"><div class="site-tags">
@foreach($content->categories  as $category)<a href="{{ url('/blog').'?category='.urlencode($category->slug) }}">{{ $category->name }}</a>
@endforeach</div><h1>{{ $content->title }}</h1><p>{{ $content->excerpt }}</p><div class="site-article-meta"><span>{{ $content->author_name?:'PackTrade Editorial' }}</span><time datetime="{{ ($content->published_at??$content->created_at)->toAtomString() }}">{{ ($content->published_at??$content->created_at)->format('j F Y') }}</time><span>{{ $content->reading_minutes }} min read</span></div></header>
@if($content->featuredMedia)<figure class="pack-container site-featured"><img src="{{ $content->featuredMedia->url }}" alt="{{ $content->featuredMedia->alt }}" width="{{ $content->featuredMedia->width }}" height="{{ $content->featuredMedia->height }}">
@if($content->featuredMedia->caption)<figcaption>{{ $content->featuredMedia->caption }}</figcaption>
@endif</figure>
@endif<div class="pack-container site-content-section"><div class="site-prose">{!! $content->body !!}</div></div>
@include('site.sections')
@unless($content->sections->contains(fn($s)=>$s->enabled&&$s->type==='gallery'))
@include('site.gallery')
@endunless<div class="pack-container site-article-footer"><nav class="site-tags" aria-label="Article tags">
@foreach($content->tags  as $tag)<a href="{{ url('/blog').'?tag='.urlencode($tag->slug) }}">{{ $tag->name }}</a>
@endforeach</nav><div class="site-share"><strong>Share this article</strong><a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($content->url) }}" target="_blank" rel="noopener noreferrer">LinkedIn ↗</a><a href="https://wa.me/?text={{ urlencode($content->title.' '.$content->url) }}" target="_blank" rel="noopener noreferrer">WhatsApp ↗</a><button type="button" data-copy-url="{{ $content->url }}">Copy link</button><span role="status" class="site-copy-status"></span></div></div></article>
@if($content->comments_enabled)<section id="comments" class="pack-container site-comments"><div class="site-section-heading"><span class="pack-kicker">A THOUGHTFUL CONVERSATION</span><h2>Comments</h2></div>
@if(session('comment_status'))<p class="site-success" role="status">{{ session('comment_status') }}</p>
@endif @foreach($comments  as $comment)<article class="site-comment"><h3>{{ $comment->name }}</h3><time datetime="{{ $comment->created_at->toAtomString() }}">{{ $comment->created_at->format('j F Y') }}</time><p>{{ $comment->body }}</p></article>
@endforeach{{ $comments->links() }}<form method="POST" action="{{ route('comments.store',$content) }}" class="site-form">
@csrf @include('site.spam-fields')<h3>Add your perspective</h3><p>Your email stays private. Comments are reviewed before publication.</p>
@include('site.form-errors')<div class="site-form-grid"><label>Name <span>*</span><input name="name" value="{{ old('name') }}" required maxlength="100" autocomplete="name"></label><label>Email <span>*</span><input type="email" name="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email"></label></div><label>Comment <span>*</span><textarea name="body" required minlength="5" maxlength="3000" rows="5">{{ old('body') }}</textarea></label><label class="site-consent"><input type="checkbox" name="consent" value="1" required> I agree to the processing of these details for comment moderation as described in the <a href="{{ url('/privacy-policy') }}">privacy policy</a>.</label><button class="pack-button pack-button-orange">Submit for moderation <x-frontend::arrow/></button></form></section>
@endif
@if($related->isNotEmpty())<section class="pack-section pack-container"><div class="site-section-heading"><h2>Continue exploring.</h2></div><div class="site-card-grid">
@foreach($related  as $post)
@include('site.post-card')
@endforeach</div></section>
@endif @endsection
