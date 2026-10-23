@extends('errors.layout')

@section('title', 'Sign In Required')
@section('code', '401')

{{-- Slate/blue — neutral, not alarming --}}
@push('head')
<style>
    :root {
        --accent:      #3b82f6;
        --accent-2:    #6366f1;
        --accent-bg:   rgba(59, 130, 246, 0.08);
        --accent-ring: rgba(59, 130, 246, 0.2);
        --accent-glow: rgba(99, 102, 241, 0.28);
    }
    html.dark {
        --accent:      #60a5fa;
        --accent-2:    #818cf8;
        --accent-bg:   rgba(96, 165, 250, 0.1);
        --accent-ring: rgba(96, 165, 250, 0.25);
        --accent-glow: rgba(96, 165, 250, 0.28);
    }
</style>
@endpush

@section('icon')
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
    <polyline points="10 17 15 12 10 7"/>
    <line x1="15" y1="12" x2="3" y2="12"/>
</svg>
@endsection

@section('message')
{{ $exception->getMessage() ?: "You need to be signed in to view this page. Log in with your account and we'll send you right back here." }}
@endsection

@section('actions')
<a href="{{ route('login') }}" class="btn btn-primary" id="btn-login-401">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
        <polyline points="10 17 15 12 10 7"/>
        <line x1="15" y1="12" x2="3" y2="12"/>
    </svg>
    Sign In
</a>
<a href="{{ url('/') }}" class="btn btn-ghost" id="btn-home-401">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
        <polyline points="9 22 9 12 15 12 15 22"/>
    </svg>
    Home
</a>
@endsection
