@php
    $logoExists = file_exists(public_path('images/branding/logo.png'));
@endphp

@if ($logoExists)
    <img
        src="{{ asset('images/branding/logo.png') }}"
        alt="FitHub"
        class="{{ $class ?? 'w-7 h-7' }} rounded object-contain"
    >
@else
    <span class="{{ $class ?? 'w-7 h-7' }} rounded bg-gradient-to-br from-gold to-gold-2 flex items-center justify-center font-display font-extrabold text-ink {{ $textClass ?? 'text-xs' }}">FH</span>
@endif
