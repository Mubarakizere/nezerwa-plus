@extends('errors.layout')

@section('title', 'Page Not Found (404)')
@section('code', '404')

@section('icon')
<svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="11" cy="11" r="8"/>
    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
    <line x1="8" y1="11" x2="14" y2="11"/>
</svg>
@endsection

@section('title', 'Page Not Found')

@section('message')
{{ $exception->getMessage() ?: "The page, transaction, or record you are trying to reach doesn't exist, may have been relocated, or is no longer available." }}
@endsection
