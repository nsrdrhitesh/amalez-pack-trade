@extends('admin.layouts.cms') @section('title','Comment moderation') @section('content')<div class="dashboard-heading"><div><span class="eyebrow">EDITORIAL CONVERSATIONS</span><h1>Comment moderation</h1><p>Only approved comments appear on published articles.</p></div></div><x-cms.filters :statuses="['pending','approved','rejected']"/><div class="cms-stack">
@forelse($records  as $comment)<article class="panel cms-form-panel"><h2>{{ $comment->post->title }}</h2><small>{{ $comment->name }} · {{ $comment->email }} · {{ $comment->created_at->format('Y-m-d H:i') }} UTC</small><p class="cms-pre-wrap">{{ $comment->body }}</p><div class="cms-actions"><form method="POST" action="{{ route('admin.comments.update',$comment) }}">
@csrf @method('PUT')<label>Status<select name="status">
@foreach(['pending','approved','rejected'] as $status)<option @selected($comment->status===$status)>{{ $status }}</option>
@endforeach</select></label><button class="button button-outline small">Save moderation</button></form>
@can('delete',$comment)<form method="POST" action="{{ route('admin.comments.destroy',$comment) }}" data-confirm="Delete this comment?">
@csrf @method('DELETE')<button class="cms-danger">Delete</button></form>
@endcan</div></article>
@empty<div class="panel cms-form-panel empty-state">No comments match this view.</div>
@endforelse</div>{{ $records->links() }}
@endsection
