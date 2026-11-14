@extends('admin.layouts.cms')
@section('title',request()->routeIs('admin.schema.index')?'Schema management':'SEO management')
@section('content')
<div class="dashboard-heading"><div><span class="eyebrow">SEARCH & ENTITY CLARITY</span><h1>Every page, considered.</h1><p>Metadata and schema are edited with their content. Manual JSON-LD replaces automatic content schema.</p></div></div>
<form method="GET" class="cms-filters">
<label>Content type<select name="type">
@foreach(['pages'=>'Pages','services'=>'Services','posts'=>'Blog posts'] as $key=>$label)
<option value="{{ $key }}" @selected($type===$key)>{{ $label }}</option>
@endforeach
</select></label>
<label>Search title<input name="q" value="{{ request('q') }}" type="search" maxlength="200"></label>
<button class="button button-secondary">Filter content</button>
</form>
<section class="panel"><div class="table-scroll"><table><thead><tr><th>Content</th><th>Type</th><th>SEO title</th><th>Schema</th><th>Indexing</th><th>Edit</th></tr></thead><tbody>
@forelse($records as $record)
<tr><td>{{ $record->title }}</td><td>{{ $type }}</td><td>{{ $record->seo?->data['title']??'Automatic title' }}</td><td>{{ $record->seo?->data['schema_mode']??'auto' }}</td><td>{{ !empty($record->seo?->data['index'])?'Allowed if website switch is enabled':'No index' }}</td><td><a href="{{ route('admin.cms.edit',[$type,$record->id]) }}#seo">Edit SEO & schema</a></td></tr>
@empty
<tr><td colspan="6">No content matches your search.</td></tr>
@endforelse
</tbody></table></div></section>
{{ $records->links() }}
@endsection
