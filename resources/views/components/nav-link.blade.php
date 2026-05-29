@props(['active'])

@php
$classes = ($active ?? false)
            ? 'flex items-center rounded-xl border border-sky-200 bg-sky-100 px-4 py-3 text-sm font-semibold text-sky-950 shadow-sm transition duration-150 ease-in-out'
            : 'flex items-center rounded-xl px-4 py-3 text-sm font-semibold text-slate-800 transition duration-150 ease-in-out hover:bg-slate-100 hover:text-slate-950';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
