<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Comment;
use App\Models\FormField;
use App\Models\Inquiry;
use App\Models\Media;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Post;
use App\Models\Service;
use App\Models\Tag;
use App\Models\User;
use App\Services\RichText;
use App\Services\Seo;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DynamicCmsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cms.enabled' => true]);
        Storage::fake('local');
        $this->seed(CmsSeeder::class);
    }

    private function owner(): User
    {
        return User::factory()->create(['is_admin' => true, 'cms_role' => 'owner']);
    }

    private function content(array $overrides = []): array
    {
        return array_replace(['title' => 'A useful content page', 'slug' => 'useful-content', 'excerpt' => 'A clear description.', 'body' => '<h2>Purposeful content</h2><p>A useful starting point.</p>', 'status' => 'published', 'sort_order' => 0, 'template' => 'standard'], $overrides);
    }

    private function spam(): array
    {
        return ['website' => '', '_form_token' => Crypt::encryptString((string) (now()->timestamp - 10)), 'consent' => '1'];
    }

    private function inquiry(array $fields = []): array
    {
        return $this->spam() + ['fields' => array_replace(['name' => 'Example Visitor', 'email' => 'visitor@example.test', 'message' => 'Please discuss this packaging brief.'], $fields)];
    }

    public function test_all_independent_public_pages_and_three_original_articles_render(): void
    {
        foreach (['/', '/about', '/services', '/blog', '/contact', '/faq', '/infrastructure', '/privacy-policy', '/terms-and-conditions', '/cookie-policy'] as $url) {
            $this->get($url)->assertOk()->assertSee('Amalez PackTrade')->assertDontSee('Forma');
        }
        $this->assertDatabaseCount('posts', 3);
        foreach (Post::all() as $post) {
            $this->get($post->url)->assertOk()->assertSee($post->title)->assertSee('BlogPosting');
        }
        $this->get('/')->assertDontSee('href="#products"', false)->assertSee('https://hiteshahire.in', false);
    }

    public function test_owner_can_render_every_cms_screen(): void
    {
        $this->actingAs($this->owner());
        foreach (['/admin/dashboard', '/admin/cms/pages', '/admin/cms/services', '/admin/cms/posts', '/admin/cms/pages/create', '/admin/cms/services/create', '/admin/cms/posts/create', '/admin/media', '/admin/taxonomy/categories', '/admin/taxonomy/tags', '/admin/comments', '/admin/inquiries', '/admin/menus', '/admin/website-settings', '/admin/cms-users', '/admin/seo', '/admin/schema', '/admin/contact-fields', '/admin/activity'] as $url) {
            $this->get($url)->assertOk();
        }
        foreach (['pages' => Page::first(), 'services' => Service::first(), 'posts' => Post::first()] as $type => $record) {
            $this->get('/admin/cms/'.$type.'/'.$record->id.'/edit')->assertOk()->assertSee($record->title);
        }
    }

    public function test_guest_and_non_admin_cannot_use_cms_actions(): void
    {
        $this->get('/admin/cms/pages')->assertRedirect('/admin/login');
        $this->post('/admin/cms/pages', $this->content())->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create())->get('/admin/cms/pages')->assertForbidden();
        $this->post('/admin/cms/pages', $this->content())->assertForbidden();
        $this->assertDatabaseMissing('pages', ['slug' => 'useful-content']);
    }

    public function test_editor_permissions_exclude_settings_users_private_data_and_scripts(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true, 'cms_role' => 'editor']));
        $this->get('/admin/cms/pages')->assertOk();
        foreach (['/admin/website-settings', '/admin/cms-users', '/admin/inquiries', '/admin/comments', '/admin/activity'] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->put('/admin/website-settings', ['analytics_id' => 'G-TEST1234'])->assertForbidden();
        $this->post('/admin/cms-users', ['name' => 'Unauthorized'])->assertForbidden();
    }

    public function test_page_crud_sanitizes_rich_text_and_removes_orphaned_sections(): void
    {
        $this->actingAs($this->owner());
        $this->post('/admin/cms/pages', $this->content(['body' => '<h2>Safe heading</h2><p onclick="evil()">Clear text.</p><script>alert(1)</script><img src="https://evil.example/a.png" onerror="evil()"><a href="javascript:evil()">Unsafe link</a>', 'sections' => [['type' => 'text', 'title' => 'Optional section', 'body' => '<p>Section content</p>', 'enabled' => 1, 'sort_order' => 0]]]))->assertRedirect()->assertSessionHasNoErrors();
        $page = Page::whereSlug('useful-content')->firstOrFail();
        $this->assertStringNotContainsString('<script', $page->body);
        $this->assertStringNotContainsString('onclick', $page->body);
        $this->assertStringNotContainsString('evil.example', $page->body);
        $this->get('/useful-content')->assertOk()->assertSee('Optional section');
        $this->put('/admin/cms/pages/'.$page->id, $this->content(['title' => 'Updated content']))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('pages', ['id' => $page->id, 'title' => 'Updated content']);
        $this->delete('/admin/cms/pages/'.$page->id)->assertRedirect();
        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
        $this->assertDatabaseMissing('seo_metadata', ['seoable_id' => $page->id, 'seoable_type' => Page::class]);
    }

    public function test_duplicate_and_reserved_page_slugs_and_core_deletion_are_protected(): void
    {
        $this->actingAs($this->owner());
        $this->post('/admin/cms/pages', $this->content(['slug' => 'about']))->assertSessionHasErrors('slug');
        $this->post('/admin/cms/pages', $this->content(['slug' => 'services']))->assertSessionHasErrors('slug');
        $home = Page::whereSlug('home')->firstOrFail();
        $this->delete('/admin/cms/pages/'.$home->id)->assertStatus(422);
        $this->put('/admin/cms/pages/'.$home->id, $this->content(['slug' => 'renamed-home', 'template' => 'home']))->assertSessionHasErrors('slug');
    }

    public function test_draft_and_future_content_are_hidden_until_publication_time(): void
    {
        $draft = Page::create($this->content(['slug' => 'draft-preview', 'status' => 'draft']));
        $scheduled = Post::create(collect($this->content(['slug' => 'future-article', 'status' => 'scheduled', 'published_at' => now()->addDay()]))->except('template')->all());
        $scheduled->seo()->create(['data'=>['index'=>true]]);
        $this->get('/draft-preview')->assertNotFound();
        $this->get('/blog/future-article')->assertNotFound();
        $this->get('/sitemap.xml')->assertDontSee('future-article');
        $this->travel(2)->days();
        $this->get('/blog/future-article')->assertOk();
        $this->get('/sitemap.xml')->assertSee('future-article');
    }

    public function test_nested_service_urls_breadcrumbs_cycles_and_delete_protection(): void
    {
        $root = Service::whereSlug('flexible-packaging')->firstOrFail();
        $child = Service::whereSlug('stand-up-pouches')->firstOrFail();
        $this->get('/services/flexible-packaging/stand-up-pouches/zipper-pouches')->assertOk()->assertSee('Stand-Up Zipper Pouches')->assertSee('BreadcrumbList');
        $this->get('/services/stand-up-pouches')->assertNotFound();
        $this->get('/services/labels-and-printing/stand-up-pouches')->assertNotFound();
        $this->actingAs($this->owner());
        $this->put('/admin/cms/services/'.$root->id, $this->content(['title' => $root->title, 'slug' => $root->slug, 'parent_id' => $child->id]))->assertSessionHasErrors('parent_id');
        $this->delete('/admin/cms/services/'.$root->id)->assertStatus(422);
        $root->update(['status' => 'draft']);
        $this->get('/services/flexible-packaging/stand-up-pouches')->assertNotFound();
        $this->get('/sitemap.xml')->assertDontSee('/services/flexible-packaging/stand-up-pouches');
    }

    public function test_services_and_posts_can_be_created_and_have_unique_slugs(): void
    {
        $this->actingAs($this->owner());
        $this->post('/admin/cms/services', $this->content(['slug' => 'new-service', 'parent_id' => Service::whereSlug('flexible-packaging')->value('id')]))->assertRedirect()->assertSessionHasNoErrors();
        $this->get('/services/flexible-packaging/new-service')->assertOk();
        $this->post('/admin/cms/services', $this->content(['slug' => 'new-service']))->assertSessionHasErrors('slug');
        $category = Category::first();
        $tag = Tag::first();
        $this->post('/admin/cms/posts', $this->content(['slug' => 'new-article', 'categories' => [$category->id], 'tags' => [$tag->id], 'comments_enabled' => 1]))->assertRedirect()->assertSessionHasNoErrors();
        $post = Post::whereSlug('new-article')->firstOrFail();
        $this->assertTrue($post->categories->contains($category));
        $this->assertTrue($post->tags->contains($tag));
        $this->get('/blog/new-article')->assertOk();
        $this->put('/admin/cms/posts/'.$post->id, $this->content(['slug' => 'new-article', 'title' => 'Revised article']))->assertRedirect()->assertSessionHasNoErrors();
        $this->delete('/admin/cms/posts/'.$post->id)->assertRedirect();
    }

    public function test_inline_taxonomy_creation_search_and_duplicate_slug_validation(): void
    {
        $this->actingAs($this->owner());
        $category = $this->postJson('/admin/taxonomy/categories', ['name' => 'Artwork planning'])->assertCreated()->json();
        $this->assertSame('artwork-planning', $category['slug']);
        $this->postJson('/admin/taxonomy/categories', ['name' => 'Artwork planning'])->assertUnprocessable();
        $tag = $this->postJson('/admin/taxonomy/tags', ['name' => 'Artwork checks'])->assertCreated()->json();
        $this->put('/admin/taxonomy/tags/'.$tag['id'], ['name' => 'Artwork review', 'slug' => 'artwork-review'])->assertRedirect();
        $this->get('/admin/taxonomy/categories?q=Artwork')->assertOk()->assertSee('Artwork planning');
        $this->delete('/admin/taxonomy/tags/'.$tag['id'])->assertRedirect();
    }

    public function test_blog_search_category_filters_and_related_articles(): void
    {
        $this->get('/blog?q=artwork')->assertOk()->assertSee('A cleaner artwork handover');
        $this->get('/blog?category=packaging-guides')->assertOk()->assertSee('Before the design');
        $this->get('/blog?tag=project-planning')->assertOk();
        $this->get('/blog?q=no-such-article')->assertOk()->assertSee('No articles match');
        $this->get(Post::first()->url)->assertSee('Continue exploring.');
    }

    public function test_comments_are_private_until_moderated_and_can_be_disabled(): void
    {
        $post = Post::first();
        $data = $this->spam() + ['name' => 'Reader', 'email' => 'private-reader@example.test', 'body' => 'A thoughtful contribution.', 'status' => 'approved'];
        $this->post('/blog/'.$post->id.'/comments', $data)->assertRedirect();
        $comment = Comment::sole();
        $this->assertSame('pending', $comment->status);
        $this->get($post->url)->assertDontSee('A thoughtful contribution.')->assertDontSee('private-reader@example.test');
        $this->actingAs($this->owner())->put('/admin/comments/'.$comment->id, ['status' => 'approved'])->assertRedirect();
        $this->get($post->url)->assertSee('A thoughtful contribution.')->assertDontSee('private-reader@example.test');
        $post->update(['comments_enabled' => false]);
        $this->post('/blog/'.$post->id.'/comments', $data)->assertNotFound();
    }

    public function test_contact_submissions_are_stored_private_and_manageable(): void
    {
        $this->post('/contact', $this->inquiry())->assertRedirect('/contact')->assertSessionHas('inquiry_reference');
        $inquiry = Inquiry::sole();
        $this->assertNotNull($inquiry->consented_at);
        $this->assertSame('visitor@example.test', $inquiry->field('email'));
        $this->get('/')->assertDontSee('visitor@example.test');
        $this->get('/admin/inquiries/'.$inquiry->id)->assertRedirect('/admin/login');
        $this->actingAs($this->owner());
        $this->get('/admin/inquiries/'.$inquiry->id)->assertOk()->assertSee('visitor@example.test');
        $this->put('/admin/inquiries/'.$inquiry->id, ['status' => 'follow-up', 'notes' => 'Review brief', 'assigned_to' => auth()->id()])->assertRedirect();
        $this->assertDatabaseHas('inquiries', ['id' => $inquiry->id, 'status' => 'follow-up']);
    }

    public function test_contact_validation_spam_and_csrf_protection(): void
    {
        $this->post('/contact', $this->inquiry(['email' => 'invalid']))->assertSessionHasErrors('fields.email');
        $bad = $this->inquiry();
        $bad['website'] = 'spam';
        $this->post('/contact', $bad)->assertSessionHasErrors('website');
        $bad = $this->inquiry();
        $bad['_form_token'] = Crypt::encryptString((string) now()->timestamp);
        $this->post('/contact', $bad)->assertSessionHasErrors('message');
        $bad = $this->inquiry();
        $bad['consent'] = '0';
        $this->post('/contact', $bad)->assertSessionHasErrors('consent');
        $this->assertDatabaseCount('inquiries', 0);
        $this->app->instance('env', 'production');
        $this->post('/contact', $this->inquiry())->assertStatus(419);
    }

    public function test_contact_field_configuration_and_csv_formula_safety(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner)->post('/admin/contact-fields', ['key' => 'quantity', 'label' => 'Quantity', 'type' => 'text', 'enabled' => 1, 'required' => 1, 'sort_order' => 20])->assertRedirect();
        $this->post('/contact', $this->inquiry())->assertSessionHasErrors('fields.quantity');
        $this->post('/contact', $this->inquiry(['quantity' => '1000', 'name' => '=HYPERLINK("unsafe")']))->assertRedirect();
        $csv = $this->get('/admin/inquiries/export')->assertOk()->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $field = FormField::where('key', 'quantity')->firstOrFail();
        $this->delete('/admin/contact-fields/'.$field->id)->assertRedirect();
        $this->assertDatabaseHas('inquiry_values', ['key' => 'quantity', 'value' => '1000']);
    }

    public function test_media_upload_replacement_alt_text_and_safe_deletion(): void
    {
        $this->actingAs($this->owner());
        $response = $this->postJson('/admin/media', ['image' => UploadedFile::fake()->image('pack.jpg', 300, 200), 'alt' => 'A packaging mockup']);
        $response->assertOk();
        $media = Media::findOrFail($response->json('id'));
        $url = $media->url;
        $this->get($url)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->put('/admin/media/'.$media->uuid, ['alt' => 'Replacement packaging mockup', 'image' => UploadedFile::fake()->image('replacement.png', 250, 250)])->assertRedirect();
        $this->assertSame($url, $media->fresh()->url);
        Page::first()->update(['featured_media_id' => $media->id]);
        $this->delete('/admin/media/'.$media->uuid)->assertStatus(422);
        Page::first()->update(['featured_media_id' => null]);
        $this->delete('/admin/media/'.$media->uuid)->assertRedirect();
        $this->get($url)->assertNotFound();
    }

    public function test_malicious_uploads_and_missing_alt_text_are_rejected(): void
    {
        $this->actingAs($this->owner());
        $this->postJson('/admin/media', ['image' => UploadedFile::fake()->create('script.svg', 2, 'image/svg+xml'), 'alt' => 'Unsafe vector'])->assertUnprocessable();
        $this->postJson('/admin/media', ['image' => UploadedFile::fake()->image('missing-alt.jpg')])->assertUnprocessable();
        $this->postJson('/admin/media', ['image' => UploadedFile::fake()->create('payload.php', 1, 'application/x-httpd-php'), 'alt' => 'Code'])->assertUnprocessable();
    }

    public function test_navigation_url_safety_parent_cycles_and_website_cache_invalidation(): void
    {
        $this->actingAs($this->owner());
        $menu = NavigationMenu::where('location', 'header')->firstOrFail();
        $item = $menu->items->first();
        $base = ['navigation_menu_id' => $menu->id, 'label' => 'Updated home', 'url' => '/', 'sort_order' => 0, 'enabled' => 1];
        $this->put('/admin/menu-items/'.$item->id, $base + ['parent_id' => $item->id])->assertSessionHasErrors('parent_id');
        $this->post('/admin/menu-items', array_replace($base, ['url' => 'javascript:alert(1)']))->assertSessionHasErrors('url');
        $this->get('/')->assertOk();
        $this->put('/admin/menu-items/'.$item->id, $base)->assertRedirect();
        $this->get('/')->assertSee('Updated home');
    }

    public function test_seo_manual_schema_validation_escaping_and_no_fabricated_business_schema(): void
    {
        $this->actingAs($this->owner());
        $bad = $this->content(['seo' => ['schema_mode' => 'manual', 'schema_json' => '{invalid']]);
        $this->post('/admin/cms/pages', $bad)->assertSessionHasErrors('seo.schema_json');
        $json = json_encode(['@context' => 'https://schema.org', '@type' => 'WebPage', 'name' => '</script><script>alert(1)</script>']);
        $this->post('/admin/cms/pages', $this->content(['seo' => ['title' => 'A precise SEO title', 'description' => 'A useful page description.', 'schema_mode' => 'manual', 'schema_json' => $json]]))->assertRedirect()->assertSessionHasNoErrors();
        $this->get('/useful-content')->assertOk()->assertSee('A precise SEO title')->assertDontSee('</script><script>alert(1)</script>', false)->assertSee('application/ld+json', false);
        $this->get('/')->assertDontSee('"legalName"', false)->assertDontSee('LocalBusiness')->assertSee('noindex,nofollow', false);
        $this->get('/faq')->assertSee('FAQPage');
    }

    public function test_empty_disabled_sections_and_missing_business_details_stay_hidden(): void
    {
        $page = Page::whereSlug('about')->firstOrFail();
        $page->sections()->create(['type' => 'text', 'title' => 'Disabled heading', 'body' => 'Hidden content', 'enabled' => false]);
        $page->sections()->create(['type' => 'text', 'enabled' => true]);
        $this->get('/about')->assertOk()->assertDontSee('Disabled heading')->assertDontSee('mailto:')->assertDontSee('tel:');
        $this->actingAs($this->owner())->put('/admin/website-settings', ['business' => ['email' => 'verified@example.test'], 'indexable' => 1])->assertRedirect();
        $this->get('/about')->assertSee('verified@example.test');
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap:');
    }

    public function test_owner_permissions_protect_the_last_owner_and_seeder_preserves_edits(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner)->put('/admin/cms-users/'.$owner->id, ['name' => $owner->name, 'email' => $owner->email, 'cms_role' => 'editor'])->assertStatus(422);
        $this->delete('/admin/cms-users/'.$owner->id)->assertStatus(422);
        $page = Page::whereSlug('about')->firstOrFail();
        $page->update(['body' => '<p>Client-approved updated introduction.</p>']);
        $this->seed(CmsSeeder::class);
        $this->assertSame('<p>Client-approved updated introduction.</p>', $page->fresh()->body);
        $this->assertDatabaseCount('posts', 3);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_custom_menu_lifecycle_and_paginated_seo_search(): void
    {
        $this->actingAs($this->owner());
        $this->post('/admin/menus', ['name' => 'Support', 'location' => 'support'])->assertRedirect();
        $menu = NavigationMenu::where('location', 'support')->firstOrFail();
        $this->put('/admin/menus/'.$menu->id, ['name' => 'Help links'])->assertRedirect();
        $this->assertSame('Help links', $menu->fresh()->name);
        $this->delete('/admin/menus/'.$menu->id)->assertRedirect();
        $this->assertDatabaseMissing('navigation_menus', ['id' => $menu->id]);
        $this->delete('/admin/menus/'.NavigationMenu::where('location', 'header')->value('id'))->assertStatus(422);
        $this->get('/admin/seo?type=posts&q=artwork')->assertOk()->assertSee('A cleaner artwork handover')->assertDontSee('Before the design:');
    }

    public function test_inline_images_use_library_alt_and_concepts_do_not_claim_service_schema(): void
    {
        $media = Media::firstOrFail();
        $html = app(RichText::class)->clean('<p><img src="'.$media->url.'" alt=""></p>');
        $this->assertStringContainsString(e($media->alt), $html);
        $service = Service::firstOrFail();
        $data = app(Seo::class)->data($service);
        $this->assertSame('WebPage',$data['schema'][0]['@type']);
        $service->update(['confirmed' => true]);
        $this->assertSame('Service',app(Seo::class)->data($service)['schema'][0]['@type']);
    }
}
