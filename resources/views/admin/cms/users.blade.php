@extends('admin.layouts.cms') @section('title','Users & roles') @section('content')<div class="dashboard-heading"><div><span class="eyebrow">OWNER-ONLY ACCESS CONTROL</span><h1>The people behind the website.</h1><p>Owners manage access and settings. Managers handle content and private inquiries. Editors manage public content. Visitors have no admin access.</p></div></div><x-cms.filters/>
@foreach($records->getCollection()->concat([new \App\Models\User(['cms_role'=>'editor'])]) as $user)<details class="panel cms-form-panel" @if(!$user->exists)open
@endif><summary>{{ $user->exists?$user->name:'Create a user' }} · {{ $user->cms_role??'visitor' }}</summary><form method="POST" action="{{ $user->exists?route('admin.users.update',$user):route('admin.users.store') }}">
@csrf @if($user->exists)
@method('PUT')
@endif<div class="cms-form-grid"><x-cms.field name="name" label="Name" :value="$user->name" required/><x-cms.field name="email" label="Email" type="email" :value="$user->email" required/><label>Role<select name="cms_role">
@foreach(['owner','manager','editor','visitor'] as $role)<option @selected(($user->cms_role??'visitor')===$role)>{{ $role }}</option>
@endforeach</select></label><x-cms.field name="password" :label="$user->exists?'New password (leave empty to preserve)':'Password'" type="password" :required="!$user->exists" minlength="12" autocomplete="new-password"/><x-cms.field name="password_confirmation" label="Confirm password" type="password" :required="!$user->exists" autocomplete="new-password"/></div><small>Passwords need at least 12 characters, uppercase, lowercase, and a number. Your own owner role and the last owner are protected.</small><button class="button button-primary">Save user</button></form>
@if($user->exists&&$user->id!==auth()->id())<form method="POST" action="{{ route('admin.users.destroy',$user) }}" data-confirm="Delete this user? Their authored content will be preserved.">
@csrf @method('DELETE')<button class="cms-danger">Delete user</button></form>
@endif</details>
@endforeach{{ $records->links() }}
@endsection
