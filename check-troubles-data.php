<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Equipment Troubles Data Check:\n";
echo "================================\n\n";

$count = App\Models\EquipmentTrouble::count();
echo "Total troubles: {$count}\n\n";

if ($count > 0) {
    echo "Sample data:\n";
    $troubles = App\Models\EquipmentTrouble::with('equipment')->take(10)->get();
    
    foreach ($troubles as $index => $trouble) {
        echo ($index + 1) . ". Equipment ID: {$trouble->equipment_id}";
        if ($trouble->equipment) {
            echo " ({$trouble->equipment->name})";
        }
        echo "\n   Issue: " . substr($trouble->issue_description, 0, 60) . "...\n";
        echo "   Date: {$trouble->created_at}\n\n";
    }
    
    // Group by equipment
    echo "\nTroubles per equipment:\n";
    $grouped = App\Models\EquipmentTrouble::select('equipment_id', \DB::raw('count(*) as total'))
        ->groupBy('equipment_id')
        ->orderBy('total', 'desc')
        ->with('equipment')
        ->get();
    
    foreach ($grouped as $g) {
        $name = $g->equipment ? $g->equipment->name : "Unknown";
        echo "- Equipment ID {$g->equipment_id} ({$name}): {$g->total} troubles\n";
    }
} else {
    echo "No troubles data found. Creating sample data...\n\n";
    
    // Create sample data for testing
    $equipmentId = 1; // Chiller 1
    
    $sampleTroubles = [
        [
            'title' => 'Low Refrigerant Pressure',
            'issue_description' => 'Refrigerant pressure drop detected. Suction pressure reading below normal range.',
            'priority' => 'high',
            'status' => 'resolved',
            'downtime_minutes' => 120,
            'created_at' => now()->subDays(5),
        ],
        [
            'title' => 'High Motor Amps',
            'issue_description' => 'Motor current exceeding normal range. May indicate motor overload or bearing issue.',
            'priority' => 'medium',
            'status' => 'resolved',
            'downtime_minutes' => 60,
            'created_at' => now()->subDays(15),
        ],
        [
            'title' => 'Low Refrigerant Pressure',
            'issue_description' => 'Recurring refrigerant pressure issue. System requires refrigerant top-up.',
            'priority' => 'high',
            'status' => 'resolved',
            'downtime_minutes' => 90,
            'created_at' => now()->subDays(25),
        ],
        [
            'title' => 'Evaporator Freeze',
            'issue_description' => 'Evaporator coil frozen. Possibly due to low refrigerant charge or airflow issue.',
            'priority' => 'critical',
            'status' => 'resolved',
            'downtime_minutes' => 180,
            'created_at' => now()->subDays(35),
        ],
        [
            'title' => 'Control Panel Issue',
            'issue_description' => 'Control panel not responding. Temperature control malfunction.',
            'priority' => 'high',
            'status' => 'resolved',
            'downtime_minutes' => 45,
            'created_at' => now()->subDays(45),
        ],
        [
            'title' => 'High Motor Amps',
            'issue_description' => 'Motor current spike during shift 3. Operator reported unusual noise.',
            'priority' => 'medium',
            'status' => 'resolved',
            'downtime_minutes' => 30,
            'created_at' => now()->subDays(55),
        ],
        [
            'title' => 'Low Refrigerant Pressure',
            'issue_description' => 'Third occurrence of low refrigerant pressure. Leak suspected.',
            'priority' => 'critical',
            'status' => 'investigating',
            'downtime_minutes' => 150,
            'created_at' => now()->subDays(65),
        ],
        [
            'title' => 'Control Panel Issue',
            'issue_description' => 'Temperature sensor malfunction. Reading inaccurate values.',
            'priority' => 'medium',
            'status' => 'resolved',
            'downtime_minutes' => 60,
            'created_at' => now()->subDays(75),
        ],
    ];
    
    $reportedBy = App\Models\User::first()->id;
    
    foreach ($sampleTroubles as $troubleData) {
        App\Models\EquipmentTrouble::create(array_merge($troubleData, [
            'equipment_id' => $equipmentId,
            'reported_by' => $reportedBy,
            'reported_at' => $troubleData['created_at'],
        ]));
    }
    
    echo "✅ Created 8 sample troubles for Equipment ID {$equipmentId}\n";
    echo "   Run the test again to see Root Cause Analysis in action!\n";
}
