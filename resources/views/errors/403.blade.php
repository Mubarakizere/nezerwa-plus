@extends('errors.layout')

@section('title', 'Access Restricted (403)')
@section('code', '403')

@section('icon')
<svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
    <line x1="12" y1="15" x2="12" y2="17"/>
</svg>
@endsection

@section('title', 'Access Restricted')

@section('message')
{{ $exception->getMessage() ?: "You do not have permission or role clearance to access this module. Please check your privileges or contact an administrator." }}
@endsection
