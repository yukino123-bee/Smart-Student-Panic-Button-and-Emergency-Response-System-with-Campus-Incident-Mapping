<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Incident;
use App\Models\Notification;
use Illuminate\Http\Request;

class NdrrmoController extends Controller
{
    public function dashboard()
    {
        $activeIncidents = Incident::with('device')->active()->latest('reported_at')->get();
        $totalIncidents = Incident::count();
        $resolvedIncidents = Incident::resolved()->count();
        $devicesList = Device::all();
        $devicesCount = $devicesList->count();
        $onlineDevicesCount = $devicesList->filter->is_online->count();
        $recentLogs = Incident::with('device')->latest('reported_at')->take(5)->get();
        $todayIncidents = Incident::whereDate('reported_at', today());

        $stats = [
            'Critical' => (clone $todayIncidents)->where('emergency_type', Incident::TYPE_CRITICAL)->count(),
            'Medical' => (clone $todayIncidents)->where('emergency_type', Incident::TYPE_MEDICAL)->count(),
            'Public Safety' => (clone $todayIncidents)->where('emergency_type', Incident::TYPE_PUBLIC_SAFETY)->count(),
            'Pending' => (clone $todayIncidents)->where('status', 'Pending')->count(),
            'Acknowledged' => (clone $todayIncidents)->where('status', 'Acknowledged')->count(),
            'Responding' => (clone $todayIncidents)->where('status', 'Responding')->count(),
            'Resolved' => (clone $todayIncidents)->where('status', 'Resolved')->count(),
        ];

        return view('ndrrmo', compact('activeIncidents', 'totalIncidents', 'resolvedIncidents', 'devicesCount', 'onlineDevicesCount', 'devicesList', 'recentLogs', 'stats'));
    }

    public function alerts()
    {
        $alerts = Incident::with(['device', 'notifications'])
            ->active()
            ->latest('reported_at')
            ->get();

        return view('ndrrmo.alerts', compact('alerts'));
    }

    public function logs()
    {
        $logs = Incident::with('device')->latest('reported_at')->paginate(15);

        return view('ndrrmo.logs', compact('logs'));
    }

    public function map()
    {
        $devices = Device::all();
        $activeIncidents = Incident::with('device')
            ->active()
            ->get();

        return view('ndrrmo.map', compact('devices', 'activeIncidents'));
    }

    public function devices()
    {
        $devices = Device::latest()->get();

        return view('ndrrmo.devices', compact('devices'));
    }

    public function storeDevice(Request $request)
    {
        $validated = $request->validate([
            'device_code' => 'required|string|unique:devices,device_code',
            'building' => 'required|string',
            'floor' => 'required|string',
            'room' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'status' => 'required|string|in:active,inactive,maintenance',
        ]);

        Device::create($validated);

        return redirect()->route('ndrrmo.devices')->with('success', 'Device registered successfully.');
    }

    public function updateDevice(Request $request, Device $device)
    {
        $validated = $request->validate([
            'device_code' => 'required|string|unique:devices,device_code,'.$device->id,
            'building' => 'required|string',
            'floor' => 'required|string',
            'room' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'status' => 'required|string|in:active,inactive,maintenance',
        ]);

        $device->update($validated);

        return redirect()->route('ndrrmo.devices')->with('success', 'Device updated successfully.');
    }

    public function destroyDevice(Device $device)
    {
        $device->delete();

        return redirect()->route('ndrrmo.devices')->with('success', 'Device deleted successfully.');
    }

    public function bulkDeleteAlerts(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:incidents,id',
        ]);

        $ids = $validated['ids'];

        // Delete associated notifications first, then the incidents
        Notification::whereIn('incident_id', $ids)->delete();
        Incident::whereIn('id', $ids)->delete();

        return redirect()->route('ndrrmo.alerts')
            ->with('success', count($ids).' alert(s) deleted successfully.');
    }

    public function acknowledgeIncident(Incident $incident)
    {
        $incident->load('device');
        abort_unless($incident->status === 'Pending', 409, 'Only pending incidents can be acknowledged.');
        $incident->update(['status' => 'Acknowledged']);

        Notification::create([
            'incident_id' => $incident->id,
            'recipient' => 'DRRMO',
            'channel' => 'Dashboard',
            'status' => 'Acknowledged',
            'sent_at' => now(),
        ]);

        if (request()->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Emergency alert acknowledged.',
                'incident' => $incident,
            ]);
        }

        return redirect()->back()->with('success', 'Incident acknowledged.');
    }

    public function notifyClinic(Incident $incident)
    {
        abort_unless($incident->emergency_type === Incident::TYPE_MEDICAL, 404);

        Notification::firstOrCreate([
            'incident_id' => $incident->id,
            'recipient' => 'Clinic',
        ], [
            'channel' => 'Dashboard',
            'status' => 'Delivered',
            'sent_at' => now(),
        ]);

        broadcast(new \App\Events\EmergencyReported($incident))->toOthers();

        if (request()->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Clinic notified successfully.'
            ]);
        }

        return redirect()->back()->with('success', 'Clinic notified successfully.');
    }

    public function dispatchIncident(Incident $incident)
    {
        abort_unless(in_array($incident->status, ['Pending', 'Acknowledged'], true), 409, 'Incident cannot be dispatched from its current status.');
        $incident->update([
            'status' => 'Responding',
        ]);

        Notification::create([
            'incident_id' => $incident->id,
            'recipient' => 'DRRMO',
            'channel' => 'Dashboard',
            'status' => 'Dispatched',
            'sent_at' => now(),
        ]);

        if (request()->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Responders dispatched.',
                'incident' => $incident,
            ]);
        }

        return redirect()->back()->with('success', 'Responders dispatched.');
    }

    public function resolveIncident(Incident $incident)
    {
        abort_if($incident->status === 'Resolved', 409, 'Incident is already resolved.');
        $incident->update([
            'status' => 'Resolved',
            'resolved_at' => now(),
        ]);

        Notification::create([
            'incident_id' => $incident->id,
            'recipient' => 'DRRMO',
            'channel' => 'Dashboard',
            'status' => 'Resolved',
            'sent_at' => now(),
        ]);

        if (request()->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Incident marked resolved and recorded.',
                'incident' => $incident,
            ]);
        }

        return redirect()->back()->with('success', 'Incident marked resolved and recorded.');
    }

    public function sms()
    {
        $smsLogs = Notification::where('channel', 'SMS Backup')
            ->with('incident.device')
            ->latest()
            ->paginate(15);

        return view('ndrrmo.sms', compact('smsLogs'));
    }

    public function reports()
    {
        $stats = Incident::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $typeStats = Incident::selectRaw('emergency_type, count(*) as count')
            ->groupBy('emergency_type')
            ->pluck('count', 'emergency_type');

        $totalIncidents = Incident::count();
        $averageResolutionSeconds = Incident::resolved()
            ->whereNotNull('resolved_at')
            ->get(['reported_at', 'resolved_at'])
            ->avg(fn (Incident $incident) => $incident->reported_at->diffInSeconds($incident->resolved_at));

        return view('ndrrmo.reports', compact('stats', 'typeStats', 'totalIncidents', 'averageResolutionSeconds'));
    }

    public function statsJson()
    {
        $devices = Device::all();
        $latestPending = Incident::with('device')
            ->where('status', 'Pending')
            ->latest('reported_at')
            ->first();

        return response()->json([
            'active_alerts' => Incident::active()->count(),
            'total_incidents' => Incident::count(),
            'resolved_incidents' => Incident::resolved()->count(),
            'devices_online' => $devices->filter->is_online->count(),
            'total_devices' => $devices->count(),
            'pending' => Incident::where('status', 'Pending')->count(),
            'responding' => Incident::where('status', 'Responding')->count(),
            'resolved' => Incident::resolved()->count(),
            'latest_pending' => $latestPending,
        ]);
    }

    public function exportExcel()
    {
        $incidents = Incident::with('device')->latest('reported_at')->get();
        $filename = 'incident_reports_'.date('Y-m-d_H-i-s').'.xls';

        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($incidents) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, ['Incident ID', 'Time Reported', 'Emergency Category', 'Building Location', 'Floor / Room', 'Device Code', 'Status'], "\t");

            foreach ($incidents as $inc) {
                fputcsv($file, [
                    $inc->id,
                    $inc->created_at ? $inc->created_at->format('Y-m-d H:i:s') : '',
                    $inc->emergency_type,
                    $inc->device->building ?? 'N/A',
                    trim(($inc->device->floor ?? '').' '.($inc->device->room ?? '')),
                    $inc->device->device_code ?? 'N/A',
                    strtoupper($inc->status),
                ], "\t");
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
