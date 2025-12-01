<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PmSchedule;
use App\Models\User;
use App\Services\TelegramService;
use Filament\Notifications\Notification;

class SendPmReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cmms:send-pm-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send PM reminders to technicians 1 day before scheduled date';

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
        // Get PMs scheduled for tomorrow that are still pending
        $tomorrow = now()->addDay()->startOfDay();
        $pmSchedules = PmSchedule::where('schedule_date', '>=', $tomorrow)
            ->where('schedule_date', '<', $tomorrow->copy()->endOfDay())
            ->where('status', 'pending')
            ->with(['asset', 'assignedUser'])
            ->get();

        $count = 0;
        foreach ($pmSchedules as $pm) {
            // Send Telegram notification
            $this->telegram->sendPmReminder([
                'pm_code' => $pm->pm_code,
                'title' => $pm->title,
                'equipment' => $pm->asset?->name ?? 'N/A',
                'assigned_name' => $pm->assignedUser?->name ?? 'Unassigned',
                'schedule_date' => $pm->schedule_date->format('Y-m-d'),
                'priority' => $pm->priority
            ]);

            // Send in-app notification to assigned technician
            if ($pm->assignedUser) {
                Notification::make()
                    ->title('PM Reminder: Tomorrow')
                    ->body("PM {$pm->pm_code} - {$pm->title} is scheduled for tomorrow ({$pm->schedule_date->format('Y-m-d')})")
                    ->warning()
                    ->icon('heroicon-o-calendar')
                    ->sendToDatabase($pm->assignedUser);
            }

            $count++;
        }

        $this->info("Sent {$count} PM reminders.");
        return Command::SUCCESS;
    }
}
