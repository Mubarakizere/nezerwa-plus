@extends('errors.layout')

@section('title', 'Access Denied')
@section('code', '403')

{{-- Rose/red — signals "stop" without full alarm --}}
@push('head')
<style>
    :root {
        --accent:      #e11d48;
        --accent-2:    #f43f5e;
        --accent-bg:   rgba(225, 29, 72, 0.08);
        --accent-ring: rgba(225, 29, 72, 0.2);
        --accent-glow: rgba(244, 63, 94, 0.28);
    }
    html.dark {
        --accent:      #fb7185;
        --accent-2:    #f43f5e;
        --accent-bg:   rgba(251, 113, 133, 0.1);
        --accent-ring: rgba(251, 113, 133, 0.25);
        --accent-glow: rgba(251, 113, 133, 0.28);
    }
</style>
@endpush

@section('icon')
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
    <circle cx="12" cy="16" r="1" fill="currentColor"/>
</svg>
@endsection

@section('message')
{{ $exception->getMessage() ?: "Your account doesn't have access to this area. If you think this is wrong, reach out to your administrator to check your role permissions." }}
@endsection

@section('actions')
<button onclick="window.history.length > 1 ? window.history.back() : (window.location.href = '{{ url('/') }}')" class="btn btn-primary" id="btn-back-403">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="m15 18-6-6 6-6"/>
    </svg>
    Go Back
</button>
<a href="{{ url('/') }}" class="btn btn-ghost" id="btn-dash-403">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
        <polyline points="9 22 9 12 15 12 15 22"/>
    </svg>
    Dashboard
</a>
@endsection
