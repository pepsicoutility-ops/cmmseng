<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\WorkOrder;
use App\Models\Asset;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class WorkOrderFlowTest extends DuskTestCase
{
    /**
     * Test complete work order workflow from creation to completion
     */
    public function test_complete_work_order_workflow(): void
    {
        $operator = User::where('email', 'operator@cmms.com')->first();
        $technician = User::where('email', 'techmechanic1@cmms.com')->first();
        
        $this->browse(function (Browser $browser) use ($operator, $technician) {
            // Login as operator
            $browser->driver->manage()->deleteAllCookies();
            $browser->loginAs($operator)
                    ->visit('/pep');

            // Navigate to Work Orders
            $browser->clickLink('Work Orders')
                    ->waitForText('Work Orders')
                    ->assertSee('Work Orders');

            // Create new work order
            $browser->click('[data-action="create"]')
                    ->waitForText('Create Work Order');

            // Fill in work order form
            $asset = Asset::first();
            $browser->type('operator_name', 'Test Operator')
                    ->select('shift', '1')
                    ->select('problem_type', 'breakdown')
                    ->select('asset_id', $asset->id)
                    ->type('description', 'Test machine breakdown - requires immediate attention')
                    ->select('priority', 'high')
                    ->press('Create')
                    ->waitForText('Work order created successfully')
                    ->assertSee('submitted');

            // Logout operator
            $browser->click('[aria-label="User menu"]')
                    ->click('Sign out')
                    ->waitForLocation('/pep/login');

            // Login as technician to review
            $browser->driver->manage()->deleteAllCookies();
            $browser->loginAs($technician)
                    ->visit('/pep');

            // Find and review the work order
            $browser->clickLink('Work Orders')
                    ->waitForText('Work Orders')
                    ->click('tbody tr:first-child')
                    ->waitForText('Review')
                    ->click('[data-action="review"]')
                    ->waitForText('Are you sure')
                    ->press('Confirm')
                    ->waitForText('reviewed')
                    ->assertSee('reviewed');
        });
    }

    /**
     * Test technician can only see their department work orders
     */
    public function test_technician_sees_only_department_work_orders(): void
    {
        $technician = User::where('email', 'techmechanic1@cmms.com')->first();
        
        $this->browse(function (Browser $browser) use ($technician) {
            // Login as mechanic technician
            $browser->driver->manage()->deleteAllCookies();
            $browser->loginAs($technician)
                    ->visit('/pep');

            $browser->clickLink('Work Orders')
                    ->waitForText('Work Orders');

            // Should see mechanic department work orders
            // Apply filter to verify
            $browser->click('[data-filter="assign_to"]')
                    ->waitForText('mechanic')
                    ->assertSee('mechanic');
        });
    }

    /**
     * Test manager can approve work order
     */
    public function test_manager_can_approve_work_order(): void
    {
        // Create a reviewed work order first
        $workOrder = WorkOrder::factory()->create([
            'status' => 'reviewed',
            'assign_to' => 'mechanic',
        ]);
        
        $manager = User::where('email', 'manager@cmms.com')->first();

        $this->browse(function (Browser $browser) use ($manager, $workOrder) {
            $browser->driver->manage()->deleteAllCookies();
            $browser->loginAs($manager)
                    ->visit('/pep');

            $browser->clickLink('Work Orders')
                    ->waitForText('Work Orders')
                    ->click("tr[data-id=\"{$workOrder->id}\"]")
                    ->waitForText('Approve')
                    ->click('[data-action="approve"]')
                    ->waitForText('Are you sure')
                    ->press('Confirm')
                    ->waitForText('approved')
                    ->assertSee('approved');
        });
    }

    /**
     * Test work order creation auto-generates WO number
     */
    public function test_wo_number_auto_generation(): void
    {
        $operator = User::where('email', 'operator@cmms.com')->first();
        
        $this->browse(function (Browser $browser) use ($operator) {
            $browser->driver->manage()->deleteAllCookies();
            $browser->loginAs($operator)
                    ->visit('/pep');

            $browser->clickLink('Work Orders')
                    ->waitForText('Work Orders');

            $beforeCount = WorkOrder::count();

            // Create work order
            $asset = Asset::first();
            $browser->click('[data-action="create"]')
                    ->waitForText('Create Work Order')
                    ->type('operator_name', 'Auto WO Test')
                    ->select('shift', '2')
                    ->select('problem_type', 'abnormality')
                    ->select('asset_id', $asset->id)
                    ->type('description', 'Testing auto WO number generation')
                    ->press('Create')
                    ->waitForText('Work order created successfully');

            // Verify WO number format: WO-YYYYMM-####
            $latestWo = WorkOrder::latest()->first();
            $this->assertMatchesRegularExpression('/^WO-\d{6}-\d{4}$/', $latestWo->wo_number);
        });
    }
}
