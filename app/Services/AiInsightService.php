<?php

namespace App\Services;

use Exception;
use OpenAI;
use Illuminate\Support\Facades\Log;

class AiInsightService
{
    protected $client;

    public function __construct()
    {
        $this->client = OpenAI::client(config('services.openai.api_key'));
    }

    /**
     * Generate AI insights for equipment prediction
     */
    public function generateInsight(
        string $equipmentType,
        array $checklistData,
        array $predictionData
    ): array {
        try {
            $prompt = $this->buildPrompt($equipmentType, $checklistData, $predictionData);
            
            $response = $this->client->chat()->create([
                'model' => config('cmms.openai_model', 'gpt-4'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $this->getSystemPrompt()
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.3, // Lower for more factual responses
                'max_tokens' => 1000,
            ]);

            $content = $response->choices[0]->message->content;
            
            return $this->parseAiResponse($content);

        } catch (Exception $e) {
            Log::error("OpenAI insight error for {$equipmentType}: " . $e->getMessage());
            
            return $this->getDefaultInsight($predictionData);
        }
    }

    /**
     * Build analysis prompt for OpenAI
     */
    protected function buildPrompt(string $equipmentType, array $data, array $prediction): string
    {
        $equipmentName = ucfirst(str_replace(['_', 'compressor', 'chiller'], [' ', 'Compressor', 'Chiller'], $equipmentType));
        
        $prompt = "Analyze this {$equipmentName} equipment data:\n\n";
        $prompt .= "**ML Prediction Results:**\n";
        $prompt .= "- Anomaly Detected: " . ($prediction['is_anomaly'] ? 'YES' : 'NO') . "\n";
        $prompt .= "- Risk Signal: " . strtoupper($prediction['risk_signal']) . "\n";
        $prompt .= "- Confidence: " . ($prediction['confidence_score'] ?? 0) . "%\n\n";
        
        $prompt .= "**Current Readings:**\n";
        foreach ($data as $key => $value) {
            if (is_numeric($value) && $value != 0) {
                $label = ucwords(str_replace('_', ' ', $key));
                $prompt .= "- {$label}: {$value}\n";
            }
        }
        
        if (!empty($prediction['feature_importance'])) {
            $prompt .= "\n**Contributing Factors (Feature Importance):**\n";
            foreach ($prediction['feature_importance'] as $feature => $importance) {
                $prompt .= "- {$feature}: " . round($importance * 100, 1) . "%\n";
            }
        }
        
        $prompt .= "\nProvide analysis in this exact format:\n\n";
        $prompt .= "ROOT_CAUSE:\n[Explain the most likely root cause based on the data]\n\n";
        $prompt .= "RECOMMENDATIONS:\n[List 3-5 specific technical actions to take]\n\n";
        $prompt .= "SEVERITY:\n[Choose: normal, warning, or critical]\n\n";
        $prompt .= "PRIORITY:\n[Rate urgency from 1-10]";
        
        return $prompt;
    }

    /**
     * System prompt for consistent AI behavior
     */
    protected function getSystemPrompt(): string
    {
        return "You are an expert industrial maintenance engineer specializing in HVAC and refrigeration systems. " .
               "Analyze equipment data and provide concise, actionable insights. " .
               "Focus on: 1) Root cause identification, 2) Preventive actions, 3) Risk assessment. " .
               "Be specific with technical recommendations. Use industry-standard terminology. " .
               "Keep responses factual and data-driven.";
    }

    /**
     * Parse AI response into structured data
     */
    protected function parseAiResponse(string $content): array
    {
        $lines = explode("\n", $content);
        $result = [
            'root_cause' => '',
            'technical_recommendations' => '',
            'severity_level' => 'normal',
            'equipment_priority' => 5,
        ];

        $currentSection = null;
        $buffer = '';

        foreach ($lines as $line) {
            $line = trim($line);
            
            if (str_starts_with($line, 'ROOT_CAUSE:')) {
                if ($buffer && $currentSection) {
                    $result[$currentSection] = trim($buffer);
                }
                $currentSection = 'root_cause';
                $buffer = '';
            } elseif (str_starts_with($line, 'RECOMMENDATIONS:')) {
                if ($buffer && $currentSection) {
                    $result[$currentSection] = trim($buffer);
                }
                $currentSection = 'technical_recommendations';
                $buffer = '';
            } elseif (str_starts_with($line, 'SEVERITY:')) {
                if ($buffer && $currentSection) {
                    $result[$currentSection] = trim($buffer);
                }
                $currentSection = 'severity_level';
                $buffer = '';
            } elseif (str_starts_with($line, 'PRIORITY:')) {
                if ($buffer && $currentSection) {
                    $result[$currentSection] = trim($buffer);
                }
                $currentSection = 'equipment_priority';
                $buffer = '';
            } elseif ($line && !str_starts_with($line, '#')) {
                $buffer .= $line . "\n";
            }
        }

        // Capture last section
        if ($buffer && $currentSection) {
            if ($currentSection === 'equipment_priority') {
                // Extract number from priority text
                preg_match('/(\d+)/', $buffer, $matches);
                $result[$currentSection] = isset($matches[1]) ? (int) $matches[1] : 5;
            } else {
                $result[$currentSection] = trim($buffer);
            }
        }

        // Normalize severity level
        $severityText = strtolower($result['severity_level']);
        if (str_contains($severityText, 'critical')) {
            $result['severity_level'] = 'critical';
        } elseif (str_contains($severityText, 'warning')) {
            $result['severity_level'] = 'warning';
        } else {
            $result['severity_level'] = 'normal';
        }

        // Ensure priority is within range
        $result['equipment_priority'] = max(1, min(10, $result['equipment_priority']));

        return $result;
    }

    /**
     * Default insight when AI is unavailable
     */
    protected function getDefaultInsight(array $prediction): array
    {
        $severity = 'normal';
        $priority = 5;

        if ($prediction['is_anomaly'] ?? false) {
            $riskLevel = $prediction['risk_signal'] ?? 'low';
            
            $severity = match($riskLevel) {
                'critical' => 'critical',
                'high' => 'warning',
                default => 'normal',
            };

            $priority = match($riskLevel) {
                'critical' => 9,
                'high' => 7,
                'medium' => 5,
                default => 3,
            };
        }

        return [
            'root_cause' => 'AI insight service temporarily unavailable. Manual inspection recommended.',
            'technical_recommendations' => '1. Monitor equipment readings closely\n2. Check for visible signs of wear\n3. Consult maintenance logs',
            'severity_level' => $severity,
            'equipment_priority' => $priority,
        ];
    }
}
