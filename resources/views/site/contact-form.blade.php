<section class="pack-container site-contact"><div><span class="pack-kicker">START WITH A CONVERSATION</span><h2>{{ $settings['branding']['contact_heading']??'' }}</h2><p>{{ $settings['branding']['contact_intro']??'' }}</p>
@if(!empty($business['email']))<a class="pack-text-link" href="mailto:{{ $business['email'] }}">{{ $business['email'] }} ↗</a>
@endif @if(!empty($business['telephone']))<p>{{ $business['telephone'] }}</p>
@endif @if(!empty($business['address']))<p>{{ $business['address'] }}</p>
@endif</div><div>
@if(session('inquiry_reference'))<div class="site-success" role="status"><h3>Your inquiry has been received.</h3><p>Reference: {{ session('inquiry_reference') }}</p><p>The team can now review your requirement. Email delivery is separate from this confirmation.</p></div>
@endif @if($fields->isNotEmpty())<form method="POST" action="{{ route('contact.store') }}" class="site-form">
@csrf @include('site.spam-fields')
@include('site.form-errors')<div class="site-form-grid">
@foreach($fields  as $field)<label class="{{ $field->type==='textarea'?'site-form-full':'' }}" for="contact-{{ $field->key }}">{{ $field->label }} @if($field->required)<span>*</span>
@endif @if($field->type==='textarea')<textarea id="contact-{{ $field->key }}" name="fields[{{ $field->key }}]" rows="6" maxlength="10000" @required($field->required)>{{ old('fields.'.$field->key) }}</textarea>
@elseif(in_array($field->type,['select','service']))<select id="contact-{{ $field->key }}" name="fields[{{ $field->key }}]" @required($field->required)><option value="">Please select</option>
@if($field->type==='service')
@foreach($allServices  as $service)<option value="{{ $service->slug }}" @selected(old('fields.'.$field->key,request('service'))===$service->slug)>{{ $service->title }}</option>
@endforeach @else @foreach($field->options??[] as $option)<option @selected(old('fields.'.$field->key)===$option)>{{ $option }}</option>
@endforeach @endif</select>
@else<input id="contact-{{ $field->key }}" type="{{ $field->type }}" name="fields[{{ $field->key }}]" value="{{ old('fields.'.$field->key) }}" maxlength="1000" @required($field->required)>
@endif</label>
@endforeach</div><label class="site-consent"><input type="checkbox" name="consent" value="1" required> I agree to the use of my details to respond to this inquiry. <a href="{{ url('/privacy-policy') }}">Privacy policy</a></label><button class="pack-button pack-button-orange" type="submit">Send your inquiry <x-frontend::arrow/></button></form>
@else<p>The inquiry form is currently unavailable. Please use the verified contact details above when provided.</p>
@endif</div></section>
