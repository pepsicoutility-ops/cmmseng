<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            // Super Admin
            [
                'gpid' => 'SA001',
                'name' => 'Super Admin',
                'email' => 'superadmin@cmms.com',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'department' => null,
                'phone' => '08123456789',
                'is_active' => true,
            ],
            
            // Manager
            [
                'gpid' => 'MGR001',
                'name' => 'Manager CMMS',
                'email' => 'manager@cmms.com',
                'password' => Hash::make('password'),
                'role' => 'manager',
                'department' => null,
                'phone' => '08123456790',
                'is_active' => true,
            ],
            
            // Asisten Manager - Mechanic
            [
                'gpid' => 'ASM001',
                'name' => 'Asisten Manager Mechanic',
                'email' => 'asmanmechanic@cmms.com',
                'password' => Hash::make('password'),
                'role' => 'asisten_manager',
                'department' => 'mechanic',
                'phone' => '08123456791',
                'is_active' => true,
            ],
            
            // Asisten Manager - Electric
            [
                'gpid' => 'ASE001',
                'name' => 'Asisten Manager Electric',
                'email' => 'asmanelectric@cmms.com',
                'password' => Hash::make('password'),
                'role' => 'asisten_manager',
                'department' => 'electric',
                'phone' => '08123456792',
                'is_active' => true,
            ],
            
            // Asisten Manager - Utility
            [
                'gpid' => 'ASU001',
                'name' => 'Asisten Manager Utility',
                'email' => 'asmanutility@cmms.com',
                'password' => Hash::make('password'),
                'role' => 'asisten_manager',
                'department' => 'utility',
                'phone' => '08123456793',
                'is_active' => true,
            ],
            
            // Technician - Mechanic
            [
                'gpid' => 'TCM001',
                'name' => 'Technician Mechanic 1',
                'email' => 'techmechanic1@cmms.com',
                'password' => Hash::make('password'),
                'role' => 'technician',
                'department' => 'mechanic',
                'phone' => '08123456794',
                'is_active' => true,
            ],
            [
                'gpid' => 'TCM002',
                'name' => 'Technician Mechanic 2',
                'email' => 'techmechanic2@cmms.com',
                'password' => Hash::make('password'),
                'role' => 'technician',
                'department' => 'mechanic',
                'phone' => '08123456795',
                'is_active' => true,
            ],
            
            // Technician - Electric
            [
                'gpid' => 'TCE001',
                'name' => 'Technician Electric 1',
                'email' => 'techelectric1@cmms.com',
                'password' => Hash::make('password'),
                'role' => 'technician',
                'department' => 'electric',
                'phone' => '08123456796',
                'is_active' => true,
            ],
            [
                'gpid' => 'TCE002',
                'name' => 'Technician Electric 2',
                'email' => 'techelectric2@cmms.com',
                'password' => Hash::make('password'),
                'role' => 'technician',
                'department' => 'electric',
                'phone' => '08123456797',
                'is_active' => true,
            ],
            
            // Technician - Utility
            [
                'gpid' => 'TCU001',
                'name' => 'Technician Utility 1',
                'email' => 'techutility1@cmms.com',
                'password' => Hash::make('password'),
                'role' => 'technician',
                'department' => 'utility',
                'phone' => '08123456798',
                'is_active' => true,
            ],
            [
                'gpid' => 'TCU002',
                'name' => 'Technician Utility 2',
                'email' => 'techutility2@cmms.com',
                'password' => Hash::make('password'),
                'role' => 'technician',
                'department' => 'utility',
                'phone' => '08123456799',
                'is_active' => true,
            ],
            
            // Tech Store
            [
                'gpid' => 'TS001',
                'name' => 'Tech Store Admin',
                'email' => 'techstore@cmms.com',
                'password' => Hash::make('password'),
                'role' => 'tech_store',
                'department' => null,
                'phone' => '08123456800',
                'is_active' => true,
            ],
            
            // Operators (untuk testing barcode)
            [
                'gpid' => 'OP001',
                'name' => 'Operator Shift 1',
                'email' => 'operator1@cmms.com',
                'password' => Hash::make('password'),
                'role' => 'operator',
                'department' => null,
                'phone' => '08123456801',
                'is_active' => true,
            ],
            [
                'gpid' => 'OP002',
                'name' => 'Operator Shift 2',
                'email' => 'operator2@cmms.com',
                'password' => Hash::make('password'),
                'role' => 'operator',
                'department' => null,
                'phone' => '08123456802',
                'is_active' => true,
            ],
        ];

        foreach ($users as $userData) {
            User::create($userData);
        }

        $this->command->info('✅ Users seeded successfully! Total: ' . count($users) . ' users');
        $this->command->info('Default password for all users: password');
    }
}
