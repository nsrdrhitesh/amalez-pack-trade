<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Media;
use App\Models\Page;
use App\Models\Post;
use App\Models\Service;
use App\Models\WebsiteSetting;
use App\Services\Cms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SettingsController extends Controller
{
    public function edit()
    {
        Gate::authorize('cms-settings');

        return view('admin.cms.settings', ['settings' => Cms::settings(), 'media' => Media::latest()->limit(500)->get()]);
    }

    public function update(Request $r)
    {
        Gate::authorize('cms-settings');
        $data = $r->validate(['business.legal_name' => 'nullable|string|max:255', 'business.email' => 'nullable|email|max:255', 'business.telephone' => 'nullable|string|max:80', 'business.address' => 'nullable|string|max:1000', 'business.verified' => 'nullable|boolean', 'business.local_business_verified' => 'nullable|boolean', 'branding.logo_media_id' => 'nullable|exists:media,id', 'branding.favicon_media_id' => 'nullable|exists:media,id', 'branding.tagline' => 'nullable|string|max:150', 'indexable' => 'nullable|boolean', 'notification_email' => 'nullable|email|max:255', 'analytics_id' => ['nullable', 'regex:/^G-[A-Z0-9]{4,20}$/'], 'analytics_position' => 'nullable|in:header,footer', 'social_links' => 'nullable|array|max:10', 'social_links.*.label' => 'required_with:social_links|string|max:60', 'social_links.*.url' => 'required_with:social_links|url:https|max:2048']);
        $copy = $r->validate(['branding.footer_logo_media_id' => 'nullable|exists:media,id', 'branding.hero_kicker' => 'nullable|string|max:150', 'branding.hero_footnote' => 'nullable|string|max:200', 'branding.footer_description' => 'nullable|string|max:500', 'branding.contact_heading' => 'nullable|string|max:150', 'branding.contact_intro' => 'nullable|string|max:500']);
        $data['branding'] = array_merge($data['branding'] ?? [], $copy['branding'] ?? []);
        foreach (['business' => [], 'branding' => [], 'social_links' => [], 'indexable' => false, 'notification_email' => '', 'analytics_id' => '', 'analytics_position' => 'header'] as $key => $default) {
            WebsiteSetting::updateOrCreate(['key' => $key], ['value' => $data[$key] ?? $default]);
        }Cms::forget();
        Cms::log('website-settings.updated', null, ['fields' => array_keys($data)]);

        return back()->with('status', 'Website settings saved.');
    }

    public function seo(Request $r)
    {
        Gate::authorize('viewAny', Page::class);
        $types = ['pages' => Page::class, 'services' => Service::class, 'posts' => Post::class];
        $type = array_key_exists($r->input('type', 'pages'), $types) ? $r->input('type', 'pages') : 'pages';
        $records = $types[$type]::with('seo')
            ->when($r->filled('q'), fn ($query) => $query->where('title', 'like', '%'.$r->string('q').'%'))
            ->orderBy('title')->paginate(25)->withQueryString();

        return view('admin.cms.seo', compact('records', 'type'));
    }

    public function activity(Request $r)
    {
        Gate::authorize('viewAny', ActivityLog::class);

        return view('admin.cms.activity', ['records' => ActivityLog::with('user')->when($r->filled('q'), fn ($q) => $q->where('action', 'like', '%'.$r->string('q').'%'))->latest()->paginate(30)->withQueryString()]);
    }
}
