<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClinicController;
use App\Http\Controllers\NdrrmoController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        $role = auth()->user()->role;
        if ($role === 'DRRMO') {
            return redirect('/ndrrmo');
        } elseif ($role === 'Clinic') {
            return redirect('/clinic');
        }
    }

    return redirect('/login');
});

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'authenticate'])->middleware(['guest', 'throttle:5,1']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware(['auth', 'role:DRRMO'])->prefix('ndrrmo')->name('ndrrmo.')->group(function () {
    Route::get('/', [NdrrmoController::class, 'dashboard'])->name('dashboard');
    Route::get('/alerts', [NdrrmoController::class, 'alerts'])->name('alerts');
    Route::get('/logs', [NdrrmoController::class, 'logs'])->name('logs');
    Route::get('/map', [NdrrmoController::class, 'map'])->name('map');
    Route::get('/devices', [NdrrmoController::class, 'devices'])->name('devices');
    Route::post('/devices', [NdrrmoController::class, 'storeDevice'])->name('devices.store');
    Route::put('/devices/{device}', [NdrrmoController::class, 'updateDevice'])->name('devices.update');
    Route::delete('/devices/{device}', [NdrrmoController::class, 'destroyDevice'])->name('devices.destroy');
    Route::get('/sms', [NdrrmoController::class, 'sms'])->name('sms');
    Route::get('/reports', [NdrrmoController::class, 'reports'])->name('reports');
    Route::get('/stats-json', [NdrrmoController::class, 'statsJson'])->name('stats-json');
    Route::get('/reports/export-excel', [NdrrmoController::class, 'exportExcel'])->name('reports.export-excel');
    Route::post('/alerts/bulk-delete', [NdrrmoController::class, 'bulkDeleteAlerts'])->name('alerts.bulk-delete');
    Route::post('/incidents/{incident}/acknowledge', [NdrrmoController::class, 'acknowledgeIncident'])->name('incidents.acknowledge');
    Route::post('/incidents/{incident}/notify-clinic', [NdrrmoController::class, 'notifyClinic'])->name('incidents.notify-clinic');
    Route::post('/incidents/{incident}/dispatch', [NdrrmoController::class, 'dispatchIncident'])->name('incidents.dispatch');
    Route::post('/incidents/{incident}/resolve', [NdrrmoController::class, 'resolveIncident'])->name('incidents.resolve');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});

Route::middleware(['auth', 'role:Clinic'])->prefix('clinic')->name('clinic.')->group(function () {
    Route::get('/', [ClinicController::class, 'dashboard'])->name('dashboard');
    Route::get('/alerts', [ClinicController::class, 'alerts'])->name('alerts');
    Route::get('/incoming', [ClinicController::class, 'incoming'])->name('incoming');
    Route::get('/logs', [ClinicController::class, 'logs'])->name('logs');
    Route::get('/patients', [ClinicController::class, 'patients'])->name('patients');
    Route::get('/reports', [ClinicController::class, 'reports'])->name('reports');
    Route::get('/reports/export-excel', [ClinicController::class, 'exportExcel'])->name('reports.export-excel');
    Route::get('/stats-json', [ClinicController::class, 'statsJson'])->name('stats-json');
    Route::post('/alerts/bulk-delete', [ClinicController::class, 'bulkDeleteAlerts'])->name('alerts.bulk-delete');
    Route::delete('/alerts/{incident}', [ClinicController::class, 'destroyAlert'])->name('alerts.destroy');
    Route::post('/incidents/{incident}/acknowledge', [ClinicController::class, 'acknowledgeIncident'])->name('incidents.acknowledge');
    Route::post('/incidents/{incident}/resolve', [ClinicController::class, 'resolveIncident'])->name('incidents.resolve');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});
