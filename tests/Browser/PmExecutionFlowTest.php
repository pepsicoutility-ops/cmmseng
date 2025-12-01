<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\PmSchedule;
use App\Models\PmExecution;
use App\Models\Asset;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PmExecutionFlowTest extends DuskTestCase
{
    /**
     * Test technician can execute assigned PM schedule
     */
    public function test_technician_can_execute_assigned_pm(): void
    {
        $technician = User::where('email', 'techmechanic1@cmms.com')->first();
        $asset = Asset::first();

        // Create PM schedule assigned to technician
        $pmSchedule = PmSchedule::factory()->create([
            'assigned_to_gpid' => $technician->gpid,
            'asset_id' => $asset->id,
            'department' => 'mechanic',
            'schedule_type' => 'weekly',
            'is_active' => true,
        ]);

        $this->browse(function (Browser $browser) use ($technician, $pmSchedule) {
            $browser->driver->manage()->deleteAllCookies();
            $browser->loginAs($technician)
                    ->visit('/pep');

            // Navigate to PM Schedules
            $browser->clickLink('PM Schedules')
                    ->waitForText('PM Schedules')
                    ->assertSee($pmSchedule->code);

            // View PM details
            $browser->click("tr[data-code=\"{$pmSchedule->code}\"]")
                    ->waitForText('Checklist Items')
                    ->assertSee($pmSchedule->title);

            // Navigate to executions
            $browser->clickLink('PM Executions')
                    ->waitForText('PM Executions')
                    ->assertSee('Create Execution');

            // Create new execution
            $browser->click('[data-action="create"]')
                    ->waitForText('Create PM Execution')
                    ->select('pm_schedule_id', $pmSchedule->id)
                    ->type('scheduled_date', now()->format('Y-m-d'))
                    ->press('Create')
                    ->waitForText('PM execution created successfully');
        });
    }

    /**
     * Test technician sees only their assigned PM schedules
     */
    public function test_technician_sees_only_assigned_pm(): void
    {
        $technicianMechanic = User::where('email', 'techmechanic1@cmms.com')->first();
        $technicianElectric = User::where('email', 'techelectric1@cmms.com')->first();

        // Create PM for mechanic
        $pmMechanic = PmSchedule::factory()->create([
            'assigned_to_gpid' => $technicianMechanic->gpid,
            'department' => 'mechanic',
            'title' => 'Mechanic PM Test',
        ]);

        // Create PM for electric
        $pmElectric = PmSchedule::factory()->create([
            'assigned_to_gpid' => $technicianElectric->gpid,
            'department' => 'electric',
            'title' => 'Electric PM Test',
        ]);

        $this->browse(function (Browser $browser) use ($technicianMechanic, $pmMechanic, $pmElectric) {
            // Login as mechanic technician
            $browser->driver->manage()->deleteAllCookies();
            $browser->loginAs($technicianMechanic)
                    ->visit('/pep');

            $browser->clickLink('PM Schedules')
                    ->waitForText('PM Schedules');

            // Should see mechanic PM
            $browser->assertSee('Mechanic PM Test');

            // Should NOT see electric PM
            $browser->assertDontSee('Electric PM Test');
        });
    }

    /**
     * Test manager can view all PM schedules
     */
    public function test_manager_can_view_all_pm_schedules(): void
    {
        $manager = User::where('email', 'manager@cmms.com')->first();
        
        $this->browse(function (Browser $browser) use ($manager) {
            $browser->driver->manage()->deleteAllCookies();
            $browser->loginAs($manager)
                    ->visit('/pep');

            $browser->clickLink('PM Schedules')
                    ->waitForText('PM Schedules');

            // Manager should have access to all departments
            $browser->click('[data-filter="department"]')
                    ->waitForText('mechanic')
                    ->assertSee('mechanic')
                    ->assertSee('electric')
                    ->assertSee('utility');
        });
    }

    /**
     * Test PM schedule auto-generates code
     */
    public function test_pm_code_auto_generation(): void
    {
        $manager = User::where('email', 'manager@cmms.com')->first();
        
        $this->browse(function (Browser $browser) use ($manager) {
            $browser->driver->manage()->deleteAllCookies();
            $browser->loginAs($manager)
                    ->visit('/pep');

            $browser->clickLink('PM Schedules')
                    ->waitForText('PM Schedules');

            $beforeCount = PmSchedule::count();

            // Create new PM schedule
            $asset = Asset::first();
            $technician = User::where('role', 'technician')->first();

            $browser->click('[data-action="create"]')
                    ->waitForText('Create PM Schedule')
                    ->type('title', 'Auto Code Test PM')
                    ->select('asset_id', $asset->id)
                    ->select('department', 'mechanic')
                    ->select('assigned_to_gpid', $technician->gpid)
                    ->select('schedule_type', 'weekly')
                    ->type('interval_value', '1')
                    ->select('week_day', '1')
                    ->press('Create')
                    ->waitForText('PM schedule created successfully');

            // Verify code format: PM-####
            $latestPm = PmSchedule::latest()->first();
            $this->assertMatchesRegularExpression('/^PM-\d{4}$/', $latestPm->code);
        });
    }

    /**
     * Test PM execution with checklist completion
     */
    public function test_pm_execution_with_checklist(): void
    {
        $technician = User::where('email', 'techmechanic1@cmms.com')->first();
        $asset = Asset::first();

        $pmSchedule = PmSchedule::factory()->create([
            'assigned_to_gpid' => $technician->gpid,
            'asset_id' => $asset->id,
            'department' => 'mechanic',
        ]);

        // Add checklist items
        $pmSchedule->checklistItems()->create([
            'description' => 'Check oil level',
            'order' => 1,
        ]);
        $pmSchedule->checklistItems()->create([
            'description' => 'Inspect belts',
            'order' => 2,
        ]);

        $this->browse(function (Browser $browser) use ($technician, $pmSchedule) {
            $browser->driver->manage()->deleteAllCookies();
            $browser->loginAs($technician)
                    ->visit('/pep');

            $browser->clickLink('PM Executions')
                    ->waitForText('PM Executions')
                    ->click('[data-action="create"]')
                    ->waitForText('Create PM Execution')
                    ->select('pm_schedule_id', $pmSchedule->id)
                    ->type('scheduled_date', now()->format('Y-m-d'))
                    ->type('actual_start', now()->format('Y-m-d\TH:i'))
                    ->type('actual_end', now()->addHours(2)->format('Y-m-d\TH:i'))
                    ->check('checklist[0][completed]')
                    ->check('checklist[1][completed]')
                    ->select('status', 'completed')
                    ->press('Create')
                    ->waitForText('PM execution created successfully');

            // Verify execution was created
            $latestExecution = PmExecution::latest()->first();
            $this->assertEquals('completed', $latestExecution->status);
        });
    }
}
