<?php

namespace App\Filament\Imports;

use App\Models\User;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Hash;

class UserImporter extends Importer
{
    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('gpid')
                ->label('GPID')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:50', 'unique:users,gpid'])
                ->example('SA001'),
            ImportColumn::make('name')
                ->label('Full Name')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->example('John Doe'),
            ImportColumn::make('email')
                ->label('Email')
                ->rules(['nullable', 'email', 'max:255', 'unique:users,email'])
                ->example('john.doe@cmms.com'),
            ImportColumn::make('role')
                ->label('Role')
                ->rules(['nullable', 'in:super_admin,manager,asisten_manager,technician,tech_store,operator'])
                ->example('technician'),
            ImportColumn::make('department')
                ->label('Department')
                ->rules(['nullable', 'in:utility,mechanic,electric'])
                ->example('mechanic'),
            ImportColumn::make('phone')
                ->label('Phone')
                ->rules(['nullable', 'string', 'max:20'])
                ->example('08123456789'),
            ImportColumn::make('password')
                ->label('Password')
                ->rules(['nullable', 'string', 'min:8'])
                ->example('Cmms@2025'),
        ];
    }

    public function resolveRecord(): ?User
    {
        // Check if user exists by GPID
        $user = User::where('gpid', $this->data['gpid'])->first();

        if ($user) {
            // Update existing user
            return $user;
        }

        // Create new user
        return new User();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your user import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }

    protected function beforeFill(): void
    {
        // Generate email if not provided
        if (empty($this->data['email'])) {
            $this->data['email'] = strtolower($this->data['gpid']) . '@cmms.test';
        }

        // Set default role if not provided
        if (empty($this->data['role'])) {
            $this->data['role'] = 'operator';
        }

        // Fix typo: assisten_manager -> asisten_manager
        if (isset($this->data['role']) && $this->data['role'] === 'assisten_manager') {
            $this->data['role'] = 'asisten_manager';
        }

        // Set default password if not provided
        if (empty($this->data['password'])) {
            $this->data['password'] = 'Cmms@2025';
        }

        // Hash the password
        $this->data['password'] = Hash::make($this->data['password']);
    }
}
