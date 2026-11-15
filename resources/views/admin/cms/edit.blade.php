@extends('admin.layouts.cms') @section('title',($record->exists?'Edit ':'Create ').rtrim($type,'s')) @section('content')
<div class="dashboard-heading"><div><span class="eyebrow">{{ strtoupper($type) }} / EDITOR</span><h1>{{ $record->exists?$record->title:'A new beginning.' }}</h1></div><a class="button button-outline" href="{{ route('admin.cms.index',$type) }}">Back to library</a></div>
<form method="POST" action="{{ $record->exists?route('admin.cms.update',[$type,$record->id]):route('admin.cms.store',$type) }}" class="cms-content-form">
@csrf @if($record->exists) @method('PUT') @endif
<div class="cms-editor-grid"><div><section class="panel cms-form-panel"><h2>The essentials</h2><x-cms.field name="title" label="Title" :value="$record->title" required maxlength="255"/><x-cms.field name="slug" label="URL slug" :value="$record->slug" required maxlength="160" pattern="[a-z0-9]+(-[a-z0-9]+)*"/><x-cms.field name="excerpt" label="Short description" type="textarea" :value="$record->excerpt" maxlength="1200" rows="3"/><x-cms.editor name="body" label="Main content" :value="$record->body" :media="$media"/></section>
<section class="panel cms-form-panel" x-data="contentBlocks(
@js(old('sections',$record->sections->map(fn($s)=>$s->only('type','title','body','media_id','enabled','sort_order'))->all())))"><div class="flex-between"><h2>Optional content sections</h2><button type="button" class="button button-outline small" @click="add()">Add section</button></div><p>Empty or disabled sections are hidden. Adjust order values to arrange the page.</p><template x-for="(section,index) in sections" :key="section.key"><fieldset class="cms-repeater" @dragover.prevent @drop.prevent="move(dragIndex,index)"><legend x-text="'Section '+(index+1)"></legend><div class="cms-actions"><button type="button" class="button button-outline small" draggable="true" @dragstart="dragIndex=index" @dragend="dragIndex=null" aria-label="Drag section to reorder">Drag to reorder</button><button type="button" class="button button-outline small" @click="move(index,index-1)" :disabled="index===0">Move up</button><button type="button" class="button button-outline small" @click="move(index,index+1)" :disabled="index===sections.length-1">Move down</button></div><div class="cms-form-grid"><label>Section type<select :name="'sections['+index+'][type]'" x-model="section.type"><option value="text">Rich text / specifications / features</option><option value="image">Image and text</option><option value="faq">Question and answer</option><option value="cta">Contact call to action</option><option value="services">Live service cards</option><option value="posts">Latest articles</option><option value="gallery">Content image gallery</option></select></label><label>Order<input type="number" min="0" :name="'sections['+index+'][sort_order]'" x-model="section.sort_order"></label></div><label>Heading<input :name="'sections['+index+'][title]'" x-model="section.title" maxlength="255"></label><label>Content (sanitized HTML supported)<textarea :name="'sections['+index+'][body]'" x-model="section.body" rows="5"></textarea></label><label>Image<select :name="'sections['+index+'][media_id]'" x-model="section.media_id"><option value="">No image</option>
@foreach($media  as $image)<option value="{{ $image->id }}">{{ $image->alt }}</option>
@endforeach</select></label><div class="cms-actions"><input type="hidden" :name="'sections['+index+'][enabled]'" value="0"><label class="cms-check"><input type="checkbox" :name="'sections['+index+'][enabled]'" value="1" x-model="section.enabled"> Visible</label><button type="button" class="cms-danger" @click="sections.splice(index,1)">Remove section</button></div></fieldset></template><p x-show="!sections.length" class="cms-muted">No optional sections. Main content still displays.</p></section>
<section class="panel cms-form-panel" x-data="{images:
@js(old('gallery',$record->gallery->map(fn($g)=>$g->only('media_id','caption','sort_order'))->all()))}"><div class="flex-between"><h2>Image gallery</h2><button type="button" class="button button-outline small" @click="images.push({media_id:'',caption:'',sort_order:images.length})">Add image</button></div><template x-for="(image,index) in images" :key="index"><fieldset class="cms-repeater"><legend x-text="'Gallery image '+(index+1)"></legend><label>Media<select :name="'gallery['+index+'][media_id]'" x-model="image.media_id" required><option value="">Choose an image</option>
@foreach($media  as $image)<option value="{{ $image->id }}">{{ $image->alt }}</option>
@endforeach</select></label><label>Caption<input :name="'gallery['+index+'][caption]'" x-model="image.caption" maxlength="255"></label><label>Order<input type="number" min="0" :name="'gallery['+index+'][sort_order]'" x-model="image.sort_order"></label><button type="button" class="cms-danger" @click="images.splice(index,1)">Remove from gallery</button></fieldset></template></section>
@include('admin.cms.seo-fields')
</div><aside><section class="panel cms-form-panel cms-publish"><h2>Publication</h2><label>Status<select name="status">
@foreach(['draft','published','scheduled'] as $status)<option @selected(old('status',$record->status)===$status)>{{ $status }}</option>
@endforeach</select></label><x-cms.field name="published_at" label="Publish date and time (UTC)" type="datetime-local" :value="$record->published_at?->format('Y-m-d\TH:i')"/><x-cms.field name="sort_order" label="Display order" type="number" :value="$record->sort_order??0" required min="0"/><label>Featured image<select name="featured_media_id"><option value="">No featured image</option>
@foreach($media  as $image)<option value="{{ $image->id }}" @selected((string)old('featured_media_id',$record->featured_media_id)===(string)$image->id)>{{ $image->alt }}</option>
@endforeach</select></label><a href="{{ route('admin.media.index') }}" target="_blank" rel="noopener noreferrer">Open media library ↗</a>
@if($type==='pages')<label>Page template<select name="template">
@foreach(['standard','home','contact','faq','legal'] as $template)<option @selected(old('template',$record->template)===$template)>{{ $template }}</option>
@endforeach</select></label>
@endif
@if($type==='services')<label>Parent service<input type="search" placeholder="Search parent options" data-filter-select="service-parent"><select id="service-parent" name="parent_id"><option value="">Main service / no parent</option>
@foreach($parents  as $parent)
@if($parent->id!==$record->id)<option value="{{ $parent->id }}" @selected((string)old('parent_id',$record->parent_id)===(string)$parent->id)>{{ $parent->title }} /{{ $parent->slug }}</option>
@endif
@endforeach</select></label>
@foreach(['featured'=>'Featured on homepage','confirmed'=>'Client-confirmed service offering'] as $key=>$label)<input type="hidden" name="{{ $key }}" value="0"><label class="cms-check"><input type="checkbox" name="{{ $key }}" value="1" @checked(old($key,$record->$key))>{{ $label }}</label>
@endforeach<label>Related services<select name="related[]" multiple size="5">
@foreach($parents  as $parent)
@if($parent->id!==$record->id)<option value="{{ $parent->id }}" @selected(in_array($parent->id,old('related',$record->exists?$record->related->modelKeys():[])))>{{ $parent->title }}</option>
@endif
@endforeach</select></label>
@endif
@if($type==='posts')<x-cms.field name="author_name" label="Public author name" :value="$record->author_name"/><input type="hidden" name="comments_enabled" value="0"><label class="cms-check"><input type="checkbox" name="comments_enabled" value="1" @checked(old('comments_enabled',$record->comments_enabled))> Enable moderated comments</label>
@endif
<button class="button button-primary full-width" type="submit">Save content</button><small>Scheduled content becomes visible automatically when its date is reached. Dates use UTC.</small></section>
@if($type==='posts')
@foreach(['categories'=>$categories,'tags'=>$tags] as $taxonomy=>$terms)<section class="panel cms-form-panel"><h2>{{ ucfirst($taxonomy) }}</h2><input type="search" data-filter-list="{{ $taxonomy }}-options" aria-label="Search {{ $taxonomy }}" placeholder="Search {{ $taxonomy }}"><div id="{{ $taxonomy }}-options" class="cms-term-options">
@foreach($terms  as $term)<label class="cms-check"><input type="checkbox" name="{{ $taxonomy }}[]" value="{{ $term->id }}" @checked(in_array($term->id,old($taxonomy,$record->exists?$record->$taxonomy->modelKeys():[])))> {{ $term->name }}</label>
@endforeach</div><button type="button" class="button button-outline small" data-create-term="{{ route('admin.taxonomy.store',$taxonomy) }}" data-term-list="{{ $taxonomy }}-options" data-term-name="{{ $taxonomy }}">Create {{ $taxonomy==='categories'?'category':'tag' }}</button><p role="status" class="cms-term-status"></p></section>
@endforeach
@endif</aside></div></form>
@endsection
