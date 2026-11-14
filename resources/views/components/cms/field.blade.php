@props(['name','label','value'=>'','type'=>'text','required'=>false])
@php($key=str_replace(['[',']'],['.',''],$name))
@php($fieldId=$attributes->get('id','field-'.\Illuminate\Support\Str::random(10)))
<div class="cms-field"><label for="{{ $fieldId }}">{{ $label }}
@if($required) <span aria-hidden="true">*</span>
@endif</label>
@if($type==='textarea')<textarea id="{{ $fieldId }}" name="{{ $name }}" @required($required) {{ $attributes->except('id') }}>{{ old($key,$value) }}</textarea>
@else<input id="{{ $fieldId }}" name="{{ $name }}" type="{{ $type }}" value="{{ $type==='password'?'':old($key,$value) }}" @required($required) {{ $attributes->except('id') }}>
@endif @error($key)<small class="cms-error">{{ $message }}</small>
@enderror</div>
