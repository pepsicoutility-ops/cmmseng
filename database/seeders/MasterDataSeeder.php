<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\SubArea;
use App\Models\Asset;
use App\Models\SubAsset;
use App\Models\Part;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding Master Data...');

        // ========================================
        // 1. SEED AREAS
        // ========================================
        $this->command->info('Seeding Areas...');
        
        $proses = Area::create([
            'name' => 'Proses',
            'code' => 'AREA-PROSES',
            'description' => 'Area Proses Produksi',
            'is_active' => true,
        ]);

        $packaging = Area::create([
            'name' => 'Packaging',
            'code' => 'AREA-PACKAGING',
            'description' => 'Area Packaging',
            'is_active' => true,
        ]);

        $utility = Area::create([
            'name' => 'Utility',
            'code' => 'AREA-UTILITY',
            'description' => 'Area Utility',
            'is_active' => true,
        ]);

        $this->command->info('✅ Areas seeded: 3 areas');

        // ========================================
        // 2. SEED SUB AREAS
        // ========================================
        $this->command->info('Seeding Sub Areas...');

        // Sub Areas for Proses
        $ep = SubArea::create([
            'area_id' => $proses->id,
            'name' => 'EP (Extrusion Process)',
            'code' => 'SUB-EP',
            'description' => 'Extrusion Process Line',
            'is_active' => true,
        ]);

        $pc = SubArea::create([
            'area_id' => $proses->id,
            'name' => 'PC (Processing Center)',
            'code' => 'SUB-PC',
            'description' => 'Processing Center Line',
            'is_active' => true,
        ]);

        $tc = SubArea::create([
            'area_id' => $proses->id,
            'name' => 'TC (Temperature Control)',
            'code' => 'SUB-TC',
            'description' => 'Temperature Control Line',
            'is_active' => true,
        ]);

        // Sub Areas for Packaging
        $dbm = SubArea::create([
            'area_id' => $packaging->id,
            'name' => 'DBM (Double Bag Machine)',
            'code' => 'SUB-DBM',
            'description' => 'Double Bag Machine Line',
            'is_active' => true,
        ]);

        $lbcss = SubArea::create([
            'area_id' => $packaging->id,
            'name' => 'LBCSS (Large Bag Conveyor Sealing System)',
            'code' => 'SUB-LBCSS',
            'description' => 'Large Bag Conveyor Sealing System',
            'is_active' => true,
        ]);

        $this->command->info('✅ Sub Areas seeded: 5 sub areas');

        // ========================================
        // 3. SEED ASSETS
        // ========================================
        $this->command->info('Seeding Assets...');

        // Assets for EP
        $processing = Asset::create([
            'sub_area_id' => $ep->id,
            'name' => 'Processing Unit',
            'code' => 'AST-PROC-001',
            'model' => 'PRO-500X',
            'serial_number' => 'SN-PROC-2024-001',
            'installation_date' => '2024-01-15',
            'is_active' => true,
        ]);

        $vmm = Asset::create([
            'sub_area_id' => $ep->id,
            'name' => 'VMM (Vertical Mixing Machine)',
            'code' => 'AST-VMM-001',
            'model' => 'VMM-300',
            'serial_number' => 'SN-VMM-2024-001',
            'installation_date' => '2024-02-10',
            'is_active' => true,
        ]);

        $extruder = Asset::create([
            'sub_area_id' => $ep->id,
            'name' => 'EXTRUDER',
            'code' => 'AST-EXT-001',
            'model' => 'EXT-800',
            'serial_number' => 'SN-EXT-2024-001',
            'installation_date' => '2024-03-05',
            'is_active' => true,
        ]);

        // Assets for PC
        $coolingSystem = Asset::create([
            'sub_area_id' => $pc->id,
            'name' => 'Cooling System',
            'code' => 'AST-COOL-001',
            'model' => 'CS-200',
            'serial_number' => 'SN-COOL-2024-001',
            'installation_date' => '2024-01-20',
            'is_active' => true,
        ]);

        // Assets for Packaging
        $sealingMachine = Asset::create([
            'sub_area_id' => $dbm->id,
            'name' => 'Sealing Machine',
            'code' => 'AST-SEAL-001',
            'model' => 'SM-150',
            'serial_number' => 'SN-SEAL-2024-001',
            'installation_date' => '2024-02-15',
            'is_active' => true,
        ]);

        $this->command->info('✅ Assets seeded: 5 assets');

        // ========================================
        // 4. SEED SUB ASSETS
        // ========================================
        $this->command->info('Seeding Sub Assets...');

        // Sub Assets for Processing
        SubAsset::create([
            'asset_id' => $processing->id,
            'name' => 'Fryer',
            'code' => 'SUB-FRY-001',
            'description' => 'Main Fryer Unit',
            'is_active' => true,
        ]);

        SubAsset::create([
            'asset_id' => $processing->id,
            'name' => 'Fryer Overview',
            'code' => 'SUB-FRY-OV-001',
            'description' => 'Fryer Overview Control Panel',
            'is_active' => true,
        ]);

        // Sub Assets for VMM
        SubAsset::create([
            'asset_id' => $vmm->id,
            'name' => 'Mixer Blade',
            'code' => 'SUB-MIX-BLD-001',
            'description' => 'Main Mixer Blade Assembly',
            'is_active' => true,
        ]);

        // Sub Assets for Extruder
        SubAsset::create([
            'asset_id' => $extruder->id,
            'name' => 'S9 Overview',
            'code' => 'SUB-S9-OV-001',
            'description' => 'S9 Section Overview Panel',
            'is_active' => true,
        ]);

        SubAsset::create([
            'asset_id' => $extruder->id,
            'name' => 'Screw Assembly',
            'code' => 'SUB-SCR-001',
            'description' => 'Main Screw Assembly',
            'is_active' => true,
        ]);

        // Sub Assets for Cooling System
        SubAsset::create([
            'asset_id' => $coolingSystem->id,
            'name' => 'Cooling Fan Unit',
            'code' => 'SUB-FAN-001',
            'description' => 'Main Cooling Fan',
            'is_active' => true,
        ]);

        $this->command->info('✅ Sub Assets seeded: 6 sub assets');

        // ========================================
        // 5. SEED PARTS (Spare Parts)
        // ========================================
        $this->command->info('Seeding Parts...');

        $parts = [
            // Electrical Parts
            [
                'part_number' => 'E-001',
                'name' => 'Motor 3 Phase 5HP',
                'description' => 'Electric motor 3 phase 5HP for main drive',
                'category' => 'Electrical',
                'unit' => 'pcs',
                'min_stock' => 2,
                'current_stock' => 5,
                'unit_price' => 2500000,
                'location' => 'Warehouse A - Shelf E1',
            ],
            [
                'part_number' => 'E-002',
                'name' => 'Contactor 25A',
                'description' => 'Magnetic contactor 25A',
                'category' => 'Electrical',
                'unit' => 'pcs',
                'min_stock' => 5,
                'current_stock' => 8,
                'unit_price' => 350000,
                'location' => 'Warehouse A - Shelf E2',
            ],
            [
                'part_number' => 'E-003',
                'name' => 'Proximity Sensor',
                'description' => 'Inductive proximity sensor 12-24VDC',
                'category' => 'Electrical',
                'unit' => 'pcs',
                'min_stock' => 10,
                'current_stock' => 3,
                'unit_price' => 125000,
                'location' => 'Warehouse A - Shelf E3',
            ],

            // Mechanical Parts
            [
                'part_number' => 'M-001',
                'name' => 'Bearing 6205',
                'description' => 'Deep groove ball bearing 6205',
                'category' => 'Mechanical',
                'unit' => 'pcs',
                'min_stock' => 20,
                'current_stock' => 25,
                'unit_price' => 45000,
                'location' => 'Warehouse B - Shelf M1',
            ],
            [
                'part_number' => 'M-002',
                'name' => 'V-Belt A-54',
                'description' => 'V-Belt type A size 54',
                'category' => 'Mechanical',
                'unit' => 'pcs',
                'min_stock' => 10,
                'current_stock' => 12,
                'unit_price' => 75000,
                'location' => 'Warehouse B - Shelf M2',
            ],
            [
                'part_number' => 'M-003',
                'name' => 'Chain RS-40',
                'description' => 'Roller chain RS-40 1 meter',
                'category' => 'Mechanical',
                'unit' => 'meter',
                'min_stock' => 5,
                'current_stock' => 8,
                'unit_price' => 150000,
                'location' => 'Warehouse B - Shelf M3',
            ],
            [
                'part_number' => 'M-004',
                'name' => 'Coupling Flexible 50mm',
                'description' => 'Flexible coupling 50mm diameter',
                'category' => 'Mechanical',
                'unit' => 'pcs',
                'min_stock' => 3,
                'current_stock' => 1,
                'unit_price' => 450000,
                'location' => 'Warehouse B - Shelf M4',
            ],

            // Consumable Parts
            [
                'part_number' => 'C-001',
                'name' => 'Lubricant Oil SAE 40',
                'description' => 'Industrial lubricant oil SAE 40',
                'category' => 'Consumable',
                'unit' => 'liter',
                'min_stock' => 50,
                'current_stock' => 45,
                'unit_price' => 35000,
                'location' => 'Warehouse C - Shelf C1',
            ],
            [
                'part_number' => 'C-002',
                'name' => 'Grease NLGI 2',
                'description' => 'Multi-purpose grease NLGI grade 2',
                'category' => 'Consumable',
                'unit' => 'kg',
                'min_stock' => 20,
                'current_stock' => 15,
                'unit_price' => 45000,
                'location' => 'Warehouse C - Shelf C2',
            ],
            [
                'part_number' => 'C-003',
                'name' => 'Filter Air Compressed',
                'description' => 'Air compressor filter cartridge',
                'category' => 'Consumable',
                'unit' => 'pcs',
                'min_stock' => 5,
                'current_stock' => 0,
                'unit_price' => 250000,
                'location' => 'Warehouse C - Shelf C3',
            ],

            // Hydraulic Parts
            [
                'part_number' => 'H-001',
                'name' => 'Hydraulic Pump',
                'description' => 'Hydraulic gear pump 10cc',
                'category' => 'Hydraulic',
                'unit' => 'pcs',
                'min_stock' => 1,
                'current_stock' => 2,
                'unit_price' => 3500000,
                'location' => 'Warehouse D - Shelf H1',
            ],
            [
                'part_number' => 'H-002',
                'name' => 'Hydraulic Hose 1/2"',
                'description' => 'Hydraulic hose 1/2 inch per meter',
                'category' => 'Hydraulic',
                'unit' => 'meter',
                'min_stock' => 10,
                'current_stock' => 12,
                'unit_price' => 85000,
                'location' => 'Warehouse D - Shelf H2',
            ],

            // Pneumatic Parts
            [
                'part_number' => 'P-001',
                'name' => 'Pneumatic Cylinder 63x100',
                'description' => 'Pneumatic cylinder 63mm bore 100mm stroke',
                'category' => 'Pneumatic',
                'unit' => 'pcs',
                'min_stock' => 2,
                'current_stock' => 3,
                'unit_price' => 850000,
                'location' => 'Warehouse E - Shelf P1',
            ],
            [
                'part_number' => 'P-002',
                'name' => 'Solenoid Valve 5/2',
                'description' => 'Pneumatic solenoid valve 5/2 way',
                'category' => 'Pneumatic',
                'unit' => 'pcs',
                'min_stock' => 5,
                'current_stock' => 6,
                'unit_price' => 425000,
                'location' => 'Warehouse E - Shelf P2',
            ],
        ];

        foreach ($parts as $partData) {
            Part::create($partData);
        }

        $this->command->info('✅ Parts seeded: ' . count($parts) . ' parts');

        // ========================================
        // SUMMARY
        // ========================================
        $this->command->info('');
        $this->command->info('=== MASTER DATA SEEDING SUMMARY ===');
        $this->command->info('✅ Areas: 3 (Proses, Packaging, Utility)');
        $this->command->info('✅ Sub Areas: 5 (EP, PC, TC, DBM, LBCSS)');
        $this->command->info('✅ Assets: 5 (Processing, VMM, EXTRUDER, Cooling, Sealing)');
        $this->command->info('✅ Sub Assets: 6');
        $this->command->info('✅ Parts: ' . count($parts) . ' parts');
        $this->command->info('');
        $this->command->info('⚠️  Low Stock Alerts:');
        $this->command->info('   - E-003 (Proximity Sensor): 3 units (min: 10)');
        $this->command->info('   - M-004 (Coupling): 1 unit (min: 3)');
        $this->command->info('   - C-003 (Filter): 0 units (min: 5) - OUT OF STOCK!');
    }
}
