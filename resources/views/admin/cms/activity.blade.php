@extends('admin.layouts.cms') @section('title','Activity logs') @section('content')<div class="dashboard-heading"><div><span class="eyebrow">ACCOUNTABILITY</span><h1>Recent activity</h1><p>Actions are recorded without passwords, inquiry messages, or private credentials.</p></div></div><x-cms.filters/><section class="panel"><div class="table-scroll"><table><thead><tr><th>Action</th><th>Administrator</th><th>Subject</th><th>Time (UTC)</th></tr></thead><tbody>
@forelse($records  as $event)<tr><td>{{ $event->action }}</td><td>{{ $event->user?->name??'System' }}</td><td>{{ class_basename($event->subject_type??'Website') }} {{ $event->subject_id }}</td><td>{{ $event->created_at->format('Y-m-d H:i:s') }}</td></tr>
@empty<tr><td colspan="4" class="empty-state">Activity will appear as your team updates the website.</td></tr>
@endforelse</tbody></table></div><div class="cms-pagination">{{ $records->links() }}</div></section>
@endsection
