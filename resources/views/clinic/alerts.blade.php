@extends('layouts.clinic')

@section('content')

{{-- Success Flash Message --}}
@if(session('success'))
<div id="flash-msg" class="mb-4 flex items-center gap-3 px-4 py-3 bg-emerald-500/10 border border-emerald-500/30 text-emerald-800 rounded-2xl text-xs font-extrabold animate-fade-in shadow-sm">
    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <span>{{ session('success') }}</span>
</div>
<script>setTimeout(() => document.getElementById('flash-msg')?.remove(), 4000)</script>
@endif

{{-- Bulk Delete Form --}}
<form id="bulk-form" method="POST" action="{{ route('clinic.alerts.bulk-delete') }}" class="hidden">
    @csrf
    <div id="bulk-inputs"></div>
</form>

{{-- Header Row --}}
<div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
    <div>
        <div class="flex items-center gap-3 mb-1">
            <div class="w-8 h-8 rounded-xl bg-red-100 border border-red-300 flex items-center justify-center text-red-600 font-black text-sm">
                ⚠
            </div>
            <h2 class="text-xl font-black text-slate-900 tracking-tight">Critical & Medical Alerts</h2>
        </div>
        <p class="text-xs font-bold text-slate-500">Real-time emergency panic alerts requiring clinic medical response.</p>
    </div>

    <div class="flex flex-wrap items-center gap-3 text-xs">
        <span class="px-3.5 py-2 bg-red-50 border border-red-200 text-red-700 rounded-xl font-extrabold flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-red-600 animate-pulse"></span>
            <span>{{ $alerts->where('status', 'Pending')->count() }} Pending</span>
        </span>
        <span class="px-3.5 py-2 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl font-extrabold flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
            <span>{{ $alerts->whereIn('status', ['Acknowledged', 'Responding'])->count() }} In Progress</span>
        </span>

        @if(!$alerts->isEmpty())
        <div class="h-6 w-px bg-slate-200 hidden sm:block"></div>

        {{-- Select All --}}
        <label class="flex items-center gap-2 cursor-pointer select-none bg-slate-50 hover:bg-slate-100 px-3.5 py-2 rounded-xl border border-slate-300 font-extrabold text-slate-700 transition-colors">
            <input id="header-select-all" type="checkbox"
                   class="w-4 h-4 rounded border-slate-300 text-red-600 focus:ring-red-500 accent-red-600 cursor-pointer">
            <span>Select All</span>
        </label>

        {{-- Delete Selected Button --}}
        <button type="button" id="header-delete-btn"
                class="flex items-center gap-1.5 px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-extrabold transition-all disabled:opacity-40 disabled:cursor-not-allowed shadow-xs cursor-pointer"
                disabled>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            <span>Delete Selected</span>
            <span id="header-selected-count" class="hidden ml-1 px-2 py-0.5 bg-white/25 rounded-full text-[10px] font-black">0</span>
        </button>
        @endif
    </div>
</div>

@if($alerts->isEmpty())
<div class="bg-white border border-slate-200 rounded-2xl p-12 text-center flex flex-col items-center justify-center shadow-xs">
    <div class="w-16 h-16 bg-emerald-50 border border-emerald-200 rounded-full flex items-center justify-center mb-4">
        <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    </div>
    <h3 class="text-slate-900 font-black text-lg mb-1">No Active Critical Alerts</h3>
    <p class="text-slate-500 text-xs font-bold">All clinic emergency channels are clear. Monitoring network for panic signals...</p>
</div>
@else
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @foreach($alerts as $alert)
        @php
            $isCritical = str_contains(strtolower($alert->emergency_type), 'critical');
            $headerBg = $isCritical ? 'bg-red-600 text-white' : 'bg-amber-500 text-white';
        @endphp
        <div class="bg-white border border-slate-200 rounded-2xl shadow-xs hover:shadow-md transition-all overflow-hidden flex flex-col justify-between relative group">
            <div>
                <!-- Card Header -->
                <div class="{{ $headerBg }} px-5 py-3.5 flex items-center justify-between pr-12">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-white animate-ping"></span>
                        <span class="font-black text-xs uppercase tracking-wider">{{ $alert->emergency_type }}</span>
                    </div>
                    <span class="text-[11px] font-bold bg-black/20 px-2.5 py-0.5 rounded-full">
                        {{ $alert->created_at ? $alert->created_at->format('h:i A') : '' }}
                    </span>
                </div>

                <!-- Checkbox in Top Corner -->
                <label class="absolute top-3 right-3 z-10 cursor-pointer p-1 rounded-lg bg-black/30 hover:bg-black/40 transition-colors">
                    <input type="checkbox" name="ids[]" value="{{ $alert->id }}"
                           class="alert-checkbox w-4 h-4 rounded border-white text-red-600 focus:ring-red-500 accent-red-600 cursor-pointer">
                </label>

                <!-- Card Content -->
                <div class="p-5 space-y-4">
                    <div>
                        <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">LOCATION</div>
                        <div class="text-base font-black text-slate-900">{{ $alert->device->building ?? 'Campus Location' }}</div>
                        <div class="text-xs font-bold text-slate-500 mt-0.5">
                            {{ $alert->device->floor ?? 'Floor N/A' }} • {{ $alert->device->room ?? 'Room N/A' }}
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                        <div>
                            <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-0.5">DEVICE CODE</div>
                            <div class="text-xs font-mono font-black text-slate-800">{{ $alert->device->device_code ?? 'N/A' }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-0.5">STATUS</div>
                            <span class="inline-flex items-center gap-1 text-xs font-black text-slate-800">
                                <span class="w-2 h-2 rounded-full {{ strtolower($alert->status) === 'pending' ? 'bg-red-600 animate-pulse' : 'bg-emerald-500' }}"></span>
                                {{ ucfirst($alert->status) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Actions -->
            <div class="p-4 bg-slate-50 border-t border-slate-100 flex gap-2 items-center">
                {{-- Action Buttons --}}
                <div class="flex items-center gap-2 pt-2 border-t border-slate-100">
                    @php
                        $clinicAck = $alert->notifications->where('recipient', 'Clinic')->where('status', 'Acknowledged')->count();
                    @endphp
                    @if(!$clinicAck)
                        <form id="ack-form-{{ $alert->id }}" method="POST" action="{{ route('clinic.incidents.acknowledge', $alert->id) }}" class="flex-1" onsubmit="return confirmAction(event, 'Are you sure you want to acknowledge this alert?', 'Acknowledge Alert', 'Acknowledge', 'warning')">
                            @csrf
                            <button type="submit" class="w-full py-2.5 px-3 rounded-xl bg-amber-500 hover:bg-amber-600 active:scale-95 text-white font-extrabold text-xs transition-all shadow-xs flex items-center justify-center gap-1.5 cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Acknowledge
                            </button>
                        </form>
                    @endif
                    <form id="resolve-form-{{ $alert->id }}" method="POST" action="{{ route('clinic.incidents.resolve', $alert->id) }}" class="flex-1" onsubmit="return confirmAction(event, 'Are you sure you want to mark this emergency as RESOLVED?', 'Resolve Incident', 'Mark Resolved', 'danger')">
                        @csrf
                        <button type="submit" class="w-full py-2.5 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-extrabold text-xs transition-all shadow-xs flex items-center justify-center gap-1.5 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Mark Resolved
                        </button>
                    </form>
                    
                    {{-- Single Delete Button --}}
                    <form id="delete-form-{{ $alert->id }}" method="POST" action="{{ route('clinic.alerts.destroy', $alert->id) }}" class="shrink-0" onsubmit="return confirmAction(event, 'Are you sure you want to delete this alert?', 'Delete Alert', 'Delete', 'danger')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="p-2.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 hover:text-rose-700 border border-rose-200 transition-colors cursor-pointer" title="Delete Alert">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAll = document.getElementById('header-select-all');
        const checkboxes = document.querySelectorAll('.alert-checkbox');
        const deleteBtn = document.getElementById('header-delete-btn');
        const countSpan = document.getElementById('header-selected-count');
        const bulkForm = document.getElementById('bulk-form');

        if (deleteBtn) {
            deleteBtn.addEventListener('click', function(e) {
                const checked = document.querySelectorAll('.alert-checkbox:checked');
                if (checked.length === 0) return;
                
                window.showConfirmDialog({
                    title: 'Delete Selected Alerts',
                    message: `Are you sure you want to delete ${checked.length} selected alert(s)? This action cannot be undone.`,
                    confirmText: 'Delete All',
                    type: 'danger'
                }).then(confirmed => {
                    if (confirmed) {
                        const bulkInputs = document.getElementById('bulk-inputs');
                        if (bulkInputs) {
                            bulkInputs.innerHTML = '';
                            checked.forEach(cb => {
                                const input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = 'ids[]';
                                input.value = cb.value;
                                bulkInputs.appendChild(input);
                            });
                            document.getElementById('bulk-form').submit();
                        }
                    }
                });
            });
        }

        function updateState() {
            const checked = document.querySelectorAll('.alert-checkbox:checked');
            const count = checked.length;

            if (deleteBtn) deleteBtn.disabled = count === 0;

            if (countSpan) {
                if (count > 0) {
                    countSpan.textContent = count;
                    countSpan.classList.remove('hidden');
                } else {
                    countSpan.classList.add('hidden');
                }
            }

            if (selectAll) {
                selectAll.checked = checkboxes.length > 0 && count === checkboxes.length;
            }
        }

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(cb => cb.checked = selectAll.checked);
                updateState();
            });
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', updateState);
        });
    });
</script>

@endsection
