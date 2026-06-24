@php
    $logoPath = \App\Models\AppSetting::getValue('invoice.school_logo_path');
    $logoDataUrl = null;

    if ($logoPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($logoPath)) {
        $mimeType = \Illuminate\Support\Facades\Storage::disk('public')->mimeType($logoPath) ?: 'image/png';
        $logoDataUrl = 'data:' . $mimeType . ';base64,' . base64_encode(\Illuminate\Support\Facades\Storage::disk('public')->get($logoPath));
    }
@endphp

@if ($logoDataUrl)
    <img
        src="{{ $logoDataUrl }}"
        alt="{{ config('app.name', 'STAR School Management System') }}"
        {{ $attributes->merge(['class' => 'object-contain']) }}
    >
@else
    <div {{ $attributes->merge(['class' => 'flex items-center justify-center rounded-xl bg-amber-100 text-xl font-black text-amber-700']) }}>
        *
    </div>
@endif
