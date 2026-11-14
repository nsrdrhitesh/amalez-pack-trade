<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Media;
use App\Models\Page;
use App\Models\Post;
use App\Models\Service;
use App\Models\Tag;
use App\Models\User;
use App\Services\Cms;
use App\Services\RichText;
use App\Services\Seo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ContentController extends Controller
{
    public static function model(string $type): string
    {
        return match ($type) {
            'pages' => Page::class,'services' => Service::class,'posts' => Post::class,default => abort(404)
        };
    }

    public function index(Request $r, string $type)
    {
        $class = self::model($type);
        Gate::authorize('viewAny', $class);
        $q = $class::query()->with(['featuredMedia', 'seo'])->when($r->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$r->string('q').'%'))->when($r->filled('status'), fn ($q) => $q->where('status', $r->string('status')));

        return view('admin.cms.index', ['type' => $type, 'records' => $q->orderBy('sort_order')->latest('id')->paginate(15)->withQueryString(), 'tree' => $type === 'services' ? Cms::serviceTree(Service::all()) : []]);
    }

    public function create(string $type)
    {
        $class = self::model($type);
        Gate::authorize('create', $class);

        return $this->form($type, new $class(['status' => 'draft']));
    }

    public function edit(string $type, int $id)
    {
        $record = self::model($type)::with(['sections.media', 'seo.image', 'gallery.media'])->findOrFail($id);
        Gate::authorize('update', $record);

        return $this->form($type, $record);
    }

    private function form($type, $record)
    {
        return view('admin.cms.edit', compact('type', 'record') + ['media' => Media::latest()->limit(500)->get(), 'parents' => Service::orderBy('title')->get(), 'categories' => Category::orderBy('name')->get(), 'tags' => Tag::orderBy('name')->get(), 'authors' => User::where('is_admin', true)->get(['id', 'name'])]);
    }

    public function store(Request $r, string $type)
    {
        $class = self::model($type);
        Gate::authorize('create', $class);

        return $this->save($r, $type, new $class);
    }

    public function update(Request $r, string $type, int $id)
    {
        $record = self::model($type)::findOrFail($id);
        Gate::authorize('update', $record);

        return $this->save($r, $type, $record);
    }

    private function save(Request $r, string $type, $record)
    {
        $data = $r->validate(['title' => 'required|string|max:255', 'slug' => ['required', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'max:160', Rule::unique($record->getTable(), 'slug')->ignore($record->id)], 'excerpt' => 'nullable|string|max:1200', 'body' => 'nullable|string|max:200000', 'status' => ['required', Rule::in(['draft', 'published', 'scheduled'])], 'published_at' => 'nullable|date|required_if:status,scheduled', 'featured_media_id' => 'nullable|exists:media,id', 'sort_order' => 'required|integer|min:0|max:100000', 'parent_id' => 'nullable|exists:services,id', 'author_name' => 'nullable|string|max:150', 'comments_enabled' => 'nullable|boolean', 'featured' => 'nullable|boolean', 'confirmed' => 'nullable|boolean', 'template' => ['nullable', Rule::in(['standard', 'home', 'contact', 'faq', 'legal'])],
            'categories' => 'nullable|array|max:30', 'categories.*' => 'integer|exists:categories,id', 'tags' => 'nullable|array|max:30', 'tags.*' => 'integer|exists:tags,id', 'related' => 'nullable|array|max:20', 'related.*' => 'integer|exists:services,id',
            'seo' => 'nullable|array', 'seo.title' => 'nullable|string|max:255', 'seo.description' => 'nullable|string|max:1000', 'seo.canonical' => 'nullable|string|max:2048', 'seo.focus_keyword' => 'nullable|string|max:255', 'seo.secondary_keywords' => 'nullable|string|max:1000', 'seo.search_intent' => 'nullable|string|max:255', 'seo.geo_facts' => 'nullable|string|max:10000', 'seo.og_title' => 'nullable|string|max:255', 'seo.og_description' => 'nullable|string|max:1000', 'seo.twitter_title' => 'nullable|string|max:255', 'seo.twitter_description' => 'nullable|string|max:1000', 'seo.index' => 'nullable|boolean', 'seo.schema_mode' => ['nullable', Rule::in(['auto', 'manual', 'none'])], 'seo.schema_json' => 'nullable|string|max:50000', 'seo.image_id' => 'nullable|exists:media,id',
            'sections' => 'nullable|array|max:60', 'sections.*.type' => ['required', Rule::in(['text', 'image', 'faq', 'cta', 'services', 'posts', 'gallery'])], 'sections.*.title' => 'nullable|string|max:255', 'sections.*.body' => 'nullable|string|max:50000', 'sections.*.media_id' => 'nullable|exists:media,id', 'sections.*.enabled' => 'nullable|boolean', 'sections.*.sort_order' => 'required|integer|min:0|max:100000',
            'gallery' => 'nullable|array|max:30', 'gallery.*.media_id' => 'required|exists:media,id', 'gallery.*.caption' => 'nullable|string|max:255', 'gallery.*.sort_order' => 'required|integer|min:0|max:100000']);
        if ($type === 'pages' && (! $record->exists || $record->slug !== $data['slug']) && in_array($data['slug'], ['admin', 'services', 'blog', 'media', 'sitemap', 'robots', 'up', 'storage', 'build'], true)) {
            throw ValidationException::withMessages(['slug' => 'This slug is reserved.']);
        }
        if ($record instanceof Page && $record->exists && $record->slug === 'home' && ($data['slug'] !== 'home' || $data['status'] !== 'published')) {
            throw ValidationException::withMessages(['slug' => 'The homepage must remain published at home.']);
        }
        if ($type === 'services') {
            $parent = isset($data['parent_id']) ? Service::find($data['parent_id']) : null;
            $seen = [];
            while ($parent) {
                if ($parent->id === $record->id || isset($seen[$parent->id])) {
                    throw ValidationException::withMessages(['parent_id' => 'A service cannot be its own ancestor.']);
                }$seen[$parent->id] = true;
                $parent = $parent->parent;
            }
        }
        if ($type === 'pages' && $record->exists && in_array($record->slug, ['home', 'services', 'blog', 'contact'], true) && ($record->slug !== $data['slug'] || $data['status'] !== 'published')) {
            throw ValidationException::withMessages(['slug' => 'Keep core website pages published at their existing URL.']);
        }
        if ($type === 'pages' && $record->exists && in_array($record->slug, ['home', 'contact'], true) && ($data['template'] ?? 'standard') !== $record->template) {
            throw ValidationException::withMessages(['template' => 'Keep the required homepage or contact template.']);
        }
        $breadcrumb = $r->validate(['seo.breadcrumbs' => 'nullable|boolean', 'seo.breadcrumb_title' => 'nullable|string|max:150']);
        $seo = app(Seo::class)->validate(array_merge($data['seo'] ?? [], $breadcrumb['seo'] ?? []));
        $clean = app(RichText::class);
        DB::transaction(function () use ($data, $record, $type, $seo, $clean) {
            $fields = collect($data)->only(['title', 'slug', 'excerpt', 'status', 'published_at', 'featured_media_id', 'sort_order'])->all();
            $fields['body'] = $clean->clean($data['body'] ?? '');
            $fields['author_id'] = $record->author_id ?? auth()->id();
            if ($type === 'pages') {
                $fields['template'] = $data['template'] ?? 'standard';
            }
            if ($type === 'services') {
                $fields += ['parent_id' => $data['parent_id'] ?? null, 'featured' => (bool) ($data['featured'] ?? false), 'confirmed' => (bool) ($data['confirmed'] ?? false)];
            }
            if ($type === 'posts') {
                $fields += ['author_name' => $data['author_name'] ?? null, 'comments_enabled' => (bool) ($data['comments_enabled'] ?? false)];
            }
            $record->fill($fields)->save();
            $record->seo()->updateOrCreate([], ['data' => collect($seo)->except('image_id')->all(), 'image_id' => $seo['image_id'] ?? null]);
            $record->sections()->delete();
            foreach ($data['sections'] ?? [] as $s) {
                $record->sections()->create(['type' => $s['type'], 'title' => $s['title'] ?? null, 'body' => $clean->clean($s['body'] ?? ''), 'media_id' => $s['media_id'] ?? null, 'enabled' => (bool) ($s['enabled'] ?? false), 'sort_order' => $s['sort_order']]);
            }
            $record->gallery()->delete();
            foreach (collect($data['gallery'] ?? [])->unique('media_id') as $g) {
                $record->gallery()->create($g);
            }
            if ($type === 'posts') {
                $record->categories()->sync($data['categories'] ?? []);
                $record->tags()->sync($data['tags'] ?? []);
            }
            if ($type === 'services') {
                $record->related()->sync(array_values(array_diff($data['related'] ?? [], [$record->id])));
            }
            Cms::log('content.saved', $record, ['fields' => array_keys($fields)]);
        });
        Cms::forget();

        return redirect()->route('admin.cms.edit', [$type, $record->id])->with('status', 'Content saved.');
    }

    public function destroy(string $type, int $id)
    {
        $record = self::model($type)::findOrFail($id);
        Gate::authorize('delete', $record);
        abort_if($type === 'pages' && $record->slug === 'home', 422, 'The homepage cannot be deleted.');
        abort_if($type === 'services' && $record->children()->exists(), 422, 'Move child services before deleting this parent.');
        DB::transaction(function () use ($record) {
            Cms::log('content.deleted', $record);
            $record->sections()->delete();
            $record->seo()->delete();
            $record->gallery()->delete();
            $record->delete();
        });
        Cms::forget();

        return redirect()->route('admin.cms.index', $type)->with('status', 'Content deleted.');
    }
}
