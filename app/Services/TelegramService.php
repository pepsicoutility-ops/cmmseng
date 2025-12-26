<?php

namespace App\Services;

use Exception;
use Telegram\Bot\Api;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected $telegram;
    protected $chatId;
    protected $enabled;

    public function __construct()
    {
        $this->enabled = false;
        
        try {
            $token = env('TELEGRAM_BOT_TOKEN');
            $this->chatId = env('TELEGRAM_CHAT_ID');
            
            if ($token && $this->chatId) {
                $this->telegram = new Api($token);
                $this->enabled = true;
            } else {
                Log::info('Telegram service disabled: Missing TELEGRAM_BOT_TOKEN or TELEGRAM_CHAT_ID');
            }
        } catch (Exception $e) {
            Log::error('Telegram initialization failed: ' . $e->getMessage());
            $this->enabled = false;
        }
    }

    /**
     * Send a text message to Telegram
     */
    public function sendMessage(string $message, ?string $chatId = null): bool
    {
        if (!$this->enabled) {
            return false;
        }
        
        try {
            $this->telegram->sendMessage([
                'chat_id' => $chatId ?? $this->chatId,
                'text' => $message,
                'parse_mode' => 'HTML'
            ]);
            
            return true;
        } catch (Exception $e) {
            Log::error('Telegram send message failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send stock alert notification
     */
    public function sendStockAlert(array $partData): bool
    {
        if (!$this->enabled) {
            return false;
        }
        
        $message = "🚨 <b>STOCK ALERT</b> 🚨\n\n";
        $message .= "📦 <b>Part:</b> {$partData['name']}\n";
        $message .= "🔢 <b>Part Number:</b> {$partData['part_number']}\n";
        $message .= "📊 <b>Current Stock:</b> {$partData['current_stock']} {$partData['unit']}\n";
        $message .= "⚠️ <b>Min Stock:</b> {$partData['min_stock']} {$partData['unit']}\n";
        $message .= "📍 <b>Location:</b> {$partData['location']}\n\n";
        $message .= "⏰ Time: " . now()->format('Y-m-d H:i:s');

        return $this->sendMessage($message);
    }

    /**
     * Send work order notification
     */
    public function sendWoNotification(array $woData): bool
    {
        if (!$this->enabled) {
            return false;
        }
        
        $statusEmoji = [
            'submitted' => '📝',
            'review' => '👀',
            'approved' => '✅',
            'in_progress' => '⚙️',
            'completed' => '✔️',
            'on_hold' => '⏸️',
            'cancelled' => '❌'
        ];

        $emoji = $statusEmoji[$woData['status']] ?? '📋';

        $message = "{$emoji} <b>WORK ORDER UPDATE</b> {$emoji}\n\n";
        $message .= "🔖 <b>WO Number:</b> {$woData['wo_number']}\n";
        $message .= "👤 <b>Operator:</b> {$woData['operator_name']}\n";
        $message .= "🏭 <b>Equipment:</b> {$woData['equipment']}\n";
        $message .= "⚡ <b>Problem:</b> {$woData['problem_type']}\n";
        $message .= "📊 <b>Status:</b> " . strtoupper($woData['status']) . "\n";
        
        if (isset($woData['assign_to'])) {
            $message .= "🔧 <b>Assigned To:</b> {$woData['assign_to']}\n";
        }
        
        $message .= "\n⏰ Time: " . now()->format('Y-m-d H:i:s');

        return $this->sendMessage($message);
    }

    /**
     * Send PM reminder notification
     */
    public function sendPmReminder(array $pmData): bool
    {
        $priorityEmoji = [
            'critical' => '🔴',
            'high' => '🟠',
            'medium' => '🟡',
            'low' => '🟢'
        ];

        $emoji = $priorityEmoji[$pmData['priority']] ?? '📅';

        $message = "{$emoji} <b>PM REMINDER</b> {$emoji}\n\n";
        $message .= "📋 <b>PM Code:</b> {$pmData['pm_code']}\n";
        $message .= "📝 <b>Title:</b> {$pmData['title']}\n";
        $message .= "🏭 <b>Equipment:</b> {$pmData['equipment']}\n";
        $message .= "👤 <b>Assigned To:</b> {$pmData['assigned_name']}\n";
        $message .= "📅 <b>Schedule Date:</b> {$pmData['schedule_date']}\n";
        $message .= "⚠️ <b>Priority:</b> " . strtoupper($pmData['priority']) . "\n\n";
        $message .= "⏰ Time: " . now()->format('Y-m-d H:i:s');

        return $this->sendMessage($message);
    }

    /**
     * Send overdue PM alert
     */
    public function sendOverduePmAlert(array $pmData): bool
    {
        $message = "🚨 <b>OVERDUE PM ALERT</b> 🚨\n\n";
        $message .= "📋 <b>PM Code:</b> {$pmData['pm_code']}\n";
        $message .= "📝 <b>Title:</b> {$pmData['title']}\n";
        $message .= "🏭 <b>Equipment:</b> {$pmData['equipment']}\n";
        $message .= "👤 <b>Assigned To:</b> {$pmData['assigned_name']}\n";
        $message .= "📅 <b>Schedule Date:</b> {$pmData['schedule_date']}\n";
        $message .= "⏰ <b>Days Overdue:</b> {$pmData['days_overdue']}\n\n";
        $message .= "⚠️ <b>Action Required!</b>\n";
        $message .= "Time: " . now()->format('Y-m-d H:i:s');

        return $this->sendMessage($message);
    }
}
