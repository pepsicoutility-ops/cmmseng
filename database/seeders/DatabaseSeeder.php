<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('╔════════════════════════════════════════════════╗');
        $this->command->info('║   CMMS Database Seeding Started...            ║');
        $this->command->info('╚════════════════════════════════════════════════╝');
        $this->command->info('');

        // Seed in order of dependencies
        $this->call([
            UserSeeder::class,
            MasterDataSeeder::class,
            BarcodeTokenSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('╔════════════════════════════════════════════════╗');
        $this->command->info('║   ✅ CMMS Database Seeding Completed!         ║');
        $this->command->info('╚════════════════════════════════════════════════╝');
        $this->command->info('');
        $this->command->info('📝 Next Steps:');
        $this->command->info('   1. Login to Filament with any seeded user');
        $this->command->info('   2. Default password: password');
        $this->command->info('   3. Super Admin: superadmin@cmms.com');
        $this->command->info('   4. Manager: manager@cmms.com');
        $this->command->info('   5. Check barcode token in Filament panel');
        $this->command->info('');
    }
}
