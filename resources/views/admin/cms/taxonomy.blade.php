@extends('admin.layouts.cms') @section('title',ucfirst($type)) @section('content')<div class="dashboard-heading"><div><span class="eyebrow">JOURNAL ORGANIZATION</span><h1>{{ ucfirst($type) }}</h1></div></div><x-cms.filters/><section class="panel cms-form-panel"><h2>Create a term</h2><form method="POST" action="{{ route('admin.taxonomy.store',$type) }}" class="cms-form-grid">
@csrf<x-cms.field name="name" label="Name" required/><x-cms.field name="slug" label="Slug (generated if empty)"/><button class="button button-primary">Create term</button></form></section><div class="cms-stack">
@forelse($records  as $term)<section class="panel cms-form-panel"><form method="POST" action="{{ route('admin.taxonomy.update',[$type,$term->id]) }}" class="cms-form-grid">
@csrf @method('PUT')<x-cms.field name="name" label="Name" :value="$term->name" required/><x-cms.field name="slug" label="Slug" :value="$term->slug" required/><button class="button button-outline">Save · {{ $term->posts_count }} articles</button></form><form method="POST" action="{{ route('admin.taxonomy.destroy',[$type,$term->id]) }}" data-confirm="Delete this term? Articles will be preserved.">
@csrf @method('DELETE')<button class="cms-danger">Delete term</button></form></section>
@empty<div class="panel cms-form-panel empty-state">No terms found.</div>
@endforelse</div>{{ $records->links() }}
@endsection
