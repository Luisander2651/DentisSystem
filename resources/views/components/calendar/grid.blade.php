<section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <h3 class="text-lg font-bold text-slate-900" id="current-month-display">Cargando...</h3>
            <div class="flex gap-1">
                <button id="prev-month" class="p-1 rounded-lg hover:bg-slate-100 text-slate-600 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                </button>
                <button id="next-month" class="p-1 rounded-lg hover:bg-slate-100 text-slate-600 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                </button>
            </div>
        </div>
        <div class="flex rounded-xl bg-slate-100 p-1">
            <button class="rounded-lg px-3 py-1.5 text-xs font-semibold bg-white text-slate-900 shadow-sm">Mes</button>
            {{-- Proximamente vista semanal --}}
        </div>
    </div>

    {{-- Week Days Grid --}}
    <div class="grid grid-cols-7 gap-2 mb-4">
        @foreach(['Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab', 'Dom'] as $day)
            <div class="text-center text-xs font-bold uppercase tracking-wider text-slate-400 py-2">{{ $day }}</div>
        @endforeach
    </div>

    {{-- Calendar Grid Container --}}
    <div id="calendar-grid" class="grid grid-cols-7 gap-2">
        {{-- Will be populated by JS --}}
    </div>
</section>
