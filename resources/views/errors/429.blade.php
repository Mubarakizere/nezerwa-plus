@extends('errors.layout')

@section('title', 'Slow Down a Bit')
@section('code', '429')

{{-- Orange — warning/caution without full red alarm --}}
@push('head')
<style>
    :root {
        --accent:      #ea580c;
        --accent-2:    #f97316;
        --accent-bg:   rgba(234, 88, 12, 0.08);
        --accent-ring: rgba(234, 88, 12, 0.2);
        --accent-glow: rgba(249, 115, 22, 0.28);
    }
    html.dark {
        --accent:      #fb923c;
        --accent-2:    #f97316;
        --accent-bg:   rgba(251, 146, 60, 0.1);
        --accent-ring: rgba(251, 146, 60, 0.25);
        --accent-glow: rgba(251, 146, 60, 0.28);
    }

    /* Countdown timer animation */
    .countdown {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-family: 'JetBrains Mono', monospace;
        font-size: 1.6rem;
        font-weight: 700;
        color: var(--accent);
        margin: 0.5rem 0 1.25rem;
    }
    .countdown-label {
        font-size: 0.78rem;
        font-weight: 500;
        font-family: 'Inter', sans-serif;
        color: var(--c-muted);
        margin-bottom: 1.5rem;
    }
</style>
@endpush

@section('icon')
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
    <line x1="12" y1="9" x2="12" y2="13"/>
    <line x1="12" y1="17" x2="12.01" y2="17"/>
</svg>
@endsection

@section('message')
{{ $exception->getMessage() ?: "Too many requests from your connection in a short window. Wait a few seconds and you're good to go again." }}
@endsection

@push('after-message')
<div class="countdown" id="cd-timer" aria-live="polite">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 15.5 14"/></svg>
    <span id="cd-secs">30</span>s
</div>
<p class="countdown-label">Redirecting automatically once the cooldown ends</p>
@endpush

@section('actions')
<button onclick="window.location.reload()" class="btn btn-primary" id="btn-try-again-429">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
        <path d="M3 3v5h5"/>
        <path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/>
        <path d="M16 21h5v-5"/>
    </svg>
    Try Again Now
</button>
<a href="{{ url('/') }}" class="btn btn-ghost" id="btn-home-429">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
        <polyline points="9 22 9 12 15 12 15 22"/>
    </svg>
    Dashboard
</a>
@endsection

@push('scripts')
<script>
    let secs = 30;
    const el = document.getElementById('cd-secs');
    const timer = setInterval(() => {
        secs--;
        if (el) el.textContent = secs;
        if (secs <= 0) {
            clearInterval(timer);
            window.location.reload();
        }
    }, 1000);
</script>
@endpush
