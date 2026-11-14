<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><meta name="csrf-token" content="{{ csrf_token() }}"><title>
@yield('title','CMS') | Amalez Pack Trade</title><link rel="icon" href="{{ asset('images/branding/mark.svg') }}" type="image/svg+xml">
@vite(['resources/css/app.css','resources/js/cms.js'])</head>
<body class="admin-page cms-admin" x-data="{sidebar:false}" @keydown.escape.window="sidebar=false"><a class="skip-link" href="#main">Skip to content</a><div class="sidebar-backdrop" x-show="sidebar" x-cloak @click="sidebar=false"></div>
<aside class="admin-sidebar" :class="{'is-open':sidebar}"><div class="sidebar-brand"><x-brand/><button class="icon-button mobile-only" @click="sidebar=false" aria-label="Close navigation"><x-icon name="close"/></button></div><div class="workspace-switch"><span class="workspace-avatar">P</span><div><strong>PackTrade CMS</strong><small>{{ ucfirst(auth()->user()->cms_role??'administrator') }}</small></div></div>
<nav aria-label="CMS navigation">
@foreach([['Dashboard','admin.dashboard',null,'grid'],['Pages','admin.cms.index','pages','file'],['Navigation','admin.menus.index',null,'menu'],['Services','admin.cms.index','services','grid'],['Blog posts','admin.cms.index','posts','file'],['Categories','admin.taxonomy.index','categories','file'],['Tags','admin.taxonomy.index','tags','file'],['Media library','admin.media.index',null,'file'],['SEO management','admin.seo.index',null,'chart'],['Schema management','admin.schema.index',null,'file']]  as  [$label,$name,$parameter,$icon])<a class="sidebar-link {{ request()->url()===route($name,$parameter) ? 'active':'' }}" href="{{ route($name,$parameter) }}"><x-icon :name="$icon"/><span>{{ $label }}</span></a>
@endforeach
@can('cms-private')
@foreach([['Comments','admin.comments.index'],['Contact inquiries','admin.inquiries.index'],['Activity logs','admin.activity.index']] as [$label,$name])<a class="sidebar-link" href="{{ route($name) }}"><x-icon name="file"/><span>{{ $label }}</span></a>
@endforeach
@endcan
@can('cms-settings')<a class="sidebar-link" href="{{ route('admin.website-settings.edit') }}"><x-icon name="settings"/>Settings & branding</a><a class="sidebar-link" href="{{ route('admin.fields.index') }}"><x-icon name="file"/>Contact form fields</a><a class="sidebar-link" href="{{ route('admin.users.index') }}"><x-icon name="users"/>Users & roles</a>
@endcan</nav>
<div class="sidebar-user"><span class="avatar">{{ mb_substr(auth()->user()->name,0,1) }}</span><div><strong>{{ auth()->user()->name }}</strong><small>{{ ucfirst(auth()->user()->cms_role??'administrator') }}</small></div><form method="POST" action="{{ route('admin.logout') }}">
@csrf<button class="icon-button" aria-label="Sign out"><x-icon name="logout"/></button></form></div></aside>
<div class="admin-shell"><header class="admin-topbar"><div class="breadcrumb"><button class="icon-button mobile-only" @click="sidebar=true" :aria-expanded="sidebar" aria-label="Open navigation"><x-icon name="menu"/></button><span>PackTrade</span><span>/</span><strong>
@yield('title','CMS')</strong></div><div class="topbar-actions"><a href="{{ route('home') }}" target="_blank" rel="noopener noreferrer">View website ↗</a><details class="cms-profile"><summary>{{ auth()->user()->name }}</summary><div class="dropdown"><p>{{ auth()->user()->email }}</p><form method="POST" action="{{ route('admin.logout') }}">
@csrf<button class="button button-outline" type="submit">Sign out</button></form></div></details></div></header>
<main id="main" class="admin-content">
@if(session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>
@endif @if($errors->any())<div class="alert alert-error" role="alert"><strong>Please review these fields.</strong><ul>
@foreach($errors->all() as $error)<li>{{ $error }}</li>
@endforeach</ul></div>
@endif @yield('content')</main>
<footer class="admin-footer"><span>© {{ date('Y') }} Amalez Pack Trade</span><span>Designed, Developed & Maintained by <a href="https://hiteshahire.in" target="_blank" rel="noopener noreferrer">Hitesh Ahire</a></span></footer></div></body></html>
