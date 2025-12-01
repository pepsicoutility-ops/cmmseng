<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PmSchedule;
use App\Models\User;
use App\Services\TelegramService;
use Filament\Notifications\Notification;

class SendOverduePmAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cmms:send-overdue-pm-alerts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send alerts to asisten managers for overdue PMs';

    protected $telegram;

    public function __construct(TelegramService $telegram)
    {
        parent::__construct();
        $this->telegram = $telegram;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get overdue PMs (past schedule date and still pending)
        $overduePms = PmSchedule::where('schedule_date', '<', now()->startOfDay())
            ->where('status', 'pending')
            ->with(['asset', 'assignedUser'])
            ->get();

        $count = 0;
        foreach ($overduePms as $pm) {
            $daysOverdue = now()->startOfDay()->diffInDays($pm->schedule_date);
            
            // Send Telegram notification
            $this->telegram->sendOverduePmAlert([
                'pm_code' => $pm->pm_code,
                'title' => $pm->title,
                'equipment' => $pm->asset?->name ?? 'N/A',
                'assigned_name' => $pm->assignedUser?->name ?? 'Unassigned',
                'schedule_date' => $pm->schedule_date->format('Y-m-d'),
                'days_overdue' => $daysOverdue
            ]);

            // Notify asisten managers of the same department
            $department = $pm->asset?->department;
            if ($department) {
                $asistenManagers = User::where('role', 'asisten_manager')
                    ->where('department', $department)
                    ->get();

                foreach ($asistenManagers as $manager) {
                    Notification::make()
                        ->title('Overdue PM Alert')
                        ->body("PM {$pm->pm_code} is overdue by {$daysOverdue} days. Equipment: {$pm->asset?->name}")
                        ->danger()
                        ->icon('heroicon-o-exclamation-circle')
                        ->sendToDatabase($manager);
                }
            }

            $count++;
        }

        $this->info("Sent {$count} overdue PM alerts.");
        return Command::SUCCESS;
    }
}
