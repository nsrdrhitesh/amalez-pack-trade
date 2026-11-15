<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Service;
use App\Models\User;
use App\Services\BusinessContentRelease;
use App\Services\Seo;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BusinessContentReleaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cms.enabled' => true]);
        Storage::fake('local');
        $this->seed(CmsSeeder::class);
    }

    private function transform(): array
    {
        $release = app(BusinessContentRelease::class);

        return $release->apply($release->fingerprint());
    }

    public function test_release_publishes_only_five_main_services_and_preserves_old_records(): void
    {
        $owner = User::factory()->create(['is_admin' => true, 'cms_role' => 'owner']);
        $oldServices = Service::pluck('id');
        $oldPosts = Post::pluck('id');
        $result = $this->transform();
        $this->assertSame(25, $result['services_published']);
        $this->assertSame(['Rotogravure Pouches', 'Digital Printed Pouches', 'Flexo Labels', 'Paper Boxes', 'Corrugated Boxes'], Service::published()->whereNull('parent_id')->orderBy('sort_order')->pluck('title')->all());
        $this->assertSame(7, Service::whereIn('id', $oldServices)->where('status', 'draft')->count());
        $this->assertSame(3, Post::whereIn('id', $oldPosts)->where('status', 'draft')->count());
        $this->assertDatabaseCount('users', 1);
        $this->assertSame($owner->password, $owner->fresh()->password);
        $this->assertSame('owner', $owner->fresh()->cms_role);
        $this->assertSame('draft', Page::whereSlug('infrastructure')->value('status'));
        $this->get('/infrastructure')->assertNotFound();
        $this->get('/')->assertOk()->assertSee('Amalez PackTrade')->assertSee('Print made distinctive.')->assertDontSee('href="/infrastructure"', false);
    }

    public function test_all_nested_service_paths_have_images_metadata_faq_and_related_links(): void
    {
        $this->transform();
        foreach (Service::published()->with('featuredMedia', 'seo', 'gallery')->get() as $service) {
            $response = $this->get($service->url)->assertOk()->assertSee($service->title)->assertSee('application/ld+json', false)->assertSee('rel="canonical"', false)->assertSee('FAQPage');
            $this->assertNotEmpty($service->featuredMedia->alt);
            $this->assertNotEmpty($service->seo->data['focus_keyword']);
            $this->assertNotEmpty($service->seo->data['geo_facts']);
            $this->assertSame(5, $service->gallery->count());
            $this->get($service->featuredMedia->url)->assertOk()->assertHeader('Content-Type', 'image/webp');
            $type = app(Seo::class)->data($service)['schema'][0]['@type'];
            $this->assertSame($service->parent_id ? 'WebPage' : 'Service', $type);
        }
        $nested = Service::whereSlug('roto-zipper')->firstOrFail();
        $this->assertStringContainsString('/rotogravure-pouches/roto-stand-up/roto-zipper', $nested->url);
        $this->get('/services/roto-zipper')->assertNotFound();
        $this->get('/sitemap.xml')->assertOk()->assertSee('/services/corrugated-boxes')->assertDontSee('/infrastructure');
    }

    public function test_ten_articles_cover_every_service_with_unique_media_and_taxonomy(): void
    {
        $this->transform();
        $posts = Post::published()->with('categories', 'tags', 'seo')->get();
        $this->assertCount(10, $posts);
        $this->assertCount(10, $posts->pluck('featured_media_id')->unique());
        foreach (Service::published()->whereNull('parent_id')->get() as $service) {
            $category = Category::whereSlug($service->slug)->firstOrFail();
            $this->assertSame(2, $category->posts()->published()->count());
        }
        foreach ($posts as $post) {
            $this->get($post->url)->assertOk()->assertSee('BlogPosting')->assertSee('FAQPage')->assertSee('Amalez PackTrade Editorial')->assertSee('/contact');
            $this->assertGreaterThan(300, str_word_count(strip_tags($post->body)));
            $this->assertGreaterThan(0, $post->tags->count());
            $this->assertNotEmpty($post->seo->data['canonical']);
            $this->assertTrue($post->comments_enabled);
        }
        $this->get('/blog?category=flexo-labels')->assertOk()->assertSee('A label is a system')->assertDontSee('Planning repeat pouch orders');
    }

    public function test_import_requires_review_fingerprint_and_never_replaces_later_editor_changes(): void
    {
        $release = app(BusinessContentRelease::class);
        $fingerprint = $release->fingerprint();
        Page::whereSlug('about')->update(['excerpt' => 'A new client edit.']);
        try {
            $release->apply($fingerprint);
            $this->fail('A stale review must not apply.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('changed since review', $e->getMessage());
        }
        $this->transform();
        $page = Page::whereSlug('about')->firstOrFail();
        $page->update(['body' => '<p>A later approved edit.</p>']);
        $this->assertTrue($release->apply($release->fingerprint())['already_applied']);
        $this->assertSame('<p>A later approved edit.</p>', $page->fresh()->body);
        $this->assertSame(10, Post::published()->count());
    }

    public function test_confirmed_brand_schema_has_no_invented_legal_or_contact_facts(): void
    {
        $this->transform();
        $home = Page::whereSlug('home')->firstOrFail();
        $schema = app(Seo::class)->data($home)['schema'];
        $types = array_column($schema, '@type');
        $this->assertSame(1, count(array_filter($types, fn ($type) => $type === 'Organization')));
        $this->assertContains('WebSite', $types);
        $org = collect($schema)->firstWhere('@type', 'Organization');
        $this->assertSame('Amalez PackTrade', $org['name']);
        foreach (['address', 'telephone', 'legalName', 'aggregateRating'] as $field) {
            $this->assertArrayNotHasKey($field, $org);
        }
        $this->get('/')->assertSee('noindex,nofollow', false);
        $this->get('/robots.txt')->assertSee('Disallow: /');
        $home->seo->update(['data' => ['schema_mode' => 'manual', 'schema_json' => '{"@context":"https://schema.org","@type":"WebPage","name":"Approved override"}']]);
        $this->assertCount(1, app(Seo::class)->data($home->fresh())['schema']);
    }

    public function test_indexing_option_excludes_provisional_legal_pages_and_archived_content(): void
    {
        $release = app(BusinessContentRelease::class);
        $release->apply($release->fingerprint(), true);
        $this->get('/')->assertOk()->assertSee('index,follow', false)->assertDontSee('noindex,nofollow', false);
        $this->get('/privacy-policy')->assertOk()->assertSee('noindex,nofollow', false);
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin')->assertSee('Sitemap:');
        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
        $this->assertStringNotContainsString('/privacy-policy', $xml);
        $this->assertStringNotContainsString('/infrastructure', $xml);
        $this->assertStringNotContainsString('/services/flexible-packaging', $xml);
        $this->assertSame(41, substr_count($xml, '<url>'));
    }
}
