<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class TestTelegramNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:test {type? : Type of notification to test (stock, pm-reminder, pm-overdue, wo, all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Telegram notifications for stock alerts, PM reminders, PM overdue, and work orders';

    protected $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        parent::__construct();
        $this->telegramService = $telegramService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type') ?? 'all';

        // Check if Telegram credentials are configured
        if (empty(env('TELEGRAM_BOT_TOKEN')) || empty(env('TELEGRAM_CHAT_ID'))) {
            $this->error('❌ Telegram credentials not configured!');
            $this->info('Please set TELEGRAM_BOT_TOKEN and TELEGRAM_CHAT_ID in your .env file');
            return 1;
        }

        $this->info("🚀 Testing Telegram Notifications...\n");

        switch ($type) {
            case 'stock':
                $this->testStockAlert();
                break;
            case 'pm-reminder':
                $this->testPmReminder();
                break;
            case 'pm-overdue':
                $this->testPmOverdue();
                break;
            case 'wo':
                $this->testWorkOrder();
                break;
            case 'all':
            default:
                $this->testStockAlert();
                $this->newLine();
                $this->testPmReminder();
                $this->newLine();
                $this->testPmOverdue();
                $this->newLine();
                $this->testWorkOrder();
                break;
        }

        $this->newLine();
        $this->info('✅ Test notifications sent! Check your Telegram chat.');
        
        return 0;
    }

    protected function testStockAlert()
    {
        $this->info('📦 Testing Stock Alert Notification...');

        $testData = [
            'name' => 'Bearing 6205 ZZ (Test)',
            'part_number' => 'BRG-6205-ZZ',
            'current_stock' => 3,
            'min_stock' => 10,
            'unit' => 'pcs',
            'location' => 'Warehouse A - Shelf B2'
        ];

        $result = $this->telegramService->sendStockAlert($testData);

        if ($result) {
            $this->line('   ✓ Stock alert sent successfully');
        } else {
            $this->error('   ✗ Failed to send stock alert');
        }
    }

    protected function testPmReminder()
    {
        $this->info('📅 Testing PM Reminder Notification...');

        $testData = [
            'pm_code' => 'PM-2025-001-TEST',
            'title' => 'Monthly Equipment Inspection (Test)',
            'equipment' => 'CNC Machine #3',
            'assigned_name' => 'John Doe (Technician)',
            'schedule_date' => now()->addDays(2)->format('Y-m-d H:i'),
            'priority' => 'high'
        ];

        $result = $this->telegramService->sendPmReminder($testData);

        if ($result) {
            $this->line('   ✓ PM reminder sent successfully');
        } else {
            $this->error('   ✗ Failed to send PM reminder');
        }
    }

    protected function testPmOverdue()
    {
        $this->info('🚨 Testing PM Overdue Alert Notification...');

        $testData = [
            'pm_code' => 'PM-2025-002-TEST',
            'title' => 'Quarterly Maintenance Check (Test)',
            'equipment' => 'Hydraulic Press #5',
            'assigned_name' => 'Jane Smith (Technician)',
            'schedule_date' => now()->subDays(3)->format('Y-m-d H:i'),
            'days_overdue' => 3
        ];

        $result = $this->telegramService->sendOverduePmAlert($testData);

        if ($result) {
            $this->line('   ✓ PM overdue alert sent successfully');
        } else {
            $this->error('   ✗ Failed to send PM overdue alert');
        }
    }

    protected function testWorkOrder()
    {
        $this->info('⚙️ Testing Work Order Notification...');

        $testData = [
            'wo_number' => 'WO-2025-TEST-001',
            'operator_name' => 'Mike Johnson',
            'equipment' => 'Conveyor Belt System #2',
            'problem_type' => 'Belt Misalignment',
            'status' => 'submitted',
            'assign_to' => 'Tech Team A'
        ];

        $result = $this->telegramService->sendWoNotification($testData);

        if ($result) {
            $this->line('   ✓ Work order notification sent successfully');
        } else {
            $this->error('   ✗ Failed to send work order notification');
        }
    }
}
