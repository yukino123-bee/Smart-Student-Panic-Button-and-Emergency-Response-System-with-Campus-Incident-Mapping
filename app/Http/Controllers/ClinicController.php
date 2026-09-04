<?php

namespace App\Http\Controllers;

use App\Events\EmergencyReported;
use App\Models\Incident;
use App\Models\Notification;
use Illuminate\Http\Request;

class ClinicController extends Controller
{
    public function dashboard()
    {
        $activeAlerts = Incident::clinicRelevant()->active()->count();

        $incomingCount = Incident::clinicRelevant()
            ->whereDate('reported_at', today())
            ->active()
            ->count();

        $treatedTodayCount = Incident::clinicRelevant()
            ->whereDate('resolved_at', today())
            ->resolved()
            ->count();

        $resolvedTodayCount = $treatedTodayCount;

        $criticalIncidents = Incident::with('device')
            ->clinicRelevant()
            ->active()
            ->latest('reported_at')
            ->get();

        $recentHistory = Incident::with('device')
            ->clinicRelevant()
            ->whereDate('reported_at', today())
            ->latest('reported_at')
            ->take(10)
            ->get();

        $activeEmergency = $criticalIncidents->first();

        return view('clinic', compact(
            'activeAlerts',
            'incomingCount',
            'treatedTodayCount',
            'resolvedTodayCount',
            'criticalIncidents',
            'recentHistory',
            'activeEmergency'
        ));
    }

    public function alerts()
    {
        $alerts = Incident::with(['device', 'notifications'])
            ->clinicRelevant()
            ->active()
            ->latest('reported_at')
            ->get();

        return view('clinic.alerts', compact('alerts'));
    }

    public function bulkDeleteAlerts(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:incidents,id',
        ]);

        $ids = Incident::clinicRelevant()->whereIn('id', $validated['ids'])->pluck('id');

        Notification::whereIn('incident_id', $ids)->delete();
        Incident::whereIn('id', $ids)->delete();

        return redirect()->route('clinic.alerts')
            ->with('success', $ids->count().' alert(s) deleted successfully.');
    }

    public function destroyAlert($id)
    {
        $incident = Incident::clinicRelevant()->findOrFail($id);
        Notification::where('incident_id', $incident->id)->delete();
        $incident->delete();

        return redirect()->route('clinic.alerts')
            ->with('success', 'Alert deleted successfully.');
    }

    public function incoming()
    {
        $incomingPatients = Incident::with('device')
            ->clinicRelevant()
            ->active()
            ->latest('reported_at')
            ->get();

        return view('clinic.incoming', compact('incomingPatients'));
    }

    public function logs()
    {
        $logs = Incident::with('device')
            ->clinicRelevant()
            ->latest('reported_at')
            ->paginate(15);

        return view('clinic.logs', compact('logs'));
    }

    public function patients()
    {
        $patients = Incident::with('device')
            ->clinicRelevant()
            ->resolved()
            ->latest('resolved_at')
            ->paginate(15);

        return view('clinic.patients', compact('patients'));
    }

    public function reports()
    {
        $stats = Incident::clinicRelevant()->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $typeStats = Incident::clinicRelevant()->selectRaw('emergency_type, count(*) as count')
            ->groupBy('emergency_type')
            ->pluck('count', 'emergency_type');

        $buildingStats = Incident::clinicRelevant()->join('devices', 'incidents.device_id', '=', 'devices.id')
            ->selectRaw('devices.building, count(*) as count')
            ->groupBy('devices.building')
            ->pluck('count', 'devices.building');

        $totalIncidents = Incident::clinicRelevant()->count();
        $totalTreated = Incident::clinicRelevant()->resolved()->count();

        return view('clinic.reports', compact('stats', 'typeStats', 'buildingStats', 'totalIncidents', 'totalTreated'));
    }

    public function acknowledgeIncident(Incident $incident)
    {
        abort_unless(in_array($incident->emergency_type, [Incident::TYPE_CRITICAL, Incident::TYPE_MEDICAL], true), 404);
        
        if ($incident->status === 'Pending') {
            $incident->update(['status' => 'Acknowledged']);
        }
        $incident->load('device');

        Notification::create([
            'incident_id' => $incident->id,
            'recipient' => 'Clinic',
            'channel' => 'Dashboard',
            'status' => 'Acknowledged',
            'sent_at' => now(),
        ]);

        Notification::create([
            'incident_id' => $incident->id,
            'recipient' => 'DRRMO',
            'channel' => 'Dashboard',
            'status' => 'Acknowledged by Clinic',
            'sent_at' => now(),
        ]);

        broadcast(new EmergencyReported($incident))->toOthers();

        if (request()->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Medical alert acknowledged by Clinic.',
                'incident' => $incident,
            ]);
        }

        return redirect()->back()->with('success', 'Medical alert acknowledged by Clinic staff.');
    }

    public function resolveIncident(Incident $incident)
    {
        abort_unless(in_array($incident->emergency_type, [Incident::TYPE_CRITICAL, Incident::TYPE_MEDICAL], true), 404);

        if ($incident->status === 'Resolved') {
            if (request()->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Incident is already resolved.',
                    'incident' => $incident->load('device'),
                ]);
            }

            return redirect()->back()->with('info', 'Incident is already resolved.');
        }

        $incident->update(['status' => 'Resolved', 'resolved_at' => now()]);
        $incident->load('device');

        Notification::create([
            'incident_id' => $incident->id,
            'recipient' => 'Clinic',
            'channel' => 'Dashboard',
            'status' => 'Resolved',
            'sent_at' => now(),
        ]);

        Notification::create([
            'incident_id' => $incident->id,
            'recipient' => 'DRRMO',
            'channel' => 'Dashboard',
            'status' => 'Resolved by Clinic',
            'sent_at' => now(),
        ]);

        broadcast(new EmergencyReported($incident))->toOthers();

        if (request()->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Patient treated and incident marked resolved.',
                'incident' => $incident,
            ]);
        }

        return redirect()->back()->with('success', 'Patient treated and incident resolved.');
    }

    public function exportExcel()
    {
        $incidents = Incident::with('device')->clinicRelevant()->latest('reported_at')->get();
        $filename = 'clinic_patient_reports_'.date('Y-m-d_H-i-s').'.xls';

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

    public function statsJson()
    {
        $latestPending = Incident::with('device')
            ->clinicRelevant()
            ->active()
            ->whereDoesntHave('notifications', function ($query) {
                $query->where('recipient', 'Clinic')
                      ->where('status', 'Acknowledged');
            })
            ->latest('reported_at')
            ->first();

        return response()->json([
            'active_alerts' => Incident::clinicRelevant()->active()->count(),
            'incoming' => Incident::clinicRelevant()->whereDate('reported_at', today())->active()->count(),
            'treated_today' => Incident::clinicRelevant()->whereDate('resolved_at', today())->resolved()->count(),
            'resolved_today' => Incident::clinicRelevant()->whereDate('resolved_at', today())->resolved()->count(),
            'latest_pending' => $latestPending,
        ]);
    }
}
