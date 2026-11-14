@extends('admin.layouts.cms') @section('title','Contact form fields') @section('content')<div class="dashboard-heading"><div><span class="eyebrow">CONTACT EXPERIENCE</span><h1>Contact form fields</h1><p>Enable, require, and reorder fields. Historic submissions retain their original labels and values.</p></div></div>
@foreach($fields->concat([new \App\Models\FormField(['sort_order'=>$fields->count(),'type'=>'text','enabled'=>true])]) as $field)<details class="panel cms-form-panel" @if(!$field->exists)open
@endif><summary>{{ $field->exists?$field->label:'Add a custom field' }}</summary><form method="POST" action="{{ $field->exists?route('admin.fields.update',$field):route('admin.fields.store') }}">
@csrf @if($field->exists)
@method('PUT')
@endif<div class="cms-form-grid"><x-cms.field name="key" label="Stable field key" :value="$field->key" required pattern="[a-z][a-z0-9_]*"/><x-cms.field name="label" label="Label" :value="$field->label" required/><label>Input type<select name="type">
@foreach(['text','email','tel','textarea','select','service'] as $type)<option @selected($field->type===$type)>{{ $type }}</option>
@endforeach</select></label><x-cms.field name="sort_order" label="Order" type="number" :value="$field->sort_order" required min="0"/></div><x-cms.field name="options" label="Select options (one per line)" type="textarea" :value="implode(PHP_EOL,$field->options??[])"/><div class="cms-actions">
@foreach(['enabled'=>'Visible','required'=>'Required'] as $key=>$label)<input type="hidden" name="{{ $key }}" value="0"><label class="cms-check"><input type="checkbox" name="{{ $key }}" value="1" @checked($field->$key)>{{ $label }}</label>
@endforeach<button class="button button-primary">Save field</button></div></form>
@if($field->exists&&!in_array($field->key,['name','email','message']))<form method="POST" action="{{ route('admin.fields.destroy',$field) }}" data-confirm="Delete this form field? Stored inquiries are preserved.">
@csrf @method('DELETE')<button class="cms-danger">Delete field</button></form>
@endif</details>
@endforeach @endsection
