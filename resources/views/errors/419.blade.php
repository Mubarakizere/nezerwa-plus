@extends('errors.layout')

@section('title', 'Session Expired')
@section('code', '419')

{{-- Purple/violet — time / token expired feel --}}
@push('head')
<style>
    :root {
        --accent:      #7c3aed;
        --accent-2:    #8b5cf6;
        --accent-bg:   rgba(124, 58, 237, 0.08);
        --accent-ring: rgba(124, 58, 237, 0.2);
        --accent-glow: rgba(139, 92, 246, 0.28);
    }
    html.dark {
        --accent:      #a78bfa;
        --accent-2:    #8b5cf6;
        --accent-bg:   rgba(167, 139, 250, 0.1);
        --accent-ring: rgba(167, 139, 250, 0.25);
        --accent-glow: rgba(167, 139, 250, 0.28);
    }
</style>
@endpush

@section('icon')
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="12" cy="12" r="10"/>
    <polyline points="12 6 12 12 15.5 14"/>
</svg>
@endsection

@section('message')
{{ $exception->getMessage() ?: "Your session timed out after a period of inactivity — it's a security measure. Sign in again and pick up right where you left off." }}
@endsection

@section('actions')
<a href="{{ route('login') }}" class="btn btn-primary" id="btn-login-419">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
        <polyline points="10 17 15 12 10 7"/>
        <line x1="15" y1="12" x2="3" y2="12"/>
    </svg>
    Sign In Again
</a>
<button onclick="window.location.reload()" class="btn btn-ghost" id="btn-reload-419">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
        <path d="M3 3v5h5"/>
        <path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/>
        <path d="M16 21h5v-5"/>
    </svg>
    Refresh
</button>
@endsection
