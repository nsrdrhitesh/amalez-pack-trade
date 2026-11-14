<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Tag;
use App\Services\Cms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TaxonomyController extends Controller
{
    private function model($type)
    {
        return match ($type) {
            'categories' => Category::class,'tags' => Tag::class,default => abort(404)
        };
    }

    public function index(Request $r, string $type)
    {
        $class = $this->model($type);
        Gate::authorize('viewAny', $class);

        return view('admin.cms.taxonomy', ['type' => $type, 'records' => $class::withCount('posts')->when($r->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$r->string('q').'%'))->orderBy('name')->paginate(20)->withQueryString()]);
    }

    public function store(Request $r, string $type)
    {
        $class = $this->model($type);
        Gate::authorize('create', $class);
        $r->merge(['slug' => $r->input('slug') ?: Str::slug($r->input('name', ''))]);
        $data = $r->validate(['name' => 'required|string|max:120', 'slug' => ['required', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique((new $class)->getTable(), 'slug')]]);
        $record = $class::create($data);
        Cms::log('taxonomy.created', $record);

        return $r->expectsJson() ? response()->json($record->only('id', 'name', 'slug'), 201) : back()->with('status', 'Term created.');
    }

    public function update(Request $r, string $type, int $id)
    {
        $record = $this->model($type)::findOrFail($id);
        Gate::authorize('update', $record);
        $record->update($r->validate(['name' => 'required|string|max:120', 'slug' => ['required', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique($record->getTable(), 'slug')->ignore($id)]]));
        Cms::log('taxonomy.updated', $record);

        return back()->with('status', 'Term updated.');
    }

    public function destroy(string $type, int $id)
    {
        $record = $this->model($type)::findOrFail($id);
        Gate::authorize('delete', $record);
        Cms::log('taxonomy.deleted', $record);
        $record->delete();

        return back()->with('status', 'Term deleted; articles preserved.');
    }
}
