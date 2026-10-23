@extends('errors.layout')

@section('title', "Page Not Found")
@section('code', '404')

{{-- Amber accent for "lost/missing" feel --}}
@push('head')
<style>
    :root {
        --accent:      #d97706;
        --accent-2:    #f59e0b;
        --accent-bg:   rgba(217, 119, 6, 0.09);
        --accent-ring: rgba(217, 119, 6, 0.22);
        --accent-glow: rgba(245, 158, 11, 0.3);
    }
    html.dark {
        --accent:      #fbbf24;
        --accent-2:    #f59e0b;
        --accent-bg:   rgba(251, 191, 36, 0.1);
        --accent-ring: rgba(251, 191, 36, 0.25);
        --accent-glow: rgba(251, 191, 36, 0.28);
    }
</style>
@endpush

@section('icon')
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="11" cy="11" r="8"/>
    <path d="m21 21-4.35-4.35"/>
    <path d="M8 11h6"/>
</svg>
@endsection

@section('message')
{{ $exception->getMessage() ?: "That page doesn't exist here. It may have moved, been deleted, or the link was mistyped." }}
@endsection

@section('actions')
<a href="{{ url('/') }}" class="btn btn-primary" id="btn-dash-404">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
        <polyline points="9 22 9 12 15 12 15 22"/>
    </svg>
    Back to Dashboard
</a>
<button onclick="window.history.length > 1 ? window.history.back() : (window.location.href = '{{ url('/') }}')" class="btn btn-ghost" id="btn-back-404">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="m15 18-6-6 6-6"/>
    </svg>
    Previous Page
</button>
@endsection
