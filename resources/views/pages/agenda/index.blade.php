@extends('layouts.admin')

@section('title', 'Agenda - Dentissa')

@section('content')
<div class="space-y-6">
    {{-- Header Section --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <x-ui.h1 as="h2" class="text-xl! text-slate-900">Agenda de Citas</x-ui.h1>
            <p class="mt-2 text-sm text-slate-500">Gestiona los horarios y citas medicas de la clinica.</p>
        </div>

        <button type="button" data-create-appointment-open class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#E91E63] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#d61b5b]">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-7-7v14"/></svg>
            Nueva cita
        </button>
    </div>

    {{-- Stats/Counters --}}
    <div class="grid gap-3 sm:grid-cols-4">
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Hoy</p>
            <p class="mt-2 text-xl font-semibold text-slate-900" data-stat-today>0</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Pendientes</p>
            <p class="mt-2 text-xl font-semibold text-amber-600" data-stat-pending>0</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Confirmadas</p>
            <p class="mt-2 text-xl font-semibold text-[#B5114A]" data-stat-confirmed>0</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Completadas</p>
            <p class="mt-2 text-xl font-semibold text-emerald-600" data-stat-completed>0</p>
        </div>
    </div>

    {{-- Main Layout: Calendar + Side Info --}}
    <div class="grid gap-6 lg:grid-cols-12">
        {{-- Left: Calendar View --}}
        <div class="lg:col-span-8 space-y-4">
            <x-calendar.grid />
        </div>

        {{-- Right: Appointments List / Details --}}
        <div class="lg:col-span-4 space-y-6">
            <section class="rounded-3xl border border-[#F5C2D6] bg-[#FFF7FA] p-6">
                <h3 class="text-sm font-bold text-[#B5114A] uppercase tracking-wider">Citas para Hoy</h3>
                <p class="mt-1 text-xs text-slate-500">{{ now()->translatedFormat('l d \d\e F') }}</p>

                <div class="mt-6 space-y-4">
                    {{-- Appointment Card Item --}}
                    <div id="today-appointments-container">
                        {{-- Populated by JS --}}
                    </div>

                    <button class="w-full rounded-2xl bg-[#E91E63] p-4 text-xs font-semibold text-white shadow-sm transition hover:bg-[#d61b5b]">
                        + Agendar nueva cita
                    </button>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Tratamientos Populares</h3>
                <div class="mt-4 space-y-3">
                    <div class="flex items-center justify-between rounded-xl bg-slate-50 p-3">
                        <span class="text-xs font-medium text-slate-600">Limpieza</span>
                        <span class="rounded-lg bg-white px-2 py-1 text-[10px] font-bold text-[#B5114A] shadow-sm">12 hoy</span>
                    </div>
                    <div class="flex items-center justify-between rounded-xl bg-slate-50 p-3">
                        <span class="text-xs font-medium text-slate-600">Extraccion</span>
                        <span class="rounded-lg bg-white px-2 py-1 text-[10px] font-bold text-sky-600 shadow-sm">5 hoy</span>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

{{-- Modals --}}
<x-calendar.day-details-modal />

@vite('resources/js/pages/agenda/index.js')
@endsection
