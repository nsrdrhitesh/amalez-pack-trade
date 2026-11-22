<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Media;
use App\Models\NavigationItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\Service;
use App\Models\Tag;
use App\Models\WebsiteSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BusinessContentRelease
{
    public function catalogue(): array
    {
        return require database_path('content/catalogue.php');
    }

    public function articles(): array
    {
        return require database_path('content/articles.php');
    }

    public function fingerprint(): string
    {
        // No account, visitor or credential values are exported by the review command.
        $state = [];
        foreach (['pages', 'services', 'posts', 'content_sections', 'seo_metadata', 'navigation_items', 'website_settings'] as $table) {
            $state[$table] = DB::table($table)->orderBy('id')->get()->toArray();
        }

        return hash('sha256', json_encode($state, JSON_THROW_ON_ERROR));
    }

    public function plan(): array
    {
        $catalogue = $this->catalogue();

        return ['version' => $catalogue['version'], 'already_applied' => WebsiteSetting::where('key', 'business_content_release')->value('value') === $catalogue['version'],
            'fingerprint' => $this->fingerprint(), 'new_services' => count($catalogue['services']), 'main_services' => array_values(array_map(fn ($s) => $s[1], array_filter($catalogue['services'], fn ($s) => $s[2] === null))),
            'new_articles' => count($this->articles()), 'preserve_as_drafts' => ['services' => Service::published()->pluck('slug')->all(), 'posts' => Post::published()->pluck('slug')->all()],
            'update_pages' => ['home', 'about', 'services', 'blog', 'contact', 'faq'], 'draft_pages' => ['infrastructure'], 'schema_migrations' => 0, 'accounts_modified' => 0];
    }

    public function apply(string $expected, bool $enableIndexing = false): array
    {
        $catalogue = $this->catalogue();
        if (WebsiteSetting::where('key', 'business_content_release')->value('value') === $catalogue['version']) {
            return ['already_applied' => true];
        }
        if (! preg_match('/^[a-f0-9]{64}$/', $expected) || ! hash_equals($this->fingerprint(), $expected)) {
            throw new \RuntimeException('Content changed since review. Generate a new plan before applying.');
        }
        $manifest = json_decode(file_get_contents(database_path('content/assets.json')), true, 32, JSON_THROW_ON_ERROR);
        foreach ($manifest as $asset) {
            $path = public_path($asset['file']);
            if (! is_file($path) || ! hash_equals($asset['sha256'], hash_file('sha256', $path))) {
                throw new \RuntimeException('Missing or changed image asset: '.$asset['id']);
            }
        }
        $plannedSlugs = array_column($catalogue['services'], 0);
        if (Service::whereIn('slug', $plannedSlugs)->exists() || Post::whereIn('slug', array_column($this->articles(), 0))->exists()) {
            throw new \RuntimeException('A new catalogue slug already exists. Review the collision before import.');
        }

        return DB::transaction(function () use ($catalogue, $manifest, $enableIndexing, $expected) {
            // Lock the reviewed content while importing; visitor submissions stay independent.
            foreach (['pages', 'services', 'posts', 'content_sections', 'seo_metadata', 'navigation_items', 'website_settings'] as $table) {
                DB::table($table)->orderBy('id')->lockForUpdate()->get();
            }
            if (! hash_equals($this->fingerprint(), $expected)) {
                throw new \RuntimeException('Content changed since review. Generate a new plan before applying.');
            }
            $images = [];
            foreach ($manifest as $asset) {
                $path = 'cms-media/business-'.$asset['id'].'-'.substr($asset['sha256'], 0, 12).'.webp';
                if (! Storage::disk('local')->put($path, file_get_contents(public_path($asset['file'])))) {
                    throw new \RuntimeException('Private media copy failed.');
                }
                $images[$asset['id']] = Media::firstOrCreate(['path' => $path], ['uuid' => (string) Str::uuid(), 'original_name' => basename($asset['file']), 'mime' => 'image/webp', 'size' => filesize(public_path($asset['file'])), 'width' => $asset['width'], 'height' => $asset['height'], 'alt' => $asset['alt'], 'caption' => 'Original AI-generated packaging design concept. Final materials, print and specifications require confirmation.']);
            }
            // Keep historical content and all its relationships, media and visitor records.
            Service::whereIn('status', ['published', 'scheduled'])->update(['status' => 'draft']);
            Post::whereIn('status', ['published', 'scheduled'])->update(['status' => 'draft']);
            $services = [];
            foreach ($catalogue['services'] as $order => $row) {
                [$slug, $title, $parent, $image, $excerpt, $intro, $print, $spec, $applications, $question, $answer] = $row;
                $body = $this->paragraphs(['The format and the brief' => $intro, 'Consider the printed result' => $print, 'Details to discuss' => $spec, 'Application considerations' => $applications]);
                if ($parent) {
                    $body .= '<p>Explore <a href="'.e(parse_url($services[$parent]->url, PHP_URL_PATH)).'">'.e($services[$parent]->title).'</a> for the wider service brief.</p>';
                }
                $services[$slug] = $s = Service::create(['slug' => $slug, 'title' => $title, 'parent_id' => $parent ? $services[$parent]->id : null, 'excerpt' => $excerpt, 'body' => $body, 'status' => 'published', 'published_at' => now(), 'featured_media_id' => $images[$image]->id, 'sort_order' => $order, 'featured' => $parent === null, 'confirmed' => $parent === null]);
                $s->sections()->create(['type' => 'faq', 'title' => $question, 'body' => '<p>'.e($answer).'</p>', 'enabled' => true, 'sort_order' => 0]);
                $s->sections()->create(['type' => 'cta', 'title' => 'Make the next print decision a clear one.', 'body' => '<p>Share your product, dimensions, quantities and artwork status. We will review the requirement before final specifications and commercial terms are agreed.</p>', 'enabled' => true, 'sort_order' => 10]);
                $this->seo($s, $title, $excerpt, $title, $images[$image]);
            }
            foreach ($services as $service) {
                $peers = collect($services)->filter(fn ($s) => $s->parent_id === $service->parent_id && $s->id !== $service->id)->take(3);
                $service->related()->sync($peers->pluck('id')->all());
                $gallery = $service->children()->get();
                if ($gallery->isEmpty() && $service->parent_id) {
                    $gallery = collect([$service->parent, ...$peers->take(1)->all()]);
                }
                $ownImages = collect($images)->filter(fn ($image, $id) => str_starts_with($id, $service->slug.'--'));
                // Asset IDs equal service slugs; the manifest also remains reusable in media admin.
                if ($ownImages->isNotEmpty()) {
                    foreach ($ownImages->values() as $index => $image) {
                        $service->gallery()->create(['media_id' => $image->id, 'caption' => $image->alt, 'sort_order' => $index]);
                    }
                } else {
                    foreach ($gallery->take(4) as $index => $item) {
                        $service->gallery()->create(['media_id' => $item->featured_media_id, 'caption' => $item->title.' — illustrative format detail', 'sort_order' => $index]);
                    }
                }
            }
            foreach ($this->articles() as $order => $article) {
                [$slug, $title, $serviceSlug, $image, $excerpt, $sections, $question, $answer, $tags] = $article;
                $service = $services[$serviceSlug];
                $children = $service->children()->get();
                $inline = $children->first()?->featuredMedia ?? $images[$image];
                $body = '<p>'.e($excerpt).'</p>'.$this->paragraphs($sections);
                $body .= '<figure><img src="'.e($inline->url).'" alt="'.e($inline->alt).'" width="'.$inline->width.'" height="'.$inline->height.'" loading="lazy" decoding="async"><figcaption>Original illustrative print and packaging concept.</figcaption></figure>';
                $body .= '<h2>Explore the relevant formats</h2><p><a href="'.e(parse_url($service->url, PHP_URL_PATH)).'">'.e($service->title).'</a></p><ul>';
                foreach ($children as $child) {
                    $body .= '<li><a href="'.e(parse_url($child->url, PHP_URL_PATH)).'">'.e($child->title).'</a></li>';
                }
                $body .= '</ul><p><a href="/contact">Discuss your packaging and printing requirement</a>. Product-specific suitability, availability, costs and timing require confirmation.</p>';
                $p = Post::create(['slug' => $slug, 'title' => $title, 'excerpt' => $excerpt, 'body' => $body, 'status' => 'published', 'published_at' => now()->subMinutes(10 - $order), 'featured_media_id' => $images[$image]->id, 'author_name' => 'Amalez PackTrade Editorial', 'comments_enabled' => true, 'sort_order' => $order]);
                $category = Category::firstOrCreate(['slug' => $serviceSlug], ['name' => $service->title]);
                $p->categories()->sync([$category->id]);
                $p->tags()->sync(collect($tags)->map(fn ($tag) => Tag::firstOrCreate(['slug' => Str::slug($tag)], ['name' => $tag])->id)->all());
                $p->sections()->create(['type' => 'faq', 'title' => $question, 'body' => '<p>'.e($answer).'</p>', 'enabled' => true, 'sort_order' => 0]);
                $ownImages = collect($images)->filter(fn ($media, $id) => str_starts_with($id, $image.'--'));
                if ($ownImages->isNotEmpty()) {
                    foreach ($ownImages->values() as $index => $media) {
                        $p->gallery()->create(['media_id' => $media->id, 'caption' => $media->alt, 'sort_order' => $index]);
                    }
                } else {
                    foreach ($children->take(2) as $index => $child) {
                        $p->gallery()->create(['media_id' => $child->featured_media_id, 'caption' => $child->title.' — original artwork concept', 'sort_order' => $index]);
                    }
                }
                $this->seo($p, $title, $excerpt, $service->title, $images[$image]);
            }
            $this->pages($images);
            foreach (Page::whereIn('slug', ['privacy-policy', 'terms-and-conditions', 'cookie-policy'])->with('seo')->get() as $page) {
                $rename = fn ($value) => is_string($value) ? str_replace(['Amalez Pack Trade', 'Amlez Pack Trade', 'Amlex Pack Trade'], 'Amalez PackTrade', $value) : $value;
                $page->update(['body' => $rename($page->body), 'excerpt' => $rename($page->excerpt)]);
                if ($page->seo) {
                    $data = array_map($rename, $page->seo->data ?? []);
                    $data['index'] = false; // Provisional legal copy requires client review.
                    $page->seo->update(['data' => $data, 'image_id' => $images['hero']->id]);
                }
            }
            Page::where('slug', 'infrastructure')->update(['status' => 'draft']);
            NavigationItem::where('url', '/infrastructure')->update(['enabled' => false]);
            $branding = Cms::setting('branding', []);
            WebsiteSetting::updateOrCreate(['key' => 'branding'], ['value' => array_merge($branding, ['hero_kicker' => 'PACKAGING WITH PRINT AT ITS HEART', 'hero_footnote' => 'ROTOGRAVURE / DIGITAL / LABELS / PAPER / CORRUGATED', 'tagline' => 'PACKAGING & PRINTING', 'footer_description' => 'Amalez PackTrade brings packaging and printing requirements into focus. Explore five services, understand your options and start a considered project brief.', 'contact_heading' => 'Tell us what you need to print and pack.', 'contact_intro' => 'Choose a service and share the product, format, dimensions, quantities and artwork status. Final technical and commercial details require confirmation.'])]);
            WebsiteSetting::updateOrCreate(['key' => 'business_content_release'], ['value' => $catalogue['version']]);
            if ($enableIndexing) {
                WebsiteSetting::updateOrCreate(['key' => 'indexable'], ['value' => true]);
            }
            Cms::forget();

            return ['services_published' => count($services), 'main_services' => 5, 'articles_published' => count($this->articles()), 'new_media' => count($images), 'schema_migrations' => 0];
        });
    }

    private function paragraphs(array $sections): string
    {
        $html = '';
        foreach ($sections as $heading => $text) {
            $html .= '<h2>'.e($heading).'</h2><p>'.e($text).'</p>';
        }

        return $html;
    }

    private function seo($model, string $title, string $description, string $focus, Media $image): void
    {
        $model->seo()->updateOrCreate([], ['image_id' => $image->id, 'data' => ['title' => $title.' | Amalez PackTrade', 'description' => $description, 'focus_keyword' => $focus, 'secondary_keywords' => 'printed packaging, packaging brief, print artwork', 'search_intent' => $model instanceof Post ? 'Informational: practical buyer guidance' : 'Commercial investigation: understand formats and prepare an inquiry', 'canonical' => 'https://amalezpacktrade.com'.parse_url($model->url, PHP_URL_PATH), 'og_title' => $title, 'og_description' => $description, 'twitter_title' => $title, 'twitter_description' => $description, 'geo_facts' => 'Amalez PackTrade is a packaging and printing business. Five confirmed main services. No location, facility, certification or performance claims are implied. Format options require project review.', 'breadcrumbs' => true, 'index' => true, 'schema_mode' => 'auto']]);
    }

    private function pages(array $images): void
    {
        $copy = require database_path('content/pages.php');
        foreach ($copy as $slug => $data) {
            $page = Page::where('slug', $slug)->firstOrFail();
            $page->update(['title' => $data['title'], 'excerpt' => $data['excerpt'], 'body' => $data['body'], 'featured_media_id' => isset($data['image']) ? $images[$data['image']]->id : $page->featured_media_id]);
            // Superseded sections stay in the database for recovery and remain editable.
            $page->sections()->update(['enabled' => false]);
            foreach ($data['sections'] ?? [] as $order => $section) {
                $mediaId = isset($section['image']) ? $images[$section['image']]->id : null;
                unset($section['image']);
                $page->sections()->create($section + ['sort_order' => $order, 'enabled' => true, 'media_id' => $mediaId]);
            }
            if (isset($data['image'])) {
                $gallery = collect($images)->filter(fn ($image, $id) => str_starts_with($id, $data['image'].'--'));
                if ($gallery->isNotEmpty()) {
                    $page->sections()->create(['type' => 'gallery', 'title' => 'Print, structure and a closer perspective.', 'enabled' => true, 'sort_order' => 3]);
                    foreach ($gallery->values() as $index => $image) {
                        $page->gallery()->create(['media_id' => $image->id, 'caption' => $image->alt, 'sort_order' => $index]);
                    }
                }
            }
            $this->seo($page, $data['seo_title'] ?? $data['title'], $data['excerpt'], $data['keyword'], $images[$data['image'] ?? 'hero']);
        }
    }
}
