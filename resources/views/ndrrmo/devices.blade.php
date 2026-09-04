@extends('layouts.ndrrmo')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-brand-dark text-2xl font-bold">Device Management</h1>
        <p class="text-brand-text text-sm mt-1">Register, monitor, and manage ESP32 panic buttons across the campus.</p>
    </div>
    <button onclick="document.getElementById('addDeviceModal').classList.remove('hidden')" class="bg-brand-red hover:bg-red-600 text-white px-4 py-2 rounded-lg font-medium transition-colors flex items-center shadow-[0_0_15px_rgba(239,68,68,0.3)]">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        Register Device
    </button>
</div>

@if(session('success'))
<div class="mb-6 bg-brand-green/20 border border-brand-green/50 text-brand-green px-4 py-3 rounded-lg flex items-center">
    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
    {{ session('success') }}
</div>
@endif

@if($errors->any())
<div class="mb-6 bg-brand-red/20 border border-brand-red/50 text-brand-red px-4 py-3 rounded-lg">
    <ul class="list-disc list-inside">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-xs">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-[10px] uppercase tracking-wider text-slate-500 font-extrabold">
                    <th class="p-4">Device Code</th>
                    <th class="p-4">Location</th>
                    <th class="p-4">Coordinates</th>
                    <th class="p-4">Status</th>
                    <th class="p-4">Last Seen</th>
                    <th class="p-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="text-xs divide-y divide-slate-100">
                @forelse($devices as $device)
                <tr class="hover:bg-slate-50/80 transition-colors">
                    <td class="p-4">
                        <div class="font-extrabold text-slate-900 flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-red-600 animate-pulse"></span>
                            <span>{{ $device->device_code }}</span>
                        </div>
                    </td>
                    <td class="p-4 text-slate-700">
                        <div class="font-bold text-slate-900">{{ $device->building }}</div>
                        <div class="text-[11px] text-slate-500">{{ $device->floor }}, {{ $device->room }}</div>
                    </td>
                    <td class="p-4 text-slate-600 font-mono text-[11px]">
                        {{ $device->latitude ?? 'N/A' }}, <br>{{ $device->longitude ?? 'N/A' }}
                    </td>
                    <td class="p-4">
                        <div class="flex flex-col gap-1.5">
                            @if($device->is_online)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-black tracking-wider uppercase bg-emerald-50 text-emerald-700 border border-emerald-200 w-fit"><span class="w-1.5 h-1.5 rounded-full bg-emerald-600 mr-1.5 animate-pulse"></span>Online</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-black tracking-wider uppercase bg-slate-50 text-slate-600 border border-slate-200 w-fit"><span class="w-1.5 h-1.5 rounded-full bg-slate-400 mr-1.5"></span>Offline</span>
                            @endif
                            <div class="text-[9px] font-bold uppercase tracking-wider text-slate-400">
                                @if($device->status === 'active')
                                    <span class="text-emerald-600">Active</span>
                                @elseif($device->status === 'maintenance')
                                    <span class="text-amber-600">Maintenance</span>
                                @else
                                    <span class="text-slate-500">Inactive</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="p-4 text-slate-600 text-xs">
                        @if($device->last_seen)
                            <div class="font-bold text-slate-800">{{ \Carbon\Carbon::parse($device->last_seen)->diffForHumans() }}</div>
                            <div class="text-[10px] text-slate-500 font-mono">{{ \Carbon\Carbon::parse($device->last_seen)->format('M d, Y h:i A') }}</div>
                        @else
                            <span class="text-slate-400 italic">Never connected</span>
                        @endif
                    </td>
                    <td class="p-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button onclick="editDevice({{ $device }})" class="px-3 py-1.5 text-xs font-bold text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Edit
                            </button>
                            <form action="{{ route('ndrrmo.devices.destroy', $device->id) }}" method="POST" class="inline" onsubmit="return confirmAction(event, 'Are you sure you want to delete device {{ $device->device_code }}? This action cannot be undone.', 'Delete Device', 'Delete', 'danger')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-1.5 text-xs font-bold text-red-700 bg-red-50 hover:bg-red-100 rounded-lg transition-colors flex items-center gap-1 cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="p-12 text-center">
                        <div class="flex flex-col items-center justify-center">
                            <div class="w-16 h-16 rounded-full bg-brand-hover flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-brand-text" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                            </div>
                            <h3 class="text-lg font-medium text-brand-dark mb-1">No Devices Registered</h3>
                            <p class="text-brand-text text-sm mb-4">You haven't added any ESP32 panic buttons to the system yet.</p>
                            <button onclick="document.getElementById('addDeviceModal').classList.remove('hidden')" class="bg-brand-red hover:bg-red-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">Register First Device</button>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Add Device Modal -->
<div id="addDeviceModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-brand-dark/50 backdrop-blur-sm transition-opacity">
    <div class="bg-brand-card border border-brand-border rounded-xl w-full max-w-md overflow-hidden shadow-2xl transform transition-all">
        <div class="p-4 border-b border-brand-border flex justify-between items-center bg-brand-bg">
            <h3 class="text-lg font-bold text-brand-dark flex items-center">
                <svg class="w-5 h-5 mr-2 text-brand-red" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                Register New Device
            </h3>
            <button onclick="document.getElementById('addDeviceModal').classList.add('hidden')" class="text-brand-text hover:text-brand-dark transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form action="{{ route('ndrrmo.devices.store') }}" method="POST" class="p-6">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-[10px] font-bold text-brand-text mb-1 uppercase tracking-wider">Device Code</label>
                    <input type="text" name="device_code" required class="w-full bg-brand-bg border border-brand-border rounded-lg px-4 py-2.5 text-brand-dark focus:outline-none focus:border-brand-red focus:ring-1 focus:ring-brand-red transition-all" placeholder="e.g. ENG-001">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-brand-text mb-1 uppercase tracking-wider">Building</label>
                    <input type="text" name="building" required class="w-full bg-brand-bg border border-brand-border rounded-lg px-4 py-2.5 text-brand-dark focus:outline-none focus:border-brand-red focus:ring-1 focus:ring-brand-red transition-all" placeholder="e.g. Engineering Building">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-brand-text mb-1 uppercase tracking-wider">Floor</label>
                        <input type="text" name="floor" required class="w-full bg-brand-bg border border-brand-border rounded-lg px-4 py-2.5 text-brand-dark focus:outline-none focus:border-brand-red focus:ring-1 focus:ring-brand-red transition-all" placeholder="e.g. 2nd Floor">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-brand-text mb-1 uppercase tracking-wider">Room</label>
                        <input type="text" name="room" required class="w-full bg-brand-bg border border-brand-border rounded-lg px-4 py-2.5 text-brand-dark focus:outline-none focus:border-brand-red focus:ring-1 focus:ring-brand-red transition-all" placeholder="e.g. Room 203">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-brand-text mb-1 uppercase tracking-wider">Latitude</label>
                        <input type="text" name="latitude" class="w-full bg-black/40 border border-brand-border rounded-lg px-4 py-2.5 text-brand-dark font-mono text-sm focus:outline-none focus:border-brand-red focus:ring-1 focus:ring-brand-red transition-all" placeholder="10.123456">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-brand-text mb-1 uppercase tracking-wider">Longitude</label>
                        <input type="text" name="longitude" class="w-full bg-black/40 border border-brand-border rounded-lg px-4 py-2.5 text-brand-dark font-mono text-sm focus:outline-none focus:border-brand-red focus:ring-1 focus:ring-brand-red transition-all" placeholder="124.123456">
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-brand-text mb-1 uppercase tracking-wider">Initial Status</label>
                    <select name="status" class="w-full bg-brand-bg border border-brand-border rounded-lg px-4 py-2.5 text-brand-dark focus:outline-none focus:border-brand-red focus:ring-1 focus:ring-brand-red transition-all appearance-none">
                        <option value="active">Active (Ready)</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="mt-8 flex justify-end">
                <button type="button" onclick="document.getElementById('addDeviceModal').classList.add('hidden')" class="px-4 py-2 text-brand-text hover:text-brand-dark mr-3 font-medium transition-colors">Cancel</button>
                <button type="submit" class="bg-brand-red hover:bg-red-600 text-white px-6 py-2 rounded-lg font-medium transition-colors shadow-[0_0_15px_rgba(239,68,68,0.3)]">Save Device</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Device Modal -->
<div id="editDeviceModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-brand-dark/50 backdrop-blur-sm transition-opacity">
    <div class="bg-brand-card border border-brand-border rounded-xl w-full max-w-md overflow-hidden shadow-2xl transform transition-all">
        <div class="p-4 border-b border-brand-border flex justify-between items-center bg-brand-bg">
            <h3 class="text-lg font-bold text-brand-dark flex items-center">
                <svg class="w-5 h-5 mr-2 text-brand-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                Edit Device
            </h3>
            <button onclick="document.getElementById('editDeviceModal').classList.add('hidden')" class="text-brand-text hover:text-brand-dark transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form id="editForm" method="POST" class="p-6">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="block text-[10px] font-bold text-brand-text mb-1 uppercase tracking-wider">Device Code</label>
                    <input type="text" name="device_code" id="edit_device_code" required class="w-full bg-brand-bg border border-brand-border rounded-lg px-4 py-2.5 text-brand-dark focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-brand-text mb-1 uppercase tracking-wider">Building</label>
                    <input type="text" name="building" id="edit_building" required class="w-full bg-brand-bg border border-brand-border rounded-lg px-4 py-2.5 text-brand-dark focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-brand-text mb-1 uppercase tracking-wider">Floor</label>
                        <input type="text" name="floor" id="edit_floor" required class="w-full bg-brand-bg border border-brand-border rounded-lg px-4 py-2.5 text-brand-dark focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-brand-text mb-1 uppercase tracking-wider">Room</label>
                        <input type="text" name="room" id="edit_room" required class="w-full bg-brand-bg border border-brand-border rounded-lg px-4 py-2.5 text-brand-dark focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-brand-text mb-1 uppercase tracking-wider">Latitude</label>
                        <input type="text" name="latitude" id="edit_latitude" class="w-full bg-black/40 border border-brand-border rounded-lg px-4 py-2.5 text-brand-dark font-mono text-sm focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-brand-text mb-1 uppercase tracking-wider">Longitude</label>
                        <input type="text" name="longitude" id="edit_longitude" class="w-full bg-black/40 border border-brand-border rounded-lg px-4 py-2.5 text-brand-dark font-mono text-sm focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all">
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-brand-text mb-1 uppercase tracking-wider">Status</label>
                    <select name="status" id="edit_status" class="w-full bg-brand-bg border border-brand-border rounded-lg px-4 py-2.5 text-brand-dark focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all appearance-none">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
            </div>
            <div class="mt-8 flex justify-end">
                <button type="button" onclick="document.getElementById('editDeviceModal').classList.add('hidden')" class="px-4 py-2 text-brand-text hover:text-brand-dark mr-3 font-medium transition-colors">Cancel</button>
                <button type="submit" class="bg-brand-blue hover:bg-blue-600 text-white px-6 py-2 rounded-lg font-medium transition-colors shadow-[0_0_15px_rgba(59,130,246,0.3)]">Update Details</button>
            </div>
        </form>
    </div>
</div>

<script>
    function editDevice(device) {
        document.getElementById('edit_device_code').value = device.device_code;
        document.getElementById('edit_building').value = device.building;
        document.getElementById('edit_floor').value = device.floor;
        document.getElementById('edit_room').value = device.room;
        document.getElementById('edit_latitude').value = device.latitude || '';
        document.getElementById('edit_longitude').value = device.longitude || '';
        document.getElementById('edit_status').value = device.status;
        
        document.getElementById('editForm').action = `/ndrrmo/devices/${device.id}`;
        document.getElementById('editDeviceModal').classList.remove('hidden');
    }

    // Close modals when clicking outside
    window.onclick = function(event) {
        if (event.target == document.getElementById('addDeviceModal')) {
            document.getElementById('addDeviceModal').classList.add('hidden');
        }
        if (event.target == document.getElementById('editDeviceModal')) {
            document.getElementById('editDeviceModal').classList.add('hidden');
        }
    }
</script>
@endsection
