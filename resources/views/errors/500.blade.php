@extends('errors.layout')

@section('title', 'Something Broke on Our End')
@section('code', '500')

{{-- Red — server error is serious --}}
@push('head')
<style>
    :root {
        --accent:      #dc2626;
        --accent-2:    #ef4444;
        --accent-bg:   rgba(220, 38, 38, 0.08);
        --accent-ring: rgba(220, 38, 38, 0.2);
        --accent-glow: rgba(239, 68, 68, 0.28);
    }
    html.dark {
        --accent:      #f87171;
        --accent-2:    #ef4444;
        --accent-bg:   rgba(248, 113, 113, 0.1);
        --accent-ring: rgba(248, 113, 113, 0.25);
        --accent-glow: rgba(248, 113, 113, 0.28);
    }

    .debug-box {
        margin-top: 1.25rem;
        background: rgba(220, 38, 38, 0.05);
        border: 1px solid rgba(220, 38, 38, 0.15);
        border-radius: 10px;
        padding: 0.875rem 1rem;
        text-align: left;
    }
    .debug-label {
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        color: var(--accent);
        margin-bottom: 0.4rem;
    }
    .debug-msg {
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.78rem;
        color: var(--c-muted);
        line-height: 1.5;
        word-break: break-all;
    }
</style>
@endpush

@section('icon')
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <rect width="20" height="8" x="2" y="2" rx="2"/>
    <rect width="20" height="8" x="2" y="14" rx="2"/>
    <line x1="6" y1="6" x2="6.01" y2="6" stroke-width="3"/>
    <line x1="6" y1="18" x2="6.01" y2="18" stroke-width="3"/>
    <line x1="10" y1="6" x2="16" y2="6"/>
    <line x1="10" y1="18" x2="14" y2="18"/>
</svg>
@endsection

@section('message')
@if(app()->environment('local') && isset($exception) && $exception->getMessage())
    <div>An exception was thrown. Details below.</div>
    <div class="debug-box">
        <p class="debug-label">Exception</p>
        <p class="debug-msg">{{ class_basename($exception) }}: {{ $exception->getMessage() }}</p>
        <p class="debug-label" style="margin-top:0.6rem">Location</p>
        <p class="debug-msg">{{ $exception->getFile() }}:{{ $exception->getLine() }}</p>
    </div>
@else
    Something broke on our end while handling that request. It's been logged and we'll look into it. Try refreshing — if it keeps happening, come back in a little while.
@endif
@endsection

@section('actions')
<button onclick="window.location.reload()" class="btn btn-primary" id="btn-reload-500">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
        <path d="M3 3v5h5"/>
        <path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/>
        <path d="M16 21h5v-5"/>
    </svg>
    Try Again
</button>
<a href="{{ url('/') }}" class="btn btn-ghost" id="btn-home-500">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
        <polyline points="9 22 9 12 15 12 15 22"/>
    </svg>
    Dashboard
</a>
@endsection
