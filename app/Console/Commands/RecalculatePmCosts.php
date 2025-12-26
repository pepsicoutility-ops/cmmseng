<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use App\Models\PmExecution;
use App\Services\PmService;

class RecalculatePmCosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pm:recalculate-costs {--all : Recalculate all PM costs, not just missing ones}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate PM execution costs for completed PMs that are missing cost data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pmService = app(PmService::class);
        
        // Get completed PM executions
        $query = PmExecution::where('status', 'completed')
            ->whereNotNull('actual_end');
        
        if (!$this->option('all')) {
            // Only get PMs without cost records
            $query->doesntHave('cost');
        }
        
        $executions = $query->get();
        
        if ($executions->isEmpty()) {
            $this->info('No PM executions found that need cost calculation.');
            return 0;
        }
        
        $this->info("Found {$executions->count()} PM execution(s) to process...");
        
        $progressBar = $this->output->createProgressBar($executions->count());
        $progressBar->start();
        
        $calculated = 0;
        $failed = 0;
        
        foreach ($executions as $execution) {
            try {
                // Ensure duration is calculated
                if (!$execution->duration && $execution->actual_start && $execution->actual_end) {
                    $duration = $execution->actual_start->diffInMinutes($execution->actual_end);
                    $execution->update(['duration' => $duration]);
                }
                
                // Calculate cost
                $pmService->calculateCost($execution);
                $calculated++;
            } catch (Exception $e) {
                $this->error("\nFailed to calculate cost for PM Execution ID {$execution->id}: {$e->getMessage()}");
                $failed++;
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine(2);
        
        $this->info("✓ Successfully calculated costs for {$calculated} PM execution(s)");
        
        if ($failed > 0) {
            $this->warn("✗ Failed to calculate costs for {$failed} PM execution(s)");
        }
        
        return 0;
    }
}
