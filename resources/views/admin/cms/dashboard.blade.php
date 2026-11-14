@extends('admin.layouts.cms') @section('title','Dashboard') @section('content')<div class="dashboard-heading"><div><span class="eyebrow">PACKTRADE / CONTENT STUDIO</span><h1>Welcome back, {{ explode(' ',auth()->user()->name)[0] }}.</h1><p>A clear view of your website, with real content statistics.</p></div><a class="button button-primary" href="{{ route('admin.cms.create','posts') }}">Write an article <x-icon/></a></div><div class="metric-grid">
@foreach($metrics  as $label=>$value)<article class="metric-card"><span>{{ $label }}</span><strong>{{ $value }}</strong><small>Current website records</small></article>
@endforeach</div>
<div class="cms-editor-grid"><section class="panel cms-form-panel"><h2>Your next move</h2>
@foreach([['pages','Edit website pages'],['services','Organize service hierarchy'],['posts','Manage the journal']] as [$type,$label])<a class="quick-action" href="{{ route('admin.cms.index',$type) }}"><span class="feature-icon"><x-icon name="file"/></span><strong>{{ $label }}</strong><x-icon/></a>
@endforeach<a class="quick-action" href="{{ route('admin.media.index') }}"><span class="feature-icon"><x-icon name="grid"/></span><strong>Curate the media library</strong><x-icon/></a></section><section class="panel cms-form-panel"><h2>Recent activity</h2>
@forelse($events  as $event)<div class="cms-event"><strong>{{ $event->action }}</strong><small>{{ $event->user?->name??'System' }} · {{ $event->created_at->diffForHumans() }}</small></div>
@empty<p>A fresh page. Your team's changes will appear here.</p>
@endforelse @can('cms-private')<a href="{{ route('admin.activity.index') }}">View activity log ↗</a>
@endcan</section></div>
@endsection
