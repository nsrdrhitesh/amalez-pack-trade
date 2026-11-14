@props(['statuses'=>[]])<form method="GET" class="cms-filters"><label><span class="sr-only">Search</span><input name="q" value="{{ request('q') }}" placeholder="Search…" maxlength="100"></label>
@if($statuses)<label><span class="sr-only">Status</span><select name="status"><option value="">All statuses</option>
@foreach($statuses  as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ ucfirst($status) }}</option>
@endforeach</select></label>
@endif<button class="button button-outline small" type="submit">Filter</button><a href="{{ url()->current() }}">Clear</a></form>
