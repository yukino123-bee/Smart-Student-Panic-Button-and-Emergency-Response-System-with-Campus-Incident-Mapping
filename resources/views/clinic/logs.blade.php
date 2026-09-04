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

{{-- Header Row --}}
<div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-5 rounded-3xl border border-slate-200 shadow-sm">
    <div>
        <div class="flex items-center gap-3 mb-1">
            <div class="w-8 h-8 rounded-xl bg-slate-100 border border-slate-300 flex items-center justify-center text-slate-700 font-black text-sm">
                📋
            </div>
            <h2 class="text-xl font-black text-slate-900 tracking-tight">Clinic Incident Logs</h2>
        </div>
        <p class="text-xs font-bold text-slate-500">Full audit log of campus emergency medical incidents and treatment statuses.</p>
    </div>

    <div class="flex items-center gap-3">
        <a href="{{ route('clinic.reports.export-excel') }}" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-extrabold text-xs rounded-xl shadow-md shadow-emerald-200 transition-all flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            Export Excel
        </a>
    </div>
</div>

{{-- Logs Table Container --}}
<div class="bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-[11px] text-slate-600 font-black uppercase tracking-wider">
                    <th class="px-6 py-4">Time Reported</th>
                    <th class="px-6 py-4">Emergency Category</th>
                    <th class="px-6 py-4">Building Location</th>
                    <th class="px-6 py-4">Device Code</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4 text-center">Action</th>
                </tr>
            </thead>
            <tbody class="text-xs divide-y divide-slate-100 font-medium text-slate-800">
                @forelse($logs as $log)
                <tr class="hover:bg-slate-50/80 transition-colors">
                    <td class="px-6 py-4 font-bold text-slate-700">
                        {{ $log->created_at ? $log->created_at->format('M d, Y · h:i A') : 'N/A' }}
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider {{ str_contains(strtolower($log->emergency_type), 'critical') ? 'bg-red-100 text-red-800 border border-red-200' : 'bg-blue-100 text-blue-800 border border-blue-200' }}">
                            {{ $log->emergency_type }}
                        </span>
                    </td>
                    <td class="px-6 py-4 font-bold text-slate-900">
                        {{ $log->device->building ?? 'Campus Location' }}
                        <span class="text-[11px] text-slate-400 font-normal block">{{ $log->device->room ?? '' }}</span>
                    </td>
                    <td class="px-6 py-4 font-mono font-bold text-slate-800">
                        {{ $log->device->device_code ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4">
                        @php
                            $st = strtolower($log->status);
                        @endphp
                        @if($st === 'resolved')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                Resolved / Treated
                            </span>
                        @elseif($st === 'pending')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black bg-red-100 text-red-800 border border-red-200 animate-pulse">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-600"></span>
                                Pending
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-100 text-amber-800 border border-amber-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                {{ ucfirst($log->status) }}
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        @if($st !== 'resolved')
                        <form method="POST" action="{{ route('clinic.incidents.resolve', $log->id) }}">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-[11px] shadow-sm transition-all cursor-pointer">
                                Mark Resolved
                            </button>
                        </form>
                        @else
                        <span class="text-[11px] text-slate-400 font-bold">Closed</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-slate-500 font-bold">
                        No incident log records found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
    <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
        {{ $logs->links() }}
    </div>
    @endif
</div>

@endsection
