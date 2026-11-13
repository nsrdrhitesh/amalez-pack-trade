@extends('admin.layouts.app')
@section('title', $section === 'content' ? 'Content Management' : ucfirst($section))
@section('content')
<div class="dashboard-heading"><div><span class="eyebrow">SPACE FOR WHAT’S NEXT</span><h1>@yield('title')</h1><p>A considered place for the next phase of your workspace.</p></div></div><section class="panel placeholder-panel"><span class="placeholder-art" aria-hidden="true">✳</span><span class="badge neutral">Planned module</span><h2>Good things are taking shape.</h2><p>This is a clearly marked preview. {{ ['users' => 'User invitations, member roles, and team management', 'content' => 'Content editing, publishing, and media management', 'analytics' => 'Live metrics, date filters, and report exports', 'settings' => 'Profile editing and workspace preferences'][$section] }} will be implemented in a future phase.</p><a class="button button-primary" href="{{ route('admin.dashboard') }}">Back to overview <x-icon/></a></section>
@endsection
