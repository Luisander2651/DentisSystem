@php
    // Sanctum resolves the authenticated user from the browser cookies/session.
    $user = auth('sanctum')->user() ?? auth()->user();

    if ($user && method_exists($user, 'loadMissing')) {
        $user->loadMissing('role');
    }

    $sidebarRole = strtolower((string) ($user?->role?->name ?? ''));
@endphp

@extends('layouts.dashboard')
