@extends('admin.layouts.cms') @section('title','Navigation menus') @section('content')<div class="dashboard-heading"><div><span class="eyebrow">CONNECTED EXPERIENCES</span><h1>Navigation menus</h1><p>Use independent page URLs. Order values control placement; nested items stay within their menu.</p></div></div>
<details class="panel cms-form-panel"><summary>Create a menu</summary><form method="POST" action="{{ route('admin.menus.store') }}" class="cms-form-grid">
@csrf<x-cms.field name="name" label="Menu name" required/><x-cms.field name="location" label="Location key (header, footer, or custom)" required/><button class="button button-primary">Create menu</button></form></details>
<datalist id="cms-page-urls">
@foreach($pages  as $page)<option value="{{ parse_url($page->url,PHP_URL_PATH) }}">{{ $page->title }}</option>
@endforeach @foreach($services  as $service)<option value="{{ parse_url($service->url,PHP_URL_PATH) }}">{{ $service->title }}</option>
@endforeach</datalist>
@foreach($menus  as $menu)<section class="panel cms-form-panel"><h2>{{ $menu->name }} <small>{{ $menu->location }}</small></h2>
<form method="POST" action="{{ route('admin.menus.update',$menu) }}" class="cms-actions">
@csrf
@method('PUT')
<x-cms.field name="name" label="Menu name" :value="$menu->name" required/>
<button class="button button-secondary">Rename menu</button>
</form>
@if(!in_array($menu->location,['header','footer'],true))
<form method="POST" action="{{ route('admin.menus.destroy',$menu) }}" data-confirm="Delete this custom menu and all of its links?">
@csrf
@method('DELETE')
<button class="cms-danger">Delete custom menu</button>
</form>
@endif
@foreach($menu->items->concat([new \App\Models\NavigationItem(['navigation_menu_id'=>$menu->id,'sort_order'=>$menu->items->count(),'enabled'=>true])]) as $item)<details class="cms-repeater" @if(!$item->exists)open
@endif><summary>{{ $item->exists?$item->label:'Add menu item' }}
@if($item->parent_id) · child item @endif</summary><form method="POST" action="{{ $item->exists?route('admin.menu-items.update',$item):route('admin.menu-items.store') }}">
@csrf @if($item->exists)
@method('PUT')
@endif<input type="hidden" name="navigation_menu_id" value="{{ $menu->id }}"><div class="cms-form-grid"><x-cms.field name="label" label="Link label" :value="$item->label" required/><x-cms.field name="url" label="Relative page path or HTTPS URL" :value="$item->url" required list="cms-page-urls"/><label>Parent item<select name="parent_id"><option value="">Top level</option>
@foreach($menu->items  as $parent)
@if($parent->id!==$item->id)<option value="{{ $parent->id }}" @selected($parent->id===$item->parent_id)>{{ $parent->label }}</option>
@endif
@endforeach</select></label><x-cms.field name="sort_order" label="Order" type="number" :value="$item->sort_order" min="0" required/></div><div class="cms-actions">
@foreach(['enabled'=>'Visible','new_tab'=>'Open in a new tab'] as $key=>$label)<input type="hidden" name="{{ $key }}" value="0"><label class="cms-check"><input type="checkbox" name="{{ $key }}" value="1" @checked($item->$key)>{{ $label }}</label>
@endforeach<button class="button button-primary">Save link</button></div></form>
@if($item->exists)<form method="POST" action="{{ route('admin.menu-items.destroy',$item) }}" data-confirm="Delete this navigation item and its children?">
@csrf @method('DELETE')<button class="cms-danger">Delete link</button></form>
@endif</details>
@endforeach</section>
@endforeach @endsection
