<?php

namespace App\Console\Commands;

use Exception;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ImportEmployees extends Command
{
    protected $signature = 'import:employees {file?}';
    protected $description = 'Import employees from CSV file';

    public function handle()
    {
        $filePath = $this->argument('file') ?? public_path('storage/Employee Data.csv');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $this->info("Reading CSV file: {$filePath}");

        $file = fopen($filePath, 'r');
        $header = fgetcsv($file); // Skip header row

        $imported = 0;
        $skipped = 0;
        $errors = [];

        while (($row = fgetcsv($file)) !== false) {
            // Map CSV columns: gpid, name, email, role, department, password
            $data = [
                'gpid' => $row[0] ?? null,
                'name' => $row[1] ?? null,
                'email' => $row[2] ?? null,
                'role' => $row[3] ?? 'operator',
                'department' => $row[4] ?? null,
                'password' => $row[5] ?? null,
            ];

            // Fix typo in CSV: assisten_manager -> asisten_manager
            if ($data['role'] === 'assisten_manager') {
                $data['role'] = 'asisten_manager';
            }

            // Skip if essential fields are missing
            if (empty($data['gpid']) || empty($data['name']) || empty($data['email'])) {
                $skipped++;
                $this->warn("Skipped row with missing data: " . json_encode($data));
                continue;
            }

            // Check if user already exists
            $existingUser = User::where('gpid', $data['gpid'])
                ->orWhere('email', $data['email'])
                ->first();

            if ($existingUser) {
                $skipped++;
                $this->warn("User already exists: {$data['gpid']} - {$data['name']}");
                continue;
            }

            try {
                // Create user
                User::create([
                    'gpid' => $data['gpid'],
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'role' => $data['role'],
                    'department' => !empty($data['department']) ? $data['department'] : null,
                    'password' => !empty($data['password']) ? Hash::make($data['password']) : Hash::make('Cmms@2025'),
                    'email_verified_at' => now(),
                ]);

                $imported++;
                $this->info("✓ Imported: {$data['gpid']} - {$data['name']} ({$data['role']})");
            } catch (Exception $e) {
                $skipped++;
                $error = "Failed to import {$data['gpid']} - {$data['name']}: " . $e->getMessage();
                $errors[] = $error;
                $this->error($error);
            }
        }

        fclose($file);

        // Summary
        $this->newLine();
        $this->info("========================================");
        $this->info("Import Summary:");
        $this->info("Successfully imported: {$imported} users");
        $this->warn("Skipped: {$skipped} users");
        
        if (!empty($errors)) {
            $this->error("Errors encountered: " . count($errors));
            foreach ($errors as $error) {
                $this->error("  - {$error}");
            }
        }
        
        $this->info("========================================");

        return 0;
    }
}
