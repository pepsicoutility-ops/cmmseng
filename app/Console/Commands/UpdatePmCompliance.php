<?php

namespace App\Console\Commands;

use App\Services\ComplianceService;
use Illuminate\Console\Command;

class UpdatePmCompliance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cmms:update-compliance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update PM compliance tracking (weekly and monthly)';

    /**
     * Execute the console command.
     */
    public function handle(ComplianceService $complianceService): int
    {
        $this->info('Updating PM compliance...');
        
        // Update weekly compliance
        $complianceService->updatePmCompliance('week');
        $this->line('✓ Weekly compliance updated');
        
        // Update monthly compliance
        $complianceService->updatePmCompliance('month');
        $this->line('✓ Monthly compliance updated');
        
        $this->info('PM Compliance updated successfully!');
        
        return Command::SUCCESS;
    }
}
