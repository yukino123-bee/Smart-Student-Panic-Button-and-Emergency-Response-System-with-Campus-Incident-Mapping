<?php

namespace App\Http\Controllers\Api;

use App\Events\EmergencyReported;
use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Incident;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviceController extends Controller
{
    /**
     * Handle incoming emergency alerts from ESP32 devices.
     */
    public function emergency(Request $request)
    {
        // Validate incoming payload
        $validated = $request->validate([
            'device_id' => 'required|string',
            'emergency_category' => ['required', 'string', Rule::in(Incident::EMERGENCY_TYPES)],
            'timestamp' => ['nullable', 'date', 'before_or_equal:now'],
        ]);

        $deviceCode = $validated['device_id'];
        $emergencyType = $validated['emergency_category'];

        // Find the device
        $device = Device::where('device_code', $deviceCode)->first();

        if (! $device) {
            return response()->json([
                'status' => 'error',
                'message' => 'Device not found.',
            ], 404);
        }

        if ($device->status !== 'active') {
            return response()->json([
                'status' => 'error',
                'message' => 'Device is not active.',
            ], 409);
        }

        // Update device last_seen status
        $device->update(['last_seen' => now()]);

        // Create incident record
        $incident = Incident::create([
            'device_id' => $device->id,
            'emergency_type' => $emergencyType,
            'reported_at' => $validated['timestamp'] ?? now(),
            'status' => 'Pending',
        ]);

        // Create notifications log for both DRRMO and Clinic
        Notification::create([
            'incident_id' => $incident->id,
            'recipient' => 'DRRMO',
            'channel' => 'Dashboard',
            'status' => 'Delivered',
            'sent_at' => now(),
        ]);

        if ($emergencyType === Incident::TYPE_CRITICAL) {
            Notification::create([
                'incident_id' => $incident->id,
                'recipient' => 'Clinic',
                'channel' => 'Dashboard',
                'status' => 'Delivered',
                'sent_at' => now(),
            ]);
        }

        // Broadcast the event to WebSockets
        broadcast(new EmergencyReported($incident))->toOthers();

        return response()->json([
            'status' => 'success',
            'message' => 'Emergency alert received and processed.',
            'incident_id' => $incident->id,
        ], 201);
    }

    /**
     * Return status of pending incidents for an ESP32 device.
     */
    public function status(Request $request)
    {
        $validated = $request->validate(['device_id' => ['required', 'string']]);
        $deviceCode = $validated['device_id'];

        $device = Device::where('device_code', $deviceCode)->first();
        if (! $device) {
            return response()->json(['status' => 'error', 'message' => 'Device not found.'], 404);
        }

        $device->update(['last_seen' => now()]);

        $pendingIncident = Incident::where('device_id', $device->id)
            ->where('status', 'Pending')
            ->latest('reported_at')
            ->first();

        return response()->json([
            'device_code' => $deviceCode,
            'has_pending' => (bool) $pendingIncident,
            'status' => $pendingIncident ? 'pending' : 'normal',
            'incident_id' => $pendingIncident ? $pendingIncident->id : null,
        ]);
    }

    /**
     * Acknowledge an incident via API.
     */
    public function acknowledge($incident)
    {
        $incidentModel = Incident::find($incident);
        if (! $incidentModel) {
            $incidentModel = Incident::findOrFail($incident);
        }

        abort_unless($incidentModel->status === 'Pending', 409, 'Only pending incidents can be acknowledged.');
        $incidentModel->update(['status' => 'Acknowledged']);

        return response()->json([
            'status' => 'success',
            'message' => 'Emergency alert acknowledged.',
            'incident_id' => $incidentModel->id,
        ]);
    }

    public function dispatch($incident)
    {
        $incidentModel = Incident::findOrFail($incident);
        abort_unless(in_array($incidentModel->status, ['Pending', 'Acknowledged'], true), 409, 'Incident cannot be dispatched from its current status.');
        $incidentModel->update([
            'status' => 'Responding',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Responders dispatched.',
            'incident_id' => $incidentModel->id,
        ]);
    }

    public function resolve($incident)
    {
        $incidentModel = Incident::findOrFail($incident);
        abort_if($incidentModel->status === 'Resolved', 409, 'Incident is already resolved.');
        $incidentModel->update([
            'status' => 'Resolved',
            'resolved_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Incident marked resolved and recorded.',
            'incident_id' => $incidentModel->id,
        ]);
    }

    /**
     * Handle incoming SMS backup messages from GSM Module via Webhook.
     */
    public function smsWebhook(Request $request)
    {
        // Example parsed payload from GSM webhook:
        // { "sender": "+639123456789", "message": "EMERGENCY: GYM-001 - Critical Emergency" }

        $validated = $request->validate([
            'sender' => 'required|string',
            'message' => 'required|string',
        ]);

        $message = $validated['message'];

        // Very basic parsing for demo
        if (str_contains($message, 'EMERGENCY:')) {
            // Extract Device ID and Category
            // Format: "EMERGENCY: DEVICE_ID - CATEGORY"
            $parts = explode(' - ', str_replace('EMERGENCY: ', '', $message));

            if (count($parts) === 2) {
                $deviceCode = trim($parts[0]);
                $emergencyType = trim($parts[1]);

                if (! in_array($emergencyType, Incident::EMERGENCY_TYPES, true)) {
                    return response()->json(['status' => 'ignored', 'message' => 'Unknown emergency category'], 200);
                }

                $device = Device::where('device_code', $deviceCode)->first();

                if ($device) {
                    $incident = Incident::create([
                        'device_id' => $device->id,
                        'emergency_type' => $emergencyType,
                        'status' => 'Pending',
                        'remarks' => 'Received via SMS Backup ('.$validated['sender'].')',
                    ]);

                    Notification::create([
                        'incident_id' => $incident->id,
                        'recipient' => 'DRRMO',
                        'channel' => 'SMS Backup',
                        'status' => 'Delivered',
                        'sent_at' => now(),
                    ]);

                    if ($emergencyType === Incident::TYPE_CRITICAL) {
                        Notification::create([
                            'incident_id' => $incident->id,
                            'recipient' => 'Clinic',
                            'channel' => 'SMS Backup',
                            'status' => 'Delivered',
                            'sent_at' => now(),
                        ]);
                    }

                    // Broadcast the event to WebSockets
                    broadcast(new EmergencyReported($incident))->toOthers();

                    return response()->json(['status' => 'success', 'message' => 'SMS emergency processed'], 201);
                }
            }
        }

        return response()->json(['status' => 'ignored', 'message' => 'Not a valid emergency format'], 200);
    }
}
