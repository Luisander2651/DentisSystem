@php
    $user = $authenticatedUser ?? auth('sanctum')->user() ?? auth()->user();
    $sidebarRole = $sidebarRole ?? strtolower((string) ($user?->role?->name ?? ''));
@endphp

@extends('layouts.dashboard')
