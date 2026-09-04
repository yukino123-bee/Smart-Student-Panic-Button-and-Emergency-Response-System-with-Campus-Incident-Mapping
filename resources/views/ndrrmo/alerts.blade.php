@extends('layouts.ndrrmo')

@section('content')

{{-- Success flash --}}
@if(session('success'))
<div id="flash-msg" class="mb-4 flex items-center gap-3 px-4 py-3 bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 rounded-xl text-sm font-medium">
    <svg class="w-4 h-4 shrink-0 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
    {{ session('success') }}
</div>
<script>setTimeout(() => document.getElementById('flash-msg')?.remove(), 4000)</script>
@endif

<form id="bulk-form" method="POST" action="{{ route('ndrrmo.alerts.bulk-delete') }}">
@csrf

{{-- Header row with direct Select All & Delete Selected controls --}}
<div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
    <div>
        <h2 class="text-xl font-bold text-slate-800 mb-1">Live Alerts</h2>
        <p class="text-xs text-slate-500">Monitor active emergencies requiring immediate response.</p>
    </div>
    
    <div class="flex flex-wrap items-center gap-3 text-xs">
        {{-- Counter Badges --}}
        <span class="px-3 py-1.5 bg-red-50 border border-red-200 text-red-700 rounded-lg font-semibold flex items-center">
            <span class="w-2 h-2 rounded-full bg-red-600 animate-pulse mr-2"></span> {{ $alerts->where('status', 'Pending')->count() }} Pending
        </span>
        <span class="px-3 py-1.5 bg-blue-50 border border-blue-200 text-blue-700 rounded-lg font-semibold flex items-center">
            <span class="w-2 h-2 rounded-full bg-blue-600 mr-2"></span> {{ $alerts->where('status', 'Responding')->count() }} Responding
        </span>

        @if(!$alerts->isEmpty())
        <div class="h-6 w-px bg-slate-200 hidden sm:block"></div>

        {{-- Select All Checkbox --}}
        <label class="flex items-center gap-2 cursor-pointer select-none bg-slate-50 hover:bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-300 font-semibold text-slate-700 transition-colors">
            <input id="header-select-all" type="checkbox"
                   class="w-4 h-4 rounded border-slate-300 text-red-600 focus:ring-red-500 accent-red-600 cursor-pointer">
            <span>Select All</span>
        </label>

        {{-- Delete Selected Button --}}
        <button type="submit" id="header-delete-btn"
                class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-red-600 hover:bg-red-700 text-white font-bold transition-all disabled:opacity-40 disabled:cursor-not-allowed shadow-sm"
                disabled>
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            <span>Delete Selected</span>
            <span id="header-selected-count" class="hidden ml-1 px-1.5 py-0.5 bg-white/20 rounded-full text-[10px]">0</span>
        </button>
        @endif
    </div>
</div>

@if($alerts->isEmpty())
<div class="bg-white border border-slate-200 rounded-2xl p-12 text-center flex flex-col items-center justify-center shadow-sm">
    <div class="w-16 h-16 bg-slate-100 border border-slate-200 rounded-full flex items-center justify-center mb-4">
        <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    </div>
    <h3 class="text-slate-800 font-semibold text-lg mb-1">All Clear</h3>
    <p class="text-slate-500 text-sm">There are no active emergency alerts at this time.</p>
</div>
@else
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @foreach($alerts as $alert)
        @php
            // Clear, high-contrast header styling by emergency category / level
            if ($alert->emergency_type === 'Critical Emergency') {
                $headerBg = 'bg-red-600 text-white';
                $headerSubtext = 'text-red-100';
                $iconBox = 'bg-white/20 text-white';
                $statusBadge = 'bg-white/20 text-white border border-white/30';
                $dotColor = 'bg-white';
                $typeBadge = 'bg-red-100 text-red-800 border border-red-200';
            } elseif ($alert->emergency_type === 'Medical Emergency') {
                $headerBg = 'bg-orange-500 text-white';
                $headerSubtext = 'text-orange-100';
                $iconBox = 'bg-white/20 text-white';
                $statusBadge = 'bg-white/20 text-white border border-white/30';
                $dotColor = 'bg-white';
                $typeBadge = 'bg-orange-100 text-orange-800 border border-orange-200';
            } elseif ($alert->emergency_type === 'Public Safety Emergency') {
                $headerBg = 'bg-amber-500 text-white';
                $headerSubtext = 'text-amber-100';
                $iconBox = 'bg-white/20 text-white';
                $statusBadge = 'bg-white/20 text-white border border-white/30';
                $dotColor = 'bg-white';
                $typeBadge = 'bg-amber-100 text-amber-900 border border-amber-200';
            } else {
                $headerBg = 'bg-slate-700 text-white';
                $headerSubtext = 'text-slate-200';
                $iconBox = 'bg-white/20 text-white';
                $statusBadge = 'bg-white/20 text-white border border-white/30';
                $dotColor = 'bg-white';
                $typeBadge = 'bg-slate-100 text-slate-800 border border-slate-200';
            }
        @endphp

        <div class="alert-card bg-white border border-slate-300 rounded-xl flex flex-col overflow-hidden shadow-sm hover:shadow-md transition-all duration-200 relative group"
             data-id="{{ $alert->id }}">

            {{-- Checkbox --}}
            <label class="absolute top-3.5 right-3.5 z-10 cursor-pointer p-1 rounded-md bg-black/20 hover:bg-black/30 transition-colors">
                <input type="checkbox" name="ids[]" value="{{ $alert->id }}"
                       class="alert-checkbox w-4 h-4 rounded border-white text-red-600 focus:ring-red-500 accent-red-600 cursor-pointer"
                       onchange="onCheckboxChange()">
            </label>

            {{-- Bold Colored Card Header by Category --}}
            <div class="p-4 {{ $headerBg }} flex items-center justify-between pr-12">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg {{ $iconBox }} flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 animate-pulse" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2zm-2 1H8v-6c0-2.48 1.51-4.5 4-4.5s4 2.02 4 4.5v6z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-[10px] {{ $headerSubtext }} font-bold uppercase tracking-wider mb-0.5">LOCATION</div>
                        <h3 class="font-bold text-sm leading-tight text-white">{{ $alert->device->building ?? 'Unknown' }}</h3>
                    </div>
                </div>
                <div class="text-right">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $statusBadge }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $dotColor }} animate-pulse"></span>
                        {{ $alert->status }}
                    </span>
                </div>
            </div>

            {{-- Card Body --}}
            <div class="p-4 flex-1 flex flex-col justify-between space-y-4">
                <div>
                    <div class="text-[10px] text-slate-500 font-bold uppercase tracking-wider mb-1.5">INCIDENT TYPE</div>
                    <div class="inline-block px-3 py-1 rounded-md text-xs font-bold uppercase tracking-wide {{ $typeBadge }}">
                        {{ $alert->emergency_type }}
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-3 border-t border-slate-200">
                    <div>
                        <div class="text-[10px] text-slate-500 font-bold uppercase tracking-wider mb-0.5">TIME REPORTED</div>
                        <div class="text-xs text-slate-900 font-semibold">{{ $alert->created_at->format('M d, Y h:i A') }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-slate-500 font-bold uppercase tracking-wider mb-0.5">DEVICE ID</div>
                        <div class="text-xs text-slate-900 font-semibold font-mono">{{ $alert->device->device_code ?? 'N/A' }}</div>
                    </div>
                </div>

                @if($alert->remarks)
                <div class="pt-2 border-t border-slate-200 text-xs">
                    <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block mb-0.5">REMARKS</span>
                    <p class="text-slate-700 italic bg-slate-50 p-2 rounded-md border border-slate-200">{{ $alert->remarks }}</p>
                </div>
                @endif
            </div>

            {{-- Dynamic Action Buttons --}}
            <div class="p-3 bg-slate-50 border-t border-slate-200 flex flex-col gap-2">
                @if($alert->emergency_type === 'Medical Emergency' && !$alert->notifications->where('recipient', 'Clinic')->count())
                    <button type="button" onclick="notifyClinic({{ $alert->id }})" class="w-full py-2 px-3 text-xs font-bold text-orange-700 bg-orange-100 border border-orange-300 hover:bg-orange-200 rounded-lg transition-all flex items-center justify-center gap-1.5 shadow-sm active:scale-95">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        <span>Notify Clinic for Assistance</span>
                    </button>
                @endif
                @if(in_array(strtolower($alert->status), ['pending']))
                    <button type="button" onclick="acknowledgeSingleAlert({{ $alert->id }})" class="w-full py-2 px-3 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-all flex items-center justify-center gap-1.5 shadow-sm active:scale-95">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Acknowledge Alert</span>
                    </button>
                @elseif(in_array(strtolower($alert->status), ['acknowledged']))
                    <button type="button" onclick="dispatchSingleAlert({{ $alert->id }})" class="w-full py-2 px-3 text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-lg transition-all flex items-center justify-center gap-1.5 shadow-sm active:scale-95">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span>Dispatch Responders</span>
                    </button>
                @else
                    <button type="button" onclick="resolveSingleAlert({{ $alert->id }})" class="w-full py-2 px-3 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-all flex items-center justify-center gap-1.5 shadow-sm active:scale-95">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Mark Resolved & Record Log</span>
                    </button>
                @endif
            </div>
        </div>
    @endforeach
</div>
@endif

</form>

<script>
const headerSelectAll = document.getElementById('header-select-all');
const headerDeleteBtn = document.getElementById('header-delete-btn');
const headerSelCount  = document.getElementById('header-selected-count');

function getChecked() {
    return [...document.querySelectorAll('.alert-checkbox:checked')];
}
function getAll() {
    return [...document.querySelectorAll('.alert-checkbox')];
}

function onCheckboxChange() {
    const checked = getChecked();
    const count   = checked.length;
    const total   = getAll().length;

    // Header delete button state
    if (headerDeleteBtn) {
        headerDeleteBtn.disabled = count === 0;
        if (count > 0) {
            headerSelCount.textContent = count;
            headerSelCount.classList.remove('hidden');
        } else {
            headerSelCount.classList.add('hidden');
        }
    }

    // Header select-all checkbox state
    if (headerSelectAll) {
        headerSelectAll.indeterminate = count > 0 && count < total;
        headerSelectAll.checked       = count === total && total > 0;
    }

    // Highlight selected card borders
    getAll().forEach(cb => {
        const card = cb.closest('.alert-card');
        if (cb.checked) {
            card.classList.add('ring-4', 'ring-red-500');
        } else {
            card.classList.remove('ring-4', 'ring-red-500');
        }
    });
}

// Select-all toggle in header
if (headerSelectAll) {
    headerSelectAll.addEventListener('change', () => {
        getAll().forEach(cb => { cb.checked = headerSelectAll.checked; });
        onCheckboxChange();
    });
}

function deselectAll() {
    getAll().forEach(cb => { cb.checked = false; });
    onCheckboxChange();
}

function selectAllAlerts() {
    getAll().forEach(cb => { cb.checked = true; });
    onCheckboxChange();
}

// Ctrl+A shortcut — select all cards (skip if typing in an input)
document.addEventListener('keydown', function(e) {
    const tag = document.activeElement?.tagName?.toLowerCase();
    if (tag === 'input' || tag === 'textarea' || tag === 'select') return;
    if ((e.ctrlKey || e.metaKey) && e.key === 'a') {
        const all = getAll();
        if (all.length === 0) return;
        e.preventDefault();
        const allChecked = all.every(cb => cb.checked);
        if (allChecked) {
            deselectAll();
        } else {
            selectAllAlerts();
            showShortcutToast();
        }
    }
    // Escape to deselect all
    if (e.key === 'Escape') {
        deselectAll();
    }
});

// Brief toast when shortcut fires
function showShortcutToast() {
    if (document.getElementById('shortcut-toast')) return;
    const t = document.createElement('div');
    t.id = 'shortcut-toast';
    t.className = 'fixed bottom-6 right-6 z-50 flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white border border-slate-300 shadow-xl text-xs text-slate-800 font-semibold animate-pulse';
    t.innerHTML = '<kbd class="px-1.5 py-0.5 rounded border border-slate-300 bg-slate-100 font-mono text-[10px]">Ctrl+A</kbd> All alerts selected &mdash; press <kbd class="px-1.5 py-0.5 rounded border border-slate-300 bg-slate-100 font-mono text-[10px]">Esc</kbd> to deselect';
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3000);
}

// Confirm before submitting bulk delete
document.getElementById('bulk-form').addEventListener('submit', function(e) {
    const count = getChecked().length;
    if (count === 0) { e.preventDefault(); return; }
    e.preventDefault();
    const form = this;
    window.showConfirmDialog({
        title: 'Delete Selected Alerts',
        message: `Are you sure you want to delete ${count} selected alert(s)? This action cannot be undone.`,
        confirmText: 'Delete',
        type: 'danger'
    }).then(confirmed => {
        if (confirmed) {
            form.submit();
        }
    });
});

function acknowledgeSingleAlert(id) {
    window.showConfirmDialog({
        title: 'Acknowledge Alert',
        message: 'Are you sure you want to acknowledge this emergency alert and stop the alarm?',
        confirmText: 'Acknowledge',
        type: 'warning'
    }).then(confirmed => {
        if (!confirmed) return;

        if (window.stopEmergencySirenAudio) window.stopEmergencySirenAudio();
        if (window.stopVoiceSpeech) window.stopVoiceSpeech();

        fetch(`/ndrrmo/incidents/${id}/acknowledge`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            window.location.reload();
        })
        .catch(err => window.location.reload());
    });
}

function dispatchSingleAlert(id) {
    window.showConfirmDialog({
        title: 'Dispatch Responder Team',
        message: 'Are you sure you want to dispatch a responder team to this incident location?',
        confirmText: 'Dispatch Team',
        type: 'info'
    }).then(confirmed => {
        if (!confirmed) return;

        fetch(`/ndrrmo/incidents/${id}/dispatch`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            window.location.reload();
        })
        .catch(err => window.location.reload());
    });
}

function resolveSingleAlert(id) {
    window.showConfirmDialog({
        title: 'Resolve Emergency Incident',
        message: 'Are you sure you want to mark this incident as RESOLVED?',
        confirmText: 'Resolve Incident',
        type: 'danger'
    }).then(confirmed => {
        if (!confirmed) return;

        fetch(`/ndrrmo/incidents/${id}/resolve`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            window.location.reload();
        })
        .catch(err => window.location.reload());
    });
}

function notifyClinic(id) {
    window.showConfirmDialog({
        title: 'Notify Clinic',
        message: 'Are you sure you want to notify the Clinic to request medical assistance for this incident?',
        confirmText: 'Notify Clinic',
        type: 'warning'
    }).then(confirmed => {
        if (!confirmed) return;

        fetch(`/ndrrmo/incidents/${id}/notify-clinic`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            window.location.reload();
        })
        .catch(err => window.location.reload());
    });
}
</script>
@endsection
