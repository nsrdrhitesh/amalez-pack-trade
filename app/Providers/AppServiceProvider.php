<?php

namespace App\Providers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Comment;
use App\Models\ContentSection;
use App\Models\Inquiry;
use App\Models\Media;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Post;
use App\Models\Service;
use App\Models\Tag;
use App\Policies\ContentPolicy;
use App\Policies\PrivateRecordPolicy;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::anonymousComponentPath(resource_path('views/frontend/components'), 'frontend');
        foreach ([Page::class, Post::class, Service::class, Media::class, Category::class, Tag::class, ContentSection::class, NavigationMenu::class, NavigationItem::class] as $model) {
            Gate::policy($model, ContentPolicy::class);
        }
        foreach ([Inquiry::class, Comment::class, ActivityLog::class] as $model) {
            Gate::policy($model, PrivateRecordPolicy::class);
        }
        Gate::define('cms-settings', fn ($user) => $user->is_admin && $user->cms_role === 'owner');
        Gate::define('cms-users', fn ($user) => $user->is_admin && $user->cms_role === 'owner');
        Gate::define('cms-private', fn ($user) => $user->is_admin && in_array($user->cms_role, ['owner', 'manager'], true));
    }
}
