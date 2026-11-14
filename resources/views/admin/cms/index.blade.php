@extends('admin.layouts.cms') @section('title',ucfirst($type)) @section('content')
<div class="dashboard-heading"><div><span class="eyebrow">CONTENT LIBRARY</span><h1>{{ ucfirst($type) }}</h1><p>Create, organize, and publish your website content.</p></div><a class="button button-primary" href="{{ route('admin.cms.create',$type) }}">Create {{ $type==='posts'?'article':rtrim($type,'s') }} <x-icon/></a></div>
<x-cms.filters :statuses="['draft','published','scheduled']"/>
@if($type==='services'&&count($tree))<details class="panel cms-tree"><summary>Service hierarchy</summary>
@foreach($tree  as $branch)<p class="cms-depth-{{ min($branch['depth'],8) }}">{{ str_repeat('› ',min($branch['depth'],12)) }}<a href="{{ route('admin.cms.edit',[$type,$branch['service']->id]) }}">{{ $branch['service']->title }}</a><small> /{{ $branch['service']->slug }}</small></p>
@endforeach</details>
@endif
<section class="panel"><div class="table-scroll"><table><thead><tr><th>Title</th><th>Publication</th><th>SEO</th><th>Order</th><th>Actions</th></tr></thead><tbody>
@forelse($records  as $record)<tr><td><a href="{{ route('admin.cms.edit',[$type,$record->id]) }}"><strong>{{ $record->title }}</strong></a><small class="cms-block">/{{ $record->slug }}</small></td><td><span class="badge">{{ $record->status }}</span><small class="cms-block">{{ $record->published_at?->format('Y-m-d H:i')??'No date set' }} UTC</small></td><td>{{ filled($record->seo?->data['description']??null)?'Configured':'Needs review' }}</td><td>{{ $record->sort_order }}</td><td><div class="cms-actions"><a href="{{ route('admin.cms.edit',[$type,$record->id]) }}">Edit</a>
@if($record->isPublic())<a href="{{ $record->url }}" target="_blank" rel="noopener noreferrer">View ↗</a>
@endif<form method="POST" action="{{ route('admin.cms.destroy',[$type,$record->id]) }}" data-confirm="Delete this content? This removes its sections and relationships.">
@csrf @method('DELETE')<button class="cms-danger">Delete</button></form></div></td></tr>
@empty<tr><td colspan="5" class="empty-state"><strong>No {{ $type }} match this view.</strong><p>Create your first item or clear the filters.</p></td></tr>
@endforelse</tbody></table></div><div class="cms-pagination">{{ $records->links() }}</div></section>
@endsection
