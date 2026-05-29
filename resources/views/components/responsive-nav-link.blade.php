@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full rounded-lg border-l-4 border-sky-500 bg-sky-100 ps-3 pe-4 py-2 text-start text-base font-semibold text-sky-950 focus:outline-none focus:bg-sky-200 transition duration-150 ease-in-out'
            : 'block w-full rounded-lg border-l-4 border-transparent ps-3 pe-4 py-2 text-start text-base font-semibold text-slate-800 hover:bg-slate-100 hover:text-slate-950 hover:border-slate-300 focus:outline-none focus:bg-slate-100 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
