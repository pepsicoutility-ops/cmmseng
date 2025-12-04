<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return redirect('pep/login');
});

// WhatsApp Test Routes (Super Admin only)
Route::middleware(['auth'])->group(function () {
    Route::get('/test-whatsapp', function () {
        if (!Auth::user() || Auth::user()->role !== 'super_admin') {
            abort(403, 'Unauthorized');
        }
        
        $whatsapp = app(\App\Services\WhatsAppService::class);
        $result = $whatsapp->testConnection();
        
        return response()->json($result);
    })->name('test.whatsapp');
    
    Route::get('/test-whatsapp-message', function () {
        if (!Auth::user() || Auth::user()->role !== 'super_admin') {
            abort(403, 'Unauthorized');
        }
        
        $whatsapp = app(\App\Services\WhatsAppService::class);
        $message = "🧪 *TEST MESSAGE*\n\nThis is a test notification from CMMS system.\n\n⏱️ Sent at: " . now()->format('d/m/Y H:i:s');
        $success = $whatsapp->sendMessage($message);
        
        return response()->json([
            'success' => $success,
            'message' => $success ? 'Message sent successfully' : 'Failed to send message'
        ]);
    })->name('test.whatsapp.message');
});

// Health check endpoint for monitoring
Route::get('/health', function () {
    try {
        // Check database connection
        DB::connection()->getPdo();
        $dbStatus = 'connected';
    } catch (\Exception $e) {
        $dbStatus = 'disconnected';
    }
    
    // Check cache
    Cache::put('health-check', true, 60);
    $cacheStatus = Cache::has('health-check') ? 'working' : 'not working';
    
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toIso8601String(),
        'database' => $dbStatus,
        'cache' => $cacheStatus,
        'app_version' => config('app.version', '1.0.0'),
    ], $dbStatus === 'connected' ? 200 : 503);
})->name('health.check');

// Barcode routes (public, no authentication required)
Route::get('/barcode/wo/{token}', function($token) {
    // Redirect old WO route to new form selector
    return redirect("/barcode/form-selector/{$token}");
})->name('barcode.wo.form');

Route::get('/barcode/work-order/{token}', function($token) {
    // Direct route to work order form (used by form selector)
    $barcodeToken = \App\Models\BarcodeToken::where('token', $token)
        ->where('is_active', true)
        ->first();
    
    if (!$barcodeToken) {
        abort(404, 'Invalid or inactive barcode token');
    }
    
    return view('barcode.wo-form', compact('token'));
})->name('barcode.work-order.form');

Route::post('/barcode/wo/submit', function(\Illuminate\Http\Request $request) {
    $request->validate([
        'token' => 'required|exists:barcode_tokens,token',
        'gpid' => 'required|exists:users,gpid',
        'operator_name' => 'required|string',
        'shift' => 'required|in:1,2,3',
        'problem_type' => 'required|in:abnormality,breakdown,request_consumable,improvement,inspection',
        'assign_to' => 'required|in:utility,mechanic,electric',
        'area_id' => 'required|exists:areas,id',
        'sub_area_id' => 'required|exists:sub_areas,id',
        'asset_id' => 'required|exists:assets,id',
        'sub_asset_id' => 'required|exists:sub_assets,id',
        'description' => 'required|string',
        'photos.*' => 'nullable|image|max:5120',
    ]);
    
    // Upload photos
    $photoPaths = [];
    if ($request->hasFile('photos')) {
        foreach ($request->file('photos') as $photo) {
            $photoPaths[] = $photo->store('wo-photos', 'public');
        }
    }
    
    // Generate WO number with retry on duplicate
    $date = now()->format('Ym');
    $woNumber = null;
    $maxAttempts = 10;
    $attempt = 0;
    
    while (!$woNumber && $attempt < $maxAttempts) {
        $attempt++;
        
        // Get max number from existing WOs (including soft-deleted)
        $lastWo = \App\Models\WorkOrder::withTrashed()
            ->where('wo_number', 'LIKE', "WO-{$date}-%")
            ->orderByRaw('CAST(SUBSTRING(wo_number, -4) AS UNSIGNED) DESC')
            ->first();
        
        if ($lastWo) {
            $lastNumber = (int) substr($lastWo->wo_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        
        $tempWoNumber = "WO-{$date}-" . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        
        // Check if exists (including soft-deleted)
        if (!\App\Models\WorkOrder::withTrashed()->where('wo_number', $tempWoNumber)->exists()) {
            $woNumber = $tempWoNumber;
        }
    }
    
    if (!$woNumber) {
        return back()->withErrors(['error' => 'Failed to generate WO number. Please try again.'])->withInput();
    }
    
    // Determine priority
    $priority = match($request->problem_type) {
        'breakdown' => 'critical',
        'abnormality' => 'high',
        'inspection' => 'medium',
        default => 'low',
    };
    
    // Create Work Order with try-catch for duplicate handling
    try {
        $wo = \App\Models\WorkOrder::create([
            'wo_number' => $woNumber,
            'created_by_gpid' => $request->query('gpid'),
            'operator_name' => $request->operator_name,
            'shift' => $request->query('shift'),
            'problem_type' => $request->problem_type,
            'assign_to' => $request->assign_to,
            'area_id' => $request->area_id,
            'sub_area_id' => $request->sub_area_id,
            'asset_id' => $request->asset_id,
            'sub_asset_id' => $request->sub_asset_id,
            'description' => $request->description,
            'photos' => $photoPaths,
            'status' => 'submitted',
            'priority' => $priority,
        ]);
    } catch (\Illuminate\Database\QueryException $e) {
        // If duplicate, return error
        if ($e->getCode() == 23000) {
            return back()->withErrors(['error' => 'Duplicate work order detected. Please try again.'])->withInput();
        }
        throw $e;
    }
    
    return redirect()->route('barcode.wo.success', [
        'wo_number' => $wo->wo_number,
        'token' => $request->query('token')
    ]);
})->name('barcode.wo.submit');

Route::get('/barcode/wo/success/{wo_number}', function($wo_number, \Illuminate\Http\Request $request) {
    $token = $request->query('token');
    return view('barcode.wo-success', compact('wo_number', 'token'));
})->name('barcode.wo.success');

// Dynamic PWA manifest for each operator token
Route::get('/barcode/manifest/{token}.json', function($token) {
    $barcodeToken = \App\Models\BarcodeToken::where('token', $token)
        ->where('is_active', true)
        ->first();
    
    if (!$barcodeToken) {
        abort(404, 'Invalid token');
    }
    
    $manifest = [
        'name' => 'PEPSICO Engineering CMMS',
        'short_name' => 'CMMS',
        'description' => 'Computerized Maintenance Management System - Mobile Forms',
        'start_url' => "/barcode/form-selector/{$token}",
        'scope' => '/barcode/',
        'display' => 'standalone',
        'background_color' => '#ffffff',
        'theme_color' => '#2563eb',
        'orientation' => 'portrait',
        'icons' => [
            [
                'src' => '/images/pepsico-pwa.png',
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any maskable'
            ],
            [
                'src' => '/images/pepsico-pwa.png',
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any maskable'
            ]
        ],
        'categories' => ['productivity', 'utilities'],
        'shortcuts' => [
            [
                'name' => 'Create Work Order',
                'short_name' => 'New WO',
                'description' => 'Report equipment problems',
                'url' => "/barcode/wo/{$token}",
                'icons' => [
                    [
                        'src' => '/images/pepsico-pwa.png',
                        'sizes' => '96x96',
                        'type' => 'image/png'
                    ]
                ]
            ],
            [
                'name' => 'Running Hours',
                'short_name' => 'Hours',
                'description' => 'Record equipment running hours',
                'url' => "/barcode/running-hours/{$token}",
                'icons' => [
                    [
                        'src' => '/images/pepsico-pwa.png',
                        'sizes' => '96x96',
                        'type' => 'image/png'
                    ]
                ]
            ],
            [
                'name' => 'PM Checklist',
                'short_name' => 'PM Check',
                'description' => 'Complete preventive maintenance checklist',
                'url' => "/barcode/pm-checklist/{$token}",
                'icons' => [
                    [
                        'src' => '/images/pepsico-pwa.png',
                        'sizes' => '96x96',
                        'type' => 'image/png'
                    ]
                ]
            ],
            [
                'name' => 'Request Parts',
                'short_name' => 'Parts',
                'description' => 'Request spare parts',
                'url' => "/barcode/request-parts/{$token}",
                'icons' => [
                    [
                        'src' => '/images/pepsico-pwa.png',
                        'sizes' => '96x96',
                        'type' => 'image/png'
                    ]
                ]
            ]
        ]
    ];
    
    return response()->json($manifest)
        ->header('Content-Type', 'application/manifest+json');
})->name('barcode.manifest');

// Form Selector Route
Route::get('/barcode/form-selector/{token}', function($token) {
    $barcodeToken = \App\Models\BarcodeToken::where('token', $token)
        ->where('is_active', true)
        ->first();
    
    if (!$barcodeToken) {
        abort(404, 'Invalid token');
    }
    
    // Pass department to view for filtering
    $department = $barcodeToken->department;
    
    return view('barcode.form-selector', compact('token', 'department'));
})->name('barcode.form-selector');

// Running Hours Routes
Route::get('/barcode/running-hours/{token}', function($token) {
    $barcodeToken = \App\Models\BarcodeToken::where('token', $token)
        ->where('is_active', true)
        ->first();
    
    if (!$barcodeToken) {
        abort(404, 'Invalid token');
    }
    
    return view('barcode.running-hours-form', compact('token'));
})->name('barcode.running-hours');

Route::post('/barcode/running-hours/submit', function(\Illuminate\Http\Request $request) {
    $request->validate([
        'token' => 'required|exists:barcode_tokens,token',
        'gpid' => 'required|exists:users,gpid',
        'shift' => 'required|in:1,2,3',
        'area_id' => 'required|exists:areas,id',
        'sub_area_id' => 'required|exists:sub_areas,id',
        'asset_id' => 'required|exists:assets,id',
        'hours' => 'required|numeric|min:0',
    ]);
    
    \App\Models\RunningHour::create([
        'asset_id' => $request->asset_id,
        'hours' => $request->hours,
        'cycles' => $request->cycles,
        'recorded_by_gpid' => $request->query('gpid'),
        'notes' => $request->notes,
        'recorded_at' => now(),
    ]);
    
    return response()->json(['success' => true]);
})->name('barcode.running-hours.submit');

// PM Checklist Routes
Route::get('/barcode/pm-checklist/{token}', function($token) {
    $barcodeToken = \App\Models\BarcodeToken::where('token', $token)
        ->where('is_active', true)
        ->first();
    
    if (!$barcodeToken) {
        abort(404, 'Invalid token');
    }
    
    $pmSchedules = \App\Models\PmSchedule::where('is_active', true)
        ->where('status', 'active')
        ->select('id', 'code', 'title')
        ->get();
    
    return view('barcode.pm-checklist-form', compact('token', 'pmSchedules'));
})->name('barcode.pm-checklist');

Route::post('/barcode/pm-checklist/submit', function(\Illuminate\Http\Request $request) {
    $request->validate([
        'token' => 'required|exists:barcode_tokens,token',
        'pm_schedule_id' => 'required|exists:pm_schedules,id',
        'gpid' => 'required|exists:users,gpid',
    ]);
    
    // Create PM Execution record
    \App\Models\PmExecution::create([
        'pm_schedule_id' => $request->pm_schedule_id,
        'executed_by_gpid' => $request->query('gpid'),
        'scheduled_date' => now()->toDateString(),
        'actual_start' => now(),
        'actual_end' => now(),
        'duration' => 0,
        'checklist_data' => $request->checklist_data ?? [],
        'notes' => $request->notes,
        'status' => 'completed',
        'compliance_status' => 'compliant',
        'is_on_time' => true,
    ]);
    
    return redirect()->route('barcode.pm.success', [
        'gpid' => $request->query('gpid'),
        'token' => $request->query('token')
    ]);
})->name('barcode.pm-checklist.submit');

// Parts Request Routes  
Route::get('/barcode/request-parts/{token}', function($token) {
    $barcodeToken = \App\Models\BarcodeToken::where('token', $token)
        ->where('is_active', true)
        ->first();
    
    if (!$barcodeToken) {
        abort(404, 'Invalid token');
    }
    
    return view('barcode.request-parts-form', compact('token'));
})->name('barcode.request-parts');

Route::post('/barcode/request-parts/submit', function(\Illuminate\Http\Request $request) {
    $request->validate([
        'token' => 'required|exists:barcode_tokens,token',
        'gpid' => 'required|exists:users,gpid',
        'part_id' => 'required|exists:parts,id',
        'quantity' => 'required|integer|min:1',
        'department' => 'required|in:utility,mechanic,electric',
        'urgency' => 'required|in:critical,high,medium,low',
        'reason' => 'required|string',
    ]);
    
    // Create parts request (you can create a PartsRequest model or use InventoryMovement)
    \App\Models\InventoryMovement::create([
        'part_id' => $request->part_id,
        'quantity' => $request->quantity,
        'movement_type' => 'out',
        'reference_type' => 'parts_request',
        'moved_by_gpid' => $request->query('gpid'),
        'notes' => "Request: {$request->reason} (Urgency: {$request->urgency}, Dept: {$request->department})",
    ]);
    
    return redirect()->route('barcode.parts.success', [
        'gpid' => $request->query('gpid'),
        'token' => $request->query('token')
    ]);
})->name('barcode.request-parts.submit');

// API routes for cascade dropdowns (public)
Route::get('/api/areas', function() {
    return \App\Models\Area::where('is_active', true)
        ->select('id', 'name')
        ->orderBy('name')
        ->get();
});

Route::get('/api/sub-areas', function(\Illuminate\Http\Request $request) {
    return \App\Models\SubArea::where('area_id', $request->area_id)
        ->where('is_active', true)
        ->select('id', 'name')
        ->get();
});

Route::get('/api/assets', function(\Illuminate\Http\Request $request) {
    return \App\Models\Asset::where('sub_area_id', $request->sub_area_id)
        ->where('is_active', true)
        ->select('id', 'name')
        ->get();
});

Route::get('/api/sub-assets', function(\Illuminate\Http\Request $request) {
    return \App\Models\SubAsset::where('asset_id', $request->asset_id)
        ->where('is_active', true)
        ->select('id', 'name')
        ->get();
});

Route::get('/api/parts', function() {
    return \App\Models\Part::where('is_active', true)
        ->select('id', 'part_number', 'name', 'current_stock')
        ->where('current_stock', '>', 0)
        ->orderBy('name')
        ->get();
});

Route::get('/api/validate-gpid', function(\Illuminate\Http\Request $request) {
    $gpid = $request->query('gpid');
    $user = \App\Models\User::where('gpid', $gpid)->first();
    if ($user) {
        return response()->json([
            'valid' => true,
            'name' => $user->name
        ]);
    }
    return response()->json(['valid' => false]);
});

Route::get('/api/user-by-gpid/{gpid}', function($gpid) {
    $user = \App\Models\User::where('gpid', $gpid)->first();
    if ($user) {
        return response()->json([
            'gpid' => $user->gpid,
            'name' => $user->name
        ]);
    }
    return response()->json(['error' => 'User not found'], 404);
});

// Compressor 1 Routes
Route::get('/barcode/compressor1/{token}', function($token) {
    $barcodeToken = \App\Models\BarcodeToken::where('token', $token)
        ->where('is_active', true)
        ->first();
    
    if (!$barcodeToken) {
        abort(404, 'Invalid token');
    }
    
    return view('barcode.compressor1', compact('token'));
})->name('barcode.compressor1');

Route::post('/barcode/compressor1/submit', function(\Illuminate\Http\Request $request) {
    $request->validate([
        'token' => 'required|exists:barcode_tokens,token',
        'shift' => 'required|in:1,2,3',
    ]);
    
    $checklist = \App\Models\Compressor1Checklist::create([
        'shift' => $request->shift,
        'gpid' => $request->gpid,
        'name' => $request->name,
        'tot_run_hours' => $request->tot_run_hours,
        'bearing_oil_temperature' => $request->bearing_oil_temperature,
        'bearing_oil_pressure' => $request->bearing_oil_pressure,
        'discharge_pressure' => $request->discharge_pressure,
        'discharge_temperature' => $request->discharge_temperature,
        'cws_temperature' => $request->cws_temperature,
        'cwr_temperature' => $request->cwr_temperature,
        'cws_pressure' => $request->cws_pressure,
        'cwr_pressure' => $request->cwr_pressure,
        'refrigerant_pressure' => $request->refrigerant_pressure,
        'dew_point' => $request->dew_point,
        'notes' => $request->notes,
    ]);
    
    // Send WhatsApp notification
    try {
        $whatsapp = app(\App\Services\WhatsAppService::class);
        $whatsapp->sendCompressorNotification($checklist->toArray(), 1);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('WhatsApp notification failed for Compressor 1', ['error' => $e->getMessage()]);
    }
    
    return redirect()->route('barcode.compressor.success', [
        'title' => 'Compressor 1 Checklist',
        'shift' => $request->shift,
        'gpid' => $request->gpid,
        'token' => $request->token,
        'back_url' => route('barcode.compressor1', ['token' => $request->token])
    ]);
})->name('barcode.compressor1.submit');

// Compressor 2 Routes
Route::get('/barcode/compressor2/{token}', function($token) {
    $barcodeToken = \App\Models\BarcodeToken::where('token', $token)
        ->where('is_active', true)
        ->first();
    
    if (!$barcodeToken) {
        abort(404, 'Invalid token');
    }
    
    return view('barcode.compressor2', compact('token'));
})->name('barcode.compressor2');

Route::post('/barcode/compressor2/submit', function(\Illuminate\Http\Request $request) {
    $request->validate([
        'token' => 'required|exists:barcode_tokens,token',
        'shift' => 'required|in:1,2,3',
    ]);
    
    $checklist = \App\Models\Compressor2Checklist::create([
        'shift' => $request->shift,
        'gpid' => $request->gpid,
        'name' => $request->name,
        'tot_run_hours' => $request->tot_run_hours,
        'bearing_oil_temperature' => $request->bearing_oil_temperature,
        'bearing_oil_pressure' => $request->bearing_oil_pressure,
        'discharge_pressure' => $request->discharge_pressure,
        'discharge_temperature' => $request->discharge_temperature,
        'cws_temperature' => $request->cws_temperature,
        'cwr_temperature' => $request->cwr_temperature,
        'cws_pressure' => $request->cws_pressure,
        'cwr_pressure' => $request->cwr_pressure,
        'refrigerant_pressure' => $request->refrigerant_pressure,
        'dew_point' => $request->dew_point,
        'notes' => $request->notes,
    ]);
    
    // Send WhatsApp notification
    try {
        $whatsapp = app(\App\Services\WhatsAppService::class);
        $whatsapp->sendCompressorNotification($checklist->toArray(), 2);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('WhatsApp notification failed for Compressor 2', ['error' => $e->getMessage()]);
    }
    
    return redirect()->route('barcode.compressor.success', [
        'title' => 'Compressor 2 Checklist',
        'shift' => $request->shift,
        'gpid' => $request->gpid,
        'token' => $request->token,
        'back_url' => route('barcode.compressor2', ['token' => $request->token])
    ]);
})->name('barcode.compressor2.submit');

// Chiller 1 Routes
Route::get('/barcode/chiller1/{token}', function($token) {
    $barcodeToken = \App\Models\BarcodeToken::where('token', $token)
        ->where('is_active', true)
        ->first();
    
    if (!$barcodeToken) {
        abort(404, 'Invalid token');
    }
    
    return view('barcode.chiller1', compact('token'));
})->name('barcode.chiller1');

Route::post('/barcode/chiller1/submit', function(\Illuminate\Http\Request $request) {
    $request->validate([
        'token' => 'required|exists:barcode_tokens,token',
        'shift' => 'required|in:1,2,3',
    ]);
    
    $checklist = \App\Models\Chiller1Checklist::create([
        'shift' => $request->shift,
        'gpid' => $request->gpid,
        'name' => $request->name,
        'sat_evap_t' => $request->sat_evap_t,
        'sat_dis_t' => $request->sat_dis_t,
        'dis_superheat' => $request->dis_superheat,
        'lcl' => $request->lcl,
        'fla' => $request->fla,
        'ecl' => $request->ecl,
        'lel' => $request->lel,
        'eel' => $request->eel,
        'evap_p' => $request->evap_p,
        'conds_p' => $request->conds_p,
        'oil_p' => $request->oil_p,
        'evap_t_diff' => $request->evap_t_diff,
        'conds_t_diff' => $request->conds_t_diff,
        'reff_levels' => $request->reff_levels,
        'motor_amps' => $request->motor_amps,
        'motor_volts' => $request->motor_volts,
        'heatsink_t' => $request->heatsink_t,
        'run_hours' => $request->run_hours,
        'motor_t' => $request->motor_t,
        'comp_oil_level' => $request->comp_oil_level,
        'cooler_reff_small_temp_diff' => $request->cooler_reff_small_temp_diff,
        'cooler_liquid_inlet_pressure' => $request->cooler_liquid_inlet_pressure,
        'cooler_liquid_outlet_pressure' => $request->cooler_liquid_outlet_pressure,
        'cooler_pressure_drop' => $request->cooler_pressure_drop,
        'cond_reff_small_temp_diff' => $request->cond_reff_small_temp_diff,
        'cond_liquid_inlet_pressure' => $request->cond_liquid_inlet_pressure,
        'cond_liquid_outlet_pressure' => $request->cond_liquid_outlet_pressure,
        'cond_pressure_drop' => $request->cond_pressure_drop,
        'notes' => $request->notes,
    ]);
    
    // Send WhatsApp notification
    try {
        $whatsapp = app(\App\Services\WhatsAppService::class);
        $whatsapp->sendChillerNotification($checklist->toArray(), 1);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('WhatsApp notification failed for Chiller 1', ['error' => $e->getMessage()]);
    }
    
    return redirect()->route('barcode.chiller.success', [
        'title' => 'Chiller 1 Checklist',
        'shift' => $request->shift,
        'gpid' => $request->gpid,
        'token' => $request->token,
        'back_url' => route('barcode.chiller1', ['token' => $request->token])
    ]);
})->name('barcode.chiller1.submit');

// Chiller 2 Routes
Route::get('/barcode/chiller2/{token}', function($token) {
    $barcodeToken = \App\Models\BarcodeToken::where('token', $token)
        ->where('is_active', true)
        ->first();
    
    if (!$barcodeToken) {
        abort(404, 'Invalid token');
    }
    
    return view('barcode.chiller2', compact('token'));
})->name('barcode.chiller2');

Route::post('/barcode/chiller2/submit', function(\Illuminate\Http\Request $request) {
    $request->validate([
        'token' => 'required|exists:barcode_tokens,token',
        'shift' => 'required|in:1,2,3',
    ]);
    
    $checklist = \App\Models\Chiller2Checklist::create([
        'shift' => $request->shift,
        'gpid' => $request->gpid,
        'name' => $request->name,
        'sat_evap_t' => $request->sat_evap_t,
        'sat_dis_t' => $request->sat_dis_t,
        'dis_superheat' => $request->dis_superheat,
        'lcl' => $request->lcl,
        'fla' => $request->fla,
        'ecl' => $request->ecl,
        'lel' => $request->lel,
        'eel' => $request->eel,
        'evap_p' => $request->evap_p,
        'conds_p' => $request->conds_p,
        'oil_p' => $request->oil_p,
        'evap_t_diff' => $request->evap_t_diff,
        'conds_t_diff' => $request->conds_t_diff,
        'reff_levels' => $request->reff_levels,
        'motor_amps' => $request->motor_amps,
        'motor_volts' => $request->motor_volts,
        'heatsink_t' => $request->heatsink_t,
        'run_hours' => $request->run_hours,
        'motor_t' => $request->motor_t,
        'comp_oil_level' => $request->comp_oil_level,
        'cooler_reff_small_temp_diff' => $request->cooler_reff_small_temp_diff,
        'cooler_liquid_inlet_pressure' => $request->cooler_liquid_inlet_pressure,
        'cooler_liquid_outlet_pressure' => $request->cooler_liquid_outlet_pressure,
        'cooler_pressure_drop' => $request->cooler_pressure_drop,
        'cond_reff_small_temp_diff' => $request->cond_reff_small_temp_diff,
        'cond_liquid_inlet_pressure' => $request->cond_liquid_inlet_pressure,
        'cond_liquid_outlet_pressure' => $request->cond_liquid_outlet_pressure,
        'cond_pressure_drop' => $request->cond_pressure_drop,
        'notes' => $request->notes,
    ]);
    
    // Send WhatsApp notification
    try {
        $whatsapp = app(\App\Services\WhatsAppService::class);
        $whatsapp->sendChillerNotification($checklist->toArray(), 2);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('WhatsApp notification failed for Chiller 2', ['error' => $e->getMessage()]);
    }
    
    return redirect()->route('barcode.chiller.success', [
        'title' => 'Chiller 2 Checklist',
        'shift' => $request->shift,
        'gpid' => $request->gpid,
        'token' => $request->token,
        'back_url' => route('barcode.chiller2', ['token' => $request->token])
    ]);
})->name('barcode.chiller2.submit');

// AHU Routes
// Success route must come BEFORE the dynamic {token} route
Route::get('/barcode/ahu/success', function(\Illuminate\Http\Request $request) {
    return view('barcode.ahu-success', [
        'title' => $request->query('title', 'AHU Checklist'),
        'shift' => $request->query('shift'),
        'gpid' => $request->query('gpid'),
        'token' => $request->query('token'),
        'back_url' => $request->query('back_url')
    ]);
})->name('barcode.ahu.success');

Route::get('/barcode/ahu/{token}', function($token) {
    $barcodeToken = \App\Models\BarcodeToken::where('token', $token)
        ->where('is_active', true)
        ->first();
    
    if (!$barcodeToken) {
        abort(404, 'Invalid token');
    }
    
    return view('barcode.ahu', compact('token'));
})->name('barcode.ahu');

Route::post('/barcode/ahu/submit', function(\Illuminate\Http\Request $request) {
    $request->validate([
        'token' => 'required|exists:barcode_tokens,token',
        'shift' => 'required|in:1,2,3',
    ]);
    
    $checklist = \App\Models\AhuChecklist::create([
        'shift' => $request->shift,
        'gpid' => $request->gpid,
        'name' => $request->name,
        'ahu_mb_1_1_hf' => $request->ahu_mb_1_1_hf,
        'ahu_mb_1_1_pf' => $request->ahu_mb_1_1_pf,
        'ahu_mb_1_1_mf' => $request->ahu_mb_1_1_mf,
        'ahu_mb_1_2_hf' => $request->ahu_mb_1_2_hf,
        'ahu_mb_1_2_mf' => $request->ahu_mb_1_2_mf,
        'ahu_mb_1_2_pf' => $request->ahu_mb_1_2_pf,
        'ahu_mb_1_3_hf' => $request->ahu_mb_1_3_hf,
        'ahu_mb_1_3_mf' => $request->ahu_mb_1_3_mf,
        'ahu_mb_1_3_pf' => $request->ahu_mb_1_3_pf,
        'pau_mb_1_pf' => $request->pau_mb_1_pf,
        'pau_mb_pr_1a_hf' => $request->pau_mb_pr_1a_hf,
        'pau_mb_pr_1a_mf' => $request->pau_mb_pr_1a_mf,
        'pau_mb_pr_1a_pf' => $request->pau_mb_pr_1a_pf,
        'pau_mb_pr_1b_hf' => $request->pau_mb_pr_1b_hf,
        'pau_mb_pr_1b_mf' => $request->pau_mb_pr_1b_mf,
        'pau_mb_pr_1b_pf' => $request->pau_mb_pr_1b_pf,
        'pau_mb_pr_1c_hf' => $request->pau_mb_pr_1c_hf,
        'pau_mb_pr_1c_pf' => $request->pau_mb_pr_1c_pf,
        'pau_mb_pr_1c_mf' => $request->pau_mb_pr_1c_mf,
        'ahu_vrf_mb_ms_1a_pf' => $request->ahu_vrf_mb_ms_1a_pf,
        'ahu_vrf_mb_ms_1b_pf' => $request->ahu_vrf_mb_ms_1b_pf,
        'ahu_vrf_mb_ms_1c_pf' => $request->ahu_vrf_mb_ms_1c_pf,
        'ahu_vrf_mb_ss_1a_pf' => $request->ahu_vrf_mb_ss_1a_pf,
        'ahu_vrf_mb_ss_1b_pf' => $request->ahu_vrf_mb_ss_1b_pf,
        'ahu_vrf_mb_ss_1c_pf' => $request->ahu_vrf_mb_ss_1c_pf,
        'if_pre_filter_a' => $request->if_pre_filter_a,
        'if_medium_a' => $request->if_medium_a,
        'if_hepa_a' => $request->if_hepa_a,
        'if_pre_filter_b' => $request->if_pre_filter_b,
        'if_medium_b' => $request->if_medium_b,
        'if_hepa_b' => $request->if_hepa_b,
        'if_pre_filter_c' => $request->if_pre_filter_c,
        'if_medium_c' => $request->if_medium_c,
        'if_hepa_c' => $request->if_hepa_c,
        'if_pre_filter_d' => $request->if_pre_filter_d,
        'if_medium_d' => $request->if_medium_d,
        'if_hepa_d' => $request->if_hepa_d,
        'if_pre_filter_e' => $request->if_pre_filter_e,
        'if_medium_e' => $request->if_medium_e,
        'if_hepa_e' => $request->if_hepa_e,
        'if_pre_filter_f' => $request->if_pre_filter_f,
        'if_medium_f' => $request->if_medium_f,
        'if_hepa_f' => $request->if_hepa_f,
        'notes' => $request->notes,
    ]);
    
    // Send WhatsApp notification
    try {
        $whatsapp = app(\App\Services\WhatsAppService::class);
        $whatsapp->sendAhuNotification($checklist->toArray());
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('WhatsApp notification failed for AHU', ['error' => $e->getMessage()]);
    }
    
    return redirect()->route('barcode.ahu.success', [
        'title' => 'AHU Checklist',
        'shift' => $request->shift,
        'gpid' => $request->gpid,
        'token' => $request->token,
        'back_url' => route('barcode.ahu', ['token' => $request->token])
    ]);
})->name('barcode.ahu.submit');

// Success Pages
Route::get('/barcode/compressor/success', function(\Illuminate\Http\Request $request) {
    return view('barcode.compressor-success', [
        'title' => $request->title,
        'shift' => $request->query('shift'),
        'gpid' => $request->query('gpid'),
        'token' => $request->query('token'),
        'back_url' => $request->back_url
    ]);
})->name('barcode.compressor.success');

Route::get('/barcode/chiller/success', function(\Illuminate\Http\Request $request) {
    return view('barcode.chiller-success', [
        'title' => $request->title,
        'shift' => $request->query('shift'),
        'gpid' => $request->query('gpid'),
        'token' => $request->query('token'),
        'back_url' => $request->back_url
    ]);
})->name('barcode.chiller.success');

Route::get('/barcode/pm/success', function(\Illuminate\Http\Request $request) {
    return view('barcode.pm-success', [
        'gpid' => $request->query('gpid'),
        'token' => $request->query('token')
    ]);
})->name('barcode.pm.success');

Route::get('/barcode/parts/success', function(\Illuminate\Http\Request $request) {
    return view('barcode.parts-success', [
        'gpid' => $request->query('gpid'),
        'token' => $request->query('token')
    ]);
})->name('barcode.parts.success');


