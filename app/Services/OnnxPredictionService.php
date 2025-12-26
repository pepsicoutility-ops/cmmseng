<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OnnxPredictionService
{
    protected string $apiUrl;
    protected int $timeout;

    public function __construct()
    {
        $this->apiUrl = config('cmms.onnx_api_url', 'http://localhost:5000/predict');
        $this->timeout = config('cmms.onnx_timeout', 30);
    }

    /**
     * Predict anomaly for Chiller equipment
     */
    public function predictChiller(array $data, string $chillerType = 'chiller1'): array
    {
        $features = [
            'sat_evap_t' => $data['sat_evap_t'] ?? 0,
            'sat_dis_t' => $data['sat_dis_t'] ?? 0,
            'dis_superheat' => $data['dis_superheat'] ?? 0,
            'lcl' => $data['lcl'] ?? 0,
            'fla' => $data['fla'] ?? 0,
            'evap_p' => $data['evap_p'] ?? 0,
            'conds_p' => $data['conds_p'] ?? 0,
            'motor_amps' => $data['motor_amps'] ?? 0,
            'motor_volts' => $data['motor_volts'] ?? 0,
            'cooler_chorus_small_temp_diff' => $data['cooler_chorus_small_temp_diff'] ?? 0,
            'cond_reff_small_temp_diff' => $data['cond_reff_small_temp_diff'] ?? 0,
        ];

        return $this->callOnnxApi($features, $chillerType);
    }

    /**
     * Predict anomaly for Compressor equipment
     */
    public function predictCompressor(array $data, string $compressorType = 'compressor1'): array
    {
        $features = [
            'bearing_oil_temperature' => $data['bearing_oil_temperature'] ?? 0,
            'bearing_oil_pressure' => $data['bearing_oil_pressure'] ?? 0,
            'discharge_pressure' => $data['discharge_pressure'] ?? 0,
            'discharge_temperature' => $data['discharge_temperature'] ?? 0,
            'cws_temperature' => $data['cws_temperature'] ?? 0,
            'cwr_temperature' => $data['cwr_temperature'] ?? 0,
            'refrigerant_pressure' => $data['refrigerant_pressure'] ?? 0,
            'dew_point' => $data['dew_point'] ?? 0,
        ];

        return $this->callOnnxApi($features, $compressorType);
    }

    /**
     * Predict anomaly for AHU equipment
     */
    public function predictAHU(array $data): array
    {
        // Aggregate filter data for prediction
        $features = [
            'total_pf' => $this->sumFilters($data, 'pf'),
            'total_mf' => $this->sumFilters($data, 'mf'),
            'total_hf' => $this->sumFilters($data, 'hf'),
            'pf_ratio' => $this->calculateFilterRatio($data, 'pf'),
            'mf_ratio' => $this->calculateFilterRatio($data, 'mf'),
            'hf_ratio' => $this->calculateFilterRatio($data, 'hf'),
        ];

        return $this->callOnnxApi($features, 'ahu');
    }

    /**
     * Call ONNX API endpoint
     */
    protected function callOnnxApi(array $features, string $equipmentType): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post($this->apiUrl, [
                    'equipment_type' => $equipmentType,
                    'features' => $features,
                ]);

            if ($response->successful()) {
                $result = $response->json();
                
                return [
                    'success' => true,
                    'is_anomaly' => $result['is_anomaly'] ?? false,
                    'risk_signal' => $result['risk_signal'] ?? 'low',
                    'raw_label' => $result['raw_label'] ?? 'normal',
                    'confidence_score' => $result['confidence_score'] ?? 0,
                    'feature_importance' => $result['feature_importance'] ?? [],
                ];
            }

            // API call failed
            Log::warning("ONNX API call failed for {$equipmentType}", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return $this->getDefaultPrediction();

        } catch (Exception $e) {
            Log::error("ONNX prediction error for {$equipmentType}: " . $e->getMessage());
            return $this->getDefaultPrediction();
        }
    }

    /**
     * Sum filter values for AHU
     */
    protected function sumFilters(array $data, string $filterType): int
    {
        $total = 0;
        foreach ($data as $key => $value) {
            if (str_contains($key, $filterType) && is_numeric($value)) {
                $total += (int) $value;
            }
        }
        return $total;
    }

    /**
     * Calculate filter ratio (dirty vs total)
     */
    protected function calculateFilterRatio(array $data, string $filterType): float
    {
        $total = $this->sumFilters($data, $filterType);
        return $total > 0 ? ($total / 18) : 0; // Assume max 18 filters per type
    }

    /**
     * Default prediction when API is unavailable
     */
    protected function getDefaultPrediction(): array
    {
        return [
            'success' => false,
            'is_anomaly' => false,
            'risk_signal' => 'unknown',
            'raw_label' => 'no_prediction',
            'confidence_score' => 0,
            'feature_importance' => [],
        ];
    }
}
