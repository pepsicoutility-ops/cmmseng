<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\AIAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Phase 3 AI Features Test Suite
 * 
 * These tests require the main database (cmmseng) with production schema.
 * Set DB_DATABASE=cmmseng in phpunit.xml for proper testing.
 */
class AIPhase3Test extends TestCase
{
    protected AIAnalyticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Force use of main database for integration tests
        config(['database.connections.mysql.database' => env('DB_DATABASE_MAIN', 'cmmseng')]);
        
        $this->service = new AIAnalyticsService();
    }

    /**
     * Test Proactive Recommendations - All Categories
     */
    public function test_get_proactive_recommendations_all(): void
    {
        $result = $this->service->getProactiveRecommendations([
            'category' => 'all',
            'urgency_level' => 'all',
            'max_recommendations' => 20,
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('summary', $result);
        
        // Verify recommendations structure if any exist
        if (count($result['recommendations']) > 0) {
            $rec = $result['recommendations'][0];
            $this->assertArrayHasKey('id', $rec);
            $this->assertArrayHasKey('category', $rec);
            $this->assertArrayHasKey('title', $rec);
            $this->assertArrayHasKey('urgency', $rec);
            $this->assertArrayHasKey('priority_score', $rec);
        }
    }

    /**
     * Test Proactive Recommendations - Filter by Category
     */
    public function test_get_proactive_recommendations_filtered(): void
    {
        $categories = ['maintenance', 'inventory', 'cost', 'safety'];
        
        foreach ($categories as $category) {
            $result = $this->service->getProactiveRecommendations([
                'category' => $category,
                'max_recommendations' => 5,
            ]);

            $this->assertIsArray($result);
            $this->assertArrayHasKey('recommendations', $result);
            
            // If recommendations exist, verify they match the filter
            if (count($result['recommendations']) > 0) {
                foreach ($result['recommendations'] as $rec) {
                    $this->assertEquals($category, $rec['category']);
                }
            }
        }
    }

    /**
     * Test What-If Simulator - PM Frequency Change
     */
    public function test_simulate_pm_frequency_change(): void
    {
        $result = $this->service->simulateScenario([
            'scenario_type' => 'pm_frequency',
            'parameters' => [
                'new_frequency_days' => 14, // Simulate bi-weekly PM
            ],
            'simulation_period' => 365,
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('scenario', $result);
        $this->assertArrayHasKey('current_state', $result);
        $this->assertArrayHasKey('impact_analysis', $result);
    }

    /**
     * Test What-If Simulator - Budget Change
     */
    public function test_simulate_budget_change(): void
    {
        $result = $this->service->simulateScenario([
            'scenario_type' => 'budget_change',
            'parameters' => [
                'change_percent' => 20, // 20% budget increase
            ],
            'simulation_period' => 365,
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('scenario', $result);
        $this->assertArrayHasKey('impact_analysis', $result);
    }

    /**
     * Test What-If Simulator - Staffing Change
     */
    public function test_simulate_staffing_change(): void
    {
        $result = $this->service->simulateScenario([
            'scenario_type' => 'staffing_change',
            'parameters' => [
                'new_technician_count' => 10, // Simulate 10 technicians
            ],
            'simulation_period' => 365,
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('scenario', $result);
    }

    /**
     * Test What-If Simulator - Add Equipment
     */
    public function test_simulate_add_equipment(): void
    {
        $result = $this->service->simulateScenario([
            'scenario_type' => 'add_equipment',
            'parameters' => [],
            'simulation_period' => 365,
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('scenario', $result);
    }

    /**
     * Test What-If Simulator - Shutdown Impact
     * Note: This scenario requires equipment_id, testing without it should return error
     */
    public function test_simulate_shutdown_impact(): void
    {
        // Test without equipment_id - should return error
        $result = $this->service->simulateScenario([
            'scenario_type' => 'shutdown_impact',
            'parameters' => [
                'shutdown_duration_days' => 7, // 7 day shutdown
            ],
            'simulation_period' => 90,
        ]);

        $this->assertIsArray($result);
        // Without equipment_id, this should return an error
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Equipment ID', $result['error']);
    }

    /**
     * Test WhatsApp Briefing - Generate Only (no actual send)
     */
    public function test_whatsapp_briefing_generation(): void
    {
        // Test the briefing generation without actually sending
        // We'll test the generateMaintenanceBriefing which is used by WhatsApp
        $briefing = $this->service->generateMaintenanceBriefing([
            'type' => 'daily',
            'include_details' => true,
        ]);

        $this->assertIsArray($briefing);
        $this->assertArrayHasKey('success', $briefing);
        $this->assertTrue($briefing['success']);
        $this->assertArrayHasKey('briefing_type', $briefing);
        $this->assertArrayHasKey('generated_at', $briefing);
    }

    /**
     * Test Invalid Scenario Type
     */
    public function test_invalid_scenario_type(): void
    {
        $result = $this->service->simulateScenario([
            'scenario_type' => 'invalid_type',
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    /**
     * Test All Phase 3 Functions via AIToolsExtended
     */
    public function test_phase3_tools_extended_integration(): void
    {
        $toolsClass = \App\Services\AIToolsExtended::class;
        
        // Test get_proactive_recommendations
        $result1 = $toolsClass::executeExtendedTool('get_proactive_recommendations', [
            'category' => 'all',
            'max_recommendations' => 5,
        ]);
        $this->assertArrayNotHasKey('error', $result1, 'get_proactive_recommendations failed');

        // Test simulate_scenario
        $result2 = $toolsClass::executeExtendedTool('simulate_scenario', [
            'scenario_type' => 'budget_change',
            'parameters' => ['change_percent' => 10],
        ]);
        $this->assertArrayNotHasKey('error', $result2, 'simulate_scenario failed');
    }
}
