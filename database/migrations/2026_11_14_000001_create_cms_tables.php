<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->string('cms_role', 20)->nullable());
        // Preserve existing administrators' access; new editor accounts are assigned explicitly.
        DB::table('users')->where('is_admin', true)->whereNull('cms_role')->update(['cms_role' => 'owner']);
        Schema::create('media', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->string('path')->unique();
            $t->string('original_name');
            $t->string('mime', 80);
            $t->unsignedBigInteger('size');
            $t->unsignedInteger('width');
            $t->unsignedInteger('height');
            $t->string('alt');
            $t->text('caption')->nullable();
            $t->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
        foreach (['pages', 'services', 'posts'] as $table) {
            Schema::create($table, function (Blueprint $t) use ($table) {
                $t->id();
                $t->string('title');
                $t->string('slug', 160)->unique();
                $t->text('excerpt')->nullable();
                $t->mediumText('body')->nullable();
                $t->string('status', 20)->default('draft')->index();
                $t->timestamp('published_at')->nullable()->index();
                $t->foreignId('featured_media_id')->nullable()->constrained('media')->restrictOnDelete();
                $t->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
                $t->unsignedInteger('sort_order')->default(0)->index();
                if ($table === 'pages') {
                    $t->string('template', 30)->default('standard');
                }
                if ($table === 'services') {
                    $t->foreignId('parent_id')->nullable()->constrained('services')->restrictOnDelete();
                    $t->boolean('featured')->default(false);
                    $t->boolean('confirmed')->default(false);
                }
                if ($table === 'posts') {
                    $t->boolean('comments_enabled')->default(false);
                    $t->string('author_name')->nullable();
                }
                $t->timestamps();
                $t->index(['status', 'published_at']);
            });
        }
        Schema::create('content_sections', function (Blueprint $t) {
            $t->id();
            $t->morphs('sectionable');
            $t->string('type', 30)->default('text');
            $t->string('title')->nullable();
            $t->mediumText('body')->nullable();
            $t->json('settings')->nullable();
            $t->foreignId('media_id')->nullable()->constrained('media')->restrictOnDelete();
            $t->unsignedInteger('sort_order')->default(0);
            $t->boolean('enabled')->default(true);
            $t->timestamps();
        });
        Schema::create('seo_metadata', function (Blueprint $t) {
            $t->id();
            $t->morphs('seoable');
            $t->unique(['seoable_type', 'seoable_id']);
            $t->json('data')->nullable();
            $t->foreignId('image_id')->nullable()->constrained('media')->restrictOnDelete();
            $t->timestamps();
        });
        Schema::create('media_links', function (Blueprint $t) {
            $t->id();
            $t->morphs('mediable');
            $t->foreignId('media_id')->constrained('media')->restrictOnDelete();
            $t->string('caption')->nullable();
            $t->unsignedInteger('sort_order')->default(0);
            $t->unique(['mediable_type', 'mediable_id', 'media_id']);
        });
        Schema::create('related_services', function (Blueprint $t) {
            $t->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $t->foreignId('related_service_id')->constrained('services')->cascadeOnDelete();
            $t->primary(['service_id', 'related_service_id']);
        });
        foreach (['categories', 'tags'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->string('slug', 160)->unique();
                $t->timestamps();
            });
        }
        foreach (['category' => 'categories', 'tag' => 'tags'] as $type => $table) {
            Schema::create('post_'.$type, function (Blueprint $t) use ($type, $table) {
                $t->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
                $t->foreignId($type.'_id')->constrained($table)->cascadeOnDelete();
                $t->primary(['post_id', $type.'_id']);
            });
        }
        Schema::create('comments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $t->string('name', 100);
            $t->string('email');
            $t->text('body');
            $t->string('status', 20)->default('pending')->index();
            $t->timestamps();
        });
        Schema::create('form_fields', function (Blueprint $t) {
            $t->id();
            $t->string('key', 60)->unique();
            $t->string('label', 120);
            $t->string('type', 20);
            $t->json('options')->nullable();
            $t->boolean('enabled')->default(true);
            $t->boolean('required')->default(false);
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
        });
        Schema::create('inquiries', function (Blueprint $t) {
            $t->id();
            $t->uuid('reference')->unique();
            $t->string('status', 20)->default('new')->index();
            $t->text('notes')->nullable();
            $t->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('consented_at')->nullable();
            $t->timestamps();
        });
        Schema::create('inquiry_values', function (Blueprint $t) {
            $t->id();
            $t->foreignId('inquiry_id')->constrained('inquiries')->cascadeOnDelete();
            $t->string('key', 60)->index();
            $t->string('label', 120);
            $t->text('value');
            $t->unique(['inquiry_id', 'key']);
        });
        Schema::create('navigation_menus', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('location', 30)->unique();
            $t->timestamps();
        });
        Schema::create('navigation_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('navigation_menu_id')->constrained('navigation_menus')->cascadeOnDelete();
            $t->foreignId('parent_id')->nullable()->constrained('navigation_items')->cascadeOnDelete();
            $t->string('label', 100);
            $t->string('url', 2048);
            $t->boolean('new_tab')->default(false);
            $t->boolean('enabled')->default(true);
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
        });
        Schema::create('website_settings', function (Blueprint $t) {
            $t->id();
            $t->string('key', 100)->unique();
            $t->json('value')->nullable();
            $t->timestamps();
        });
        Schema::create('activity_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('action', 100);
            $t->string('subject_type', 100)->nullable();
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->json('metadata')->nullable();
            $t->timestamps();
            $t->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        foreach (['activity_logs', 'website_settings', 'navigation_items', 'navigation_menus', 'inquiry_values', 'inquiries', 'form_fields', 'comments', 'post_tag', 'post_category', 'tags', 'categories', 'related_services', 'media_links', 'seo_metadata', 'content_sections', 'posts', 'services', 'pages', 'media'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('cms_role'));
    }
};
