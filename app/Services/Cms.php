<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\NavigationMenu;
use App\Models\WebsiteSetting;
use Illuminate\Support\Facades\Cache;

class Cms
{
    public static function settings(): array
    {
        return Cache::remember('cms.settings', 300, fn () => WebsiteSetting::pluck('value', 'key')->all());
    }

    public static function setting(string $key, $default = null)
    {
        return self::settings()[$key] ?? $default;
    }

    public static function forget(): void
    {
        Cache::forget('cms.settings');
        Cache::forget('cms.navigation');
    }

    public static function navigation(): array
    {
        return Cache::remember('cms.navigation', 300, fn () => NavigationMenu::with(['items' => fn ($q) => $q->where('enabled', true)])->get()->keyBy('location')->map(fn ($m) => $m->items)->all());
    }

    public static function log(string $action, $model = null, array $metadata = []): void
    {
        ActivityLog::create(['user_id' => auth()->id(), 'action' => $action, 'subject_type' => $model?->getMorphClass(), 'subject_id' => $model?->id, 'metadata' => $metadata]);
    }

    public static function serviceTree($services, $parent = null, int $depth = 0): array
    {
        $result = [];
        foreach ($services->where('parent_id', $parent)->sortBy('sort_order') as $s) {
            $result[] = ['service' => $s, 'depth' => $depth];
            $result = array_merge($result, self::serviceTree($services, $s->id, $depth + 1));
        }

        return $result;
    }

    public static function safeUrl(?string $url): bool
    {
        return is_string($url) && ! preg_match('/[\x00-\x20\\\\]/', $url) && ((str_starts_with($url, '/') && ! str_starts_with($url, '//')) || (filter_var($url, FILTER_VALIDATE_URL) && parse_url($url, PHP_URL_SCHEME) === 'https'));
    }
}
