@props(['name' => 'arrow'])
<svg {{ $attributes->class(['icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@switch($name)
@case('grid') <rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/> @break
@case('users') <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m20 0v-2a4 4 0 0 0-3-3.9"/><circle cx="9" cy="7" r="4"/><path d="M16 3a4 4 0 0 1 0 8"/> @break
@case('chart') <path d="M3 3v18h18M7 16l5-6 4 3 5-8"/> @break
@case('file') <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6M8 13h8m-8 4h5"/> @break
@case('settings') <path d="m9 3-1 3-3 1-2 5 2 5 3 1 1 3h6l1-3 3-1 2-5-2-5-3-1-1-3z"/><circle cx="12" cy="12" r="3"/> @break
@case('bell') <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/> @break
@case('menu') <path d="M4 6h16M4 12h16M4 18h16"/> @break
@case('close') <path d="m6 6 12 12M6 18 18 6"/> @break
@case('check') <path d="m5 12 4 4L19 6"/> @break
@case('bolt') <path d="m13 2-9 12h7l-1 8L21 9h-8z"/> @break
@case('shield') <path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6zM8 12l3 3 5-6"/> @break
@case('logout') <path d="M9 21H4V3h5m5 5 5 4-5 4m-5-4h10"/> @break
@case('chevron') <path d="m7 10 5 5 5-5"/> @break
@default <path d="M4 12h16m-6-6 6 6-6 6"/>
@endswitch
</svg>
