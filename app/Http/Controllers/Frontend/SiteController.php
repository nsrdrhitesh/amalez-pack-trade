<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\FormField;
use App\Models\Media;
use App\Models\Page;
use App\Models\Post;
use App\Models\Service;
use App\Models\Tag;
use App\Services\Cms;
use App\Services\Seo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SiteController extends Controller
{
    public function render(string $view, $content = null, array $data = [], array $breadcrumbs = [])
    {
        $settings = Cms::settings();
        $branding = $settings['branding'] ?? [];
        $seoOptions = $content?->seo?->data ?? [];
        if (! ($seoOptions['breadcrumbs'] ?? true)) {
            $breadcrumbs = [];
        } elseif ($breadcrumbs && ! empty($seoOptions['breadcrumb_title'])) {
            $breadcrumbs[array_key_last($breadcrumbs)]['title'] = $seoOptions['breadcrumb_title'];
        }

        return view('site.'.$view, $data + ['content' => $content, 'breadcrumbs' => $breadcrumbs, 'settings' => $settings,
            'business' => $settings['business'] ?? [], 'navigation' => Cms::navigation(), 'logo' => ! empty($branding['logo_media_id']) ? Media::find($branding['logo_media_id']) : null,
            'favicon' => ! empty($branding['favicon_media_id']) ? Media::find($branding['favicon_media_id']) : null,
            'footerLogo' => ! empty($branding['footer_logo_media_id']) ? Media::find($branding['footer_logo_media_id']) : null,
            'footerServices' => Service::published()->whereNull('parent_id')->orderBy('sort_order')->limit(6)->get()] + app(Seo::class)->data($content, $breadcrumbs));
    }

    public function home()
    {
        return $this->page('home');
    }

    public function page(string $slug)
    {
        $page = Page::published()->with(['seo.image', 'featuredMedia', 'sections.media'])->where('slug', $slug)->firstOrFail();
        $data = ['featuredServices' => Service::published()->whereNull('parent_id')->with('featuredMedia')->orderByDesc('featured')->orderBy('sort_order')->limit(6)->get(), 'recentPosts' => Post::published()->with(['featuredMedia', 'categories'])->orderByDesc('published_at')->limit(3)->get()];
        if ($page->template === 'contact') {
            $data['fields'] = FormField::where('enabled', true)->orderBy('sort_order')->get();
        }$data['allServices'] = Service::published()->orderBy('title')->get()->filter->isPublic();

        return $this->render('page', $page, $data, $slug === 'home' ? [] : [['title' => 'Home', 'url' => url('/')], ['title' => $page->title, 'url' => $page->url]]);
    }

    public function services()
    {
        $page = Page::published()->with('seo.image', 'sections.media')->where('slug', 'services')->firstOrFail();

        return $this->render('services', $page, ['services' => Service::published()->whereNull('parent_id')->with('featuredMedia')->orderBy('sort_order')->paginate(12)], [['title' => 'Home', 'url' => url('/')], ['title' => 'Services', 'url' => url('/services')]]);
    }

    public function service(string $path)
    {
        $segments = explode('/', $path);
        abort_if(count($segments) > 100 || strlen($path) > 2048, 404);
        $parent = null;
        foreach ($segments as $slug) {
            $service = Service::published()->where('slug', $slug)->where('parent_id', $parent)->firstOrFail();
            $parent = $service->id;
        }$service->load(['seo.image', 'featuredMedia', 'sections.media', 'gallery.media', 'related.featuredMedia']);
        $breadcrumbs = [['title' => 'Home', 'url' => url('/')], ['title' => 'Services', 'url' => url('/services')]];
        foreach ($service->ancestors() as $s) {
            $breadcrumbs[] = ['title' => $s->title, 'url' => $s->url];
        }

        return $this->render('service', $service, ['children' => $service->children()->published()->with('featuredMedia')->get(), 'related' => $service->related->filter->isPublic()], $breadcrumbs);
    }

    public function blog(Request $r)
    {
        $r->validate(['q' => 'nullable|string|max:100', 'category' => 'nullable|string|max:160', 'tag' => 'nullable|string|max:160']);
        $page = Page::published()->with('seo.image')->where('slug', 'blog')->firstOrFail();
        $posts = Post::published()->with(['featuredMedia', 'categories'])->when($r->filled('q'), fn ($q) => $q->where(fn ($q) => $q->where('title', 'like', '%'.$r->string('q').'%')->orWhere('excerpt', 'like', '%'.$r->string('q').'%')))->when($r->filled('category'), fn ($q) => $q->whereHas('categories', fn ($q) => $q->where('slug', $r->string('category'))))->when($r->filled('tag'), fn ($q) => $q->whereHas('tags', fn ($q) => $q->where('slug', $r->string('tag'))))->orderByDesc('published_at')->paginate(9)->withQueryString();

        return $this->render('blog', $page, ['posts' => $posts, 'categories' => Category::whereHas('posts', fn ($q) => $q->published())->orderBy('name')->get(), 'tags' => Tag::whereHas('posts', fn ($q) => $q->published())->orderBy('name')->get()], [['title' => 'Home', 'url' => url('/')], ['title' => 'Journal', 'url' => url('/blog')]]);
    }

    public function post(string $slug)
    {
        $post = Post::published()->with(['seo.image', 'featuredMedia', 'categories', 'tags', 'gallery.media', 'sections.media'])->where('slug', $slug)->firstOrFail();
        $related = Post::published()->with(['featuredMedia', 'categories'])->whereKeyNot($post->id)->when($post->categories->isNotEmpty(), fn ($q) => $q->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $post->categories->modelKeys())))->latest('published_at')->limit(3)->get();

        return $this->render('post', $post, ['related' => $related, 'comments' => $post->comments()->where('status', 'approved')->latest()->paginate(10)], [['title' => 'Home', 'url' => url('/')], ['title' => 'Journal', 'url' => url('/blog')], ['title' => $post->title, 'url' => $post->url]]);
    }

    public function media(Media $media, Request $r)
    {
        abort_unless(Storage::disk('local')->exists($media->path), 404);
        $response = response()->file(Storage::disk('local')->path($media->path), ['Content-Type' => $media->mime, 'X-Content-Type-Options' => 'nosniff', 'Content-Security-Policy' => "default-src 'none'", 'Cache-Control' => 'public, max-age=3600, must-revalidate']);
        $response->setEtag(hash('sha256', $media->path.$media->updated_at));
        $response->isNotModified($r);

        return $response;
    }

    public function sitemap()
    {
        $urls = [];
        foreach (Page::published()->with('seo')->get() as $p) {
            if ($p->seo?->data['index'] ?? false) {
                $urls[] = $p->url;
            }
        }foreach (Service::published()->with('parent', 'seo')->get() as $s) {
            if ($s->isPublic() && ($s->seo?->data['index'] ?? false)) {
                $urls[] = $s->url;
            }
        }foreach (Post::published()->with('seo')->get() as $p) {
            if ($p->seo?->data['index'] ?? false) {
                $urls[] = $p->url;
            }
        }

        return response()->view('site.sitemap', ['urls' => array_unique($urls)])->header('Content-Type', 'application/xml');
    }

    public function robots()
    {
        return response(Cms::setting('indexable', false) ? "User-agent: *\nDisallow: /admin\nDisallow: /storage\nSitemap: ".url('/sitemap.xml')."\n" : "User-agent: *\nDisallow: /\n", 200, ['Content-Type' => 'text/plain']);
    }
}
