@extends('errors.layout')

@section('title', 'Down for Maintenance')
@section('code', '503')

{{-- Teal/cyan — "scheduled, planned, not broken" feel --}}
@push('head')
<style>
    :root {
        --accent:      #0891b2;
        --accent-2:    #06b6d4;
        --accent-bg:   rgba(8, 145, 178, 0.08);
        --accent-ring: rgba(8, 145, 178, 0.2);
        --accent-glow: rgba(6, 182, 212, 0.28);
    }
    html.dark {
        --accent:      #22d3ee;
        --accent-2:    #06b6d4;
        --accent-bg:   rgba(34, 211, 238, 0.1);
        --accent-ring: rgba(34, 211, 238, 0.25);
        --accent-glow: rgba(34, 211, 238, 0.28);
    }

    /* Pulsing dot indicator */
    .status-row {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 0.82rem;
        color: var(--c-muted);
        background: var(--accent-bg);
        border: 1px solid var(--accent-ring);
        padding: 5px 14px;
        border-radius: 100px;
        margin-bottom: 1.5rem;
    }
    .pulse-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--accent);
        flex-shrink: 0;
        animation: pulse-dot 1.4s ease-in-out infinite;
    }
    @keyframes pulse-dot {
        0%, 100% { opacity: 1; transform: scale(1); }
        50%       { opacity: 0.5; transform: scale(0.75); }
    }
</style>
@endpush

@section('icon')
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
</svg>
@endsection

@section('message')
{{ $exception->getMessage() ?: "We're running a scheduled update to keep things fast and reliable. Sit tight — we'll be back online very soon." }}
@endsection

@push('after-message')
<div>
    <span class="status-row">
        <span class="pulse-dot"></span>
        Maintenance in progress
    </span>
</div>
@endpush

@section('actions')
<button onclick="window.location.reload()" class="btn btn-primary" id="btn-check-503">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
        <path d="M3 3v5h5"/>
        <path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/>
        <path d="M16 21h5v-5"/>
    </svg>
    Check Again
</button>
@endsection
