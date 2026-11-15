<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    public function test_disabled_admin_module_has_no_public_entry_points(): void
    {
        config(['admin.enabled' => false]);
        foreach (['login', 'register', 'dashboard', 'users', 'content', 'analytics', 'settings'] as $page) {
            $this->get('/admin/'.$page)->assertNotFound();
        }
        foreach (['login', 'register', 'logout'] as $page) {
            $this->post('/admin/'.$page)->assertNotFound();
        }
        $this->get('/')->assertOk();
    }

    public function test_branding_metadata_follows_the_configured_name(): void
    {
        config(['company.name' => 'Amalez PackTrade']);
        $this->get('/')->assertOk()->assertSee('Amalez PackTrade')->assertDontSee('Forma');
    }

    public function test_public_pages_render_with_file_stores_and_no_database_configuration(): void
    {
        config([
            'admin.enabled' => false, 'session.driver' => 'file',
            'cache.default' => 'file', 'queue.default' => 'sync',
            'database.default' => 'not-configured',
        ]);
        $this->get('/')->assertOk();
        $this->get('/sitemap.xml')->assertOk();
    }
}
