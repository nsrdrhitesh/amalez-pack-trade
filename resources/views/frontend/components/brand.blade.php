<a href="{{ route('home') }}" class="pack-brand" aria-label="{{ config('company.name') }} home">
    @if(config('company.logo'))<img src="{{ asset(config('company.logo')) }}" width="180" height="48" alt="{{ config('company.name') }}">@else
    <svg viewBox="0 0 40 44" fill="none" aria-hidden="true"><path d="M3 12 20 3l17 9v20l-17 9L3 32V12Z" stroke="currentColor" stroke-width="2"/><path d="m3 12 17 9 17-9M20 21v20M12 7l17 10v10" stroke="currentColor" stroke-width="2"/></svg>
    <span>{{ config('company.name') }}<span class="brand-sub">PACKAGING & PRINTING SOLUTIONS</span></span>@endif
</a>
