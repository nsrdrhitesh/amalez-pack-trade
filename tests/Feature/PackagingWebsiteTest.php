<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PackagingWebsiteTest extends TestCase
{
    private function document(string $html): \DOMDocument
    {
        $document = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $document;
    }

    public function test_homepage_is_stateless_and_never_queries_the_database(): void
    {
        config(['session.driver' => 'database', 'session.connection' => 'nonexistent']);
        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });
        $this->get('/')->assertOk()->assertSee('Amalez Pack Trade')->assertCookieMissing(config('session.cookie'));
        $this->assertSame([], $queries);
    }

    public function test_all_sections_and_internal_call_to_action_targets_exist(): void
    {
        $document = $this->document($this->get('/')->assertOk()->getContent());
        $xpath = new \DOMXPath($document);
        foreach (['home', 'about', 'products', 'industries', 'solutions', 'manufacturing', 'gallery', 'contact'] as $id) {
            $this->assertSame(1, $xpath->query('//*[@id="'.$id.'"]')->length, $id);
        }
        foreach ($document->getElementsByTagName('a') as $link) {
            $href = $link->getAttribute('href');
            if (str_starts_with($href, '#')) {
                $this->assertNotSame('#', $href);
                $this->assertSame(1, $xpath->query('//*[@id="'.substr($href, 1).'"]')->length, $href);
            }
        }
        $this->assertSame(1, $document->getElementsByTagName('h1')->length);
        $this->assertSame(1, $document->getElementsByTagName('main')->length);
    }

    public function test_seo_uses_confirmed_domain_and_does_not_invent_an_organization(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $document = $this->document($html);
        $xpath = new \DOMXPath($document);
        $this->assertSame('https://amalezpacktrade.com/', $xpath->query('//link[@rel="canonical"]')->item(0)->getAttribute('href'));
        $this->assertSame('noindex,nofollow', $xpath->query('//meta[@name="robots"]')->item(0)->getAttribute('content'));
        foreach (['og:title', 'og:description', 'og:url', 'og:image'] as $property) {
            $this->assertSame(1, $xpath->query('//meta[@property="'.$property.'"]')->length);
        }
        $schema = json_decode($xpath->query('//script[@type="application/ld+json"]')->item(0)->textContent, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('WebSite', $schema['@type']);
        $this->assertArrayNotHasKey('telephone', $schema);
        $this->assertArrayNotHasKey('address', $schema);
        $this->assertArrayNotHasKey('aggregateRating', $schema);
        $this->assertStringNotContainsString('ISO 9001', $html);
    }

    public function test_all_images_are_local_valid_and_have_dimensions_and_alt_text(): void
    {
        $document = $this->document($this->get('/')->assertOk()->getContent());
        foreach ($document->getElementsByTagName('img') as $image) {
            $path = parse_url($image->getAttribute('src'), PHP_URL_PATH);
            $this->assertStringStartsWith('/images/', $path);
            $file = public_path(ltrim($path, '/'));
            $this->assertFileExists($file);
            $this->assertNotFalse(getimagesize($file), $file);
            $this->assertNotEmpty($image->getAttribute('alt'));
            $this->assertGreaterThan(0, (int) $image->getAttribute('width'));
            $this->assertGreaterThan(0, (int) $image->getAttribute('height'));
            $this->assertLessThan(150 * 1024, filesize($file));
        }
    }

    public function test_sitemap_is_stateless_and_contains_only_public_home(): void
    {
        config(['session.driver' => 'database', 'session.connection' => 'nonexistent']);
        $response = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml');
        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml);
        $this->assertSame('https://amalezpacktrade.com/', (string) $xml->url->loc);
        $this->assertStringNotContainsString('/admin', $response->getContent());
    }

    public function test_brand_and_products_can_be_changed_through_configuration(): void
    {
        config(['company.name' => 'Updated Packaging Brand', 'company.products' => []]);
        $this->get('/')->assertOk()->assertSee('Updated Packaging Brand')->assertDontSee('Stand-up pouches');
    }

    public function test_enquiry_is_explicitly_a_browser_only_preview(): void
    {
        $this->get('/')->assertOk()->assertSee('Nothing has been sent.')
            ->assertSee('Prepare requirement summary')->assertSee('does not send an enquiry');
        $this->post('/contact')->assertNotFound();
    }
}
