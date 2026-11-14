@foreach($items->where('parent_id',$parent) as $item)
@php($children=$items->where('parent_id',$item->id))
@if($children->isNotEmpty())<details class="site-nav-group"><summary>{{ $item->label }}</summary><div class="site-nav-sub"><a href="{{ $item->url }}" @if($item->new_tab)target="_blank" rel="noopener noreferrer"
@endif>{{ $item->label }}</a>
@include('site.navigation',['items'=>$items,'parent'=>$item->id])</div></details>
@else<a href="{{ $item->url }}" @if($item->new_tab)target="_blank" rel="noopener noreferrer"
@endif @if(request()->getPathInfo()===$item->url)aria-current="page"
@endif>{{ $item->label }}</a>
@endif @endforeach
