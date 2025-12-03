<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected ?string $apiUrl;
    protected ?string $apiToken;
    protected string $session;
    protected ?string $groupId;
    protected bool $enabled;

    public function __construct()
    {
        $this->apiUrl = config('services.waha.api_url') ?? '';
        $this->apiToken = config('services.waha.api_token') ?? '';
        $this->session = config('services.waha.session', 'default');
        $this->groupId = config('services.waha.group_id') ?? '';
        $this->enabled = config('services.waha.enabled', false);
    }

    /**
     * Send a text message to WhatsApp group
     */
    public function sendMessage(string $message): bool
    {
        if (!$this->enabled) {
            Log::info('WhatsApp notifications disabled');
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Content-Type' => 'application/json',
            ])->post("{$this->apiUrl}/api/sendText", [
                'session' => $this->session,
                'chatId' => $this->groupId,
                'text' => $message,
            ]);

            if ($response->successful()) {
                Log::info('WhatsApp message sent successfully', ['response' => $response->json()]);
                return true;
            }

            Log::error('WhatsApp API error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('WhatsApp send failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Format and send compressor checklist notification
     */
    public function sendCompressorNotification(array $data, int $compressorNumber): bool
    {
        $message = "🔧 *COMPRESSOR {$compressorNumber} CHECKLIST*\n\n";
        $message .= "📅 *Shift:* {$data['shift']}\n";
        $message .= "👤 *Operator:* {$data['name']} ({$data['gpid']})\n";
        $message .= "⏱️ *Submitted:* " . now()->format('d/m/Y H:i') . "\n\n";
        
        $message .= "*📊 Operating Parameters:*\n";
        $message .= "• Total Run Hours: {$data['tot_run_hours']} hrs\n\n";
        
        $message .= "*🌡️ Temperature & Pressure:*\n";
        $message .= "• Bearing Oil Temp: {$data['bearing_oil_temperature']}°C\n";
        $message .= "• Bearing Oil Pressure: {$data['bearing_oil_pressure']} bar\n";
        $message .= "• Discharge Pressure: {$data['discharge_pressure']} bar\n";
        $message .= "• Discharge Temp: {$data['discharge_temperature']}°C\n\n";
        
        $message .= "*💧 Cooling Water System:*\n";
        $message .= "• CWS Temp: {$data['cws_temperature']}°C\n";
        $message .= "• CWR Temp: {$data['cwr_temperature']}°C\n";
        $message .= "• CWS Pressure: {$data['cws_pressure']} bar\n";
        $message .= "• CWR Pressure: {$data['cwr_pressure']} bar\n\n";
        
        $message .= "*❄️ Refrigerant System:*\n";
        $message .= "• Refrigerant Pressure: {$data['refrigerant_pressure']} bar\n";
        $message .= "• Dew Point: {$data['dew_point']}°C\n";
        
        if (!empty($data['notes'])) {
            $message .= "\n📝 *Notes:*\n{$data['notes']}";
        }
        
        $message .= "\n\n✅ Data recorded in CMMS system";

        return $this->sendMessage($message);
    }

    /**
     * Format and send chiller checklist notification
     */
    public function sendChillerNotification(array $data, int $chillerNumber): bool
    {
        $message = "❄️ *CHILLER {$chillerNumber} CHECKLIST*\n\n";
        $message .= "📅 *Shift:* {$data['shift']}\n";
        $message .= "👤 *Operator:* {$data['name']} ({$data['gpid']})\n";
        $message .= "⏱️ *Submitted:* " . now()->format('d/m/Y H:i') . "\n\n";
        
        $message .= "*🌡️ Temperature & Pressure:*\n";
        $message .= "• Sat Evap T: {$data['sat_evap_t']}°C\n";
        $message .= "• Sat Dis T: {$data['sat_dis_t']}°C\n";
        $message .= "• Discharge Superheat: {$data['dis_superheat']}°C\n";
        $message .= "• Evap Pressure: {$data['evap_p']} bar\n";
        $message .= "• Cond Pressure: {$data['conds_p']} bar\n";
        $message .= "• Oil Pressure: {$data['oil_p']} bar\n\n";
        
        $message .= "*⚡ Current & Load:*\n";
        $message .= "• LCL: {$data['lcl']}%\n";
        $message .= "• FLA: {$data['fla']} A\n";
        $message .= "• ECL: {$data['ecl']}%\n";
        $message .= "• Motor Amps: {$data['motor_amps']} A\n";
        $message .= "• Motor Volts: {$data['motor_volts']} V\n\n";
        
        $message .= "*🔧 Motor & System:*\n";
        $message .= "• Run Hours: {$data['run_hours']} hrs\n";
        $message .= "• Motor Temp: {$data['motor_t']}°C\n";
        $message .= "• Heatsink Temp: {$data['heatsink_t']}°C\n";
        $message .= "• Comp Oil Level: {$data['comp_oil_level']}\n";
        
        if (!empty($data['notes'])) {
            $message .= "\n📝 *Notes:*\n{$data['notes']}";
        }
        
        $message .= "\n\n✅ Data recorded in CMMS system";

        return $this->sendMessage($message);
    }

    /**
     * Format and send AHU checklist notification
     */
    public function sendAhuNotification(array $data): bool
    {
        $message = "🌀 *AHU CHECKLIST*\n\n";
        $message .= "📅 *Shift:* {$data['shift']}\n";
        $message .= "👤 *Operator:* {$data['name']} ({$data['gpid']})\n";
        $message .= "⏱️ *Submitted:* " . now()->format('d/m/Y H:i') . "\n\n";
        
        $message .= "*🔵 AHU MB-1:*\n";
        $message .= "• MB-1.1: HF={$data['ahu_mb_1_1_hf']}, PF={$data['ahu_mb_1_1_pf']}, MF={$data['ahu_mb_1_1_mf']}\n";
        $message .= "• MB-1.2: HF={$data['ahu_mb_1_2_hf']}, MF={$data['ahu_mb_1_2_mf']}, PF={$data['ahu_mb_1_2_pf']}\n";
        $message .= "• MB-1.3: HF={$data['ahu_mb_1_3_hf']}, MF={$data['ahu_mb_1_3_mf']}, PF={$data['ahu_mb_1_3_pf']}\n\n";
        
        $message .= "*🟢 PAU MB:*\n";
        $message .= "• PAU MB-1 PF: {$data['pau_mb_1_pf']}\n";
        $message .= "• PR-1A: HF={$data['pau_mb_pr_1a_hf']}, MF={$data['pau_mb_pr_1a_mf']}, PF={$data['pau_mb_pr_1a_pf']}\n\n";
        
        $message .= "*🔴 AHU VRF MB:*\n";
        $message .= "• MS: 1A={$data['ahu_vrf_mb_ms_1a_pf']}, 1B={$data['ahu_vrf_mb_ms_1b_pf']}, 1C={$data['ahu_vrf_mb_ms_1c_pf']}\n";
        $message .= "• SS: 1A={$data['ahu_vrf_mb_ss_1a_pf']}, 1B={$data['ahu_vrf_mb_ss_1b_pf']}, 1C={$data['ahu_vrf_mb_ss_1c_pf']}\n\n";
        
        $message .= "*🟡 Inline Filters:*\n";
        $message .= "• Pre-Filter: A={$data['if_pre_filter_a']}, B={$data['if_pre_filter_b']}\n";
        $message .= "• Medium: A={$data['if_medium_a']}, B={$data['if_medium_b']}\n";
        $message .= "• HEPA: A={$data['if_hepa_a']}, B={$data['if_hepa_b']}\n";
        
        if (!empty($data['notes'])) {
            $message .= "\n📝 *Notes:*\n{$data['notes']}";
        }
        
        $message .= "\n\n✅ Data recorded in CMMS system";

        return $this->sendMessage($message);
    }

    /**
     * Test connection to WAHA API
     */
    public function testConnection(): array
    {
        if (!$this->enabled) {
            return [
                'success' => false,
                'message' => 'WhatsApp service is disabled'
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
            ])->get("{$this->apiUrl}/api/sessions");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Connected successfully',
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Connection failed',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
