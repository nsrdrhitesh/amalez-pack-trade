@extends('admin.layouts.cms') @section('title','Inquiry details') @section('content')<div class="dashboard-heading"><div><span class="eyebrow">PRIVATE / INQUIRY</span><h1>Conversation details</h1><p>{{ $inquiry->reference }}</p></div><a class="button button-outline" href="{{ route('admin.inquiries.index') }}">All inquiries</a></div><div class="cms-editor-grid"><section class="panel cms-form-panel"><h2>Submitted information</h2><dl class="cms-definition">
@foreach($inquiry->values  as $value)<dt>{{ $value->label }}</dt><dd>{{ $value->value }}</dd>
@endforeach<dt>Submitted</dt><dd>{{ $inquiry->created_at->toIso8601String() }}</dd><dt>Consent recorded</dt><dd>{{ $inquiry->consented_at?->toIso8601String()??'Not recorded' }}</dd></dl></section><section class="panel cms-form-panel"><form method="POST" action="{{ route('admin.inquiries.update',$inquiry) }}">
@csrf @method('PUT')<h2>Follow-up</h2><label>Status<select name="status">
@foreach(['new','read','follow-up','closed'] as $status)<option @selected($inquiry->status===$status)>{{ $status }}</option>
@endforeach</select></label><label>Assign to<select name="assigned_to"><option value="">Unassigned</option>
@foreach($users  as $user)<option value="{{ $user->id }}" @selected($inquiry->assigned_to===$user->id)>{{ $user->name }}</option>
@endforeach</select></label><x-cms.field name="notes" label="Internal notes" type="textarea" :value="$inquiry->notes" rows="8"/><button class="button button-primary">Save follow-up</button></form>
@can('delete',$inquiry)<form method="POST" action="{{ route('admin.inquiries.destroy',$inquiry) }}" data-confirm="Permanently delete this inquiry and its submitted values?">
@csrf @method('DELETE')<button class="cms-danger">Delete inquiry</button></form>
@endcan</section></div>
@endsection
