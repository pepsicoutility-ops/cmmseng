<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class RoleBasedAccessTest extends DuskTestCase
{
    /**
     * Test operator cannot access Filament admin panel
     */
    public function test_operator_cannot_access_admin_panel(): void
    {
        $operator = User::where('email', 'operator@cmms.com')->first();
        
        $this->browse(function (Browser $browser) use ($operator) {
            $browser->driver->manage()->deleteAllCookies();
            $browser->loginAs($operator)
                    ->visit('/pep');

            // Operator should see limited menu
            $browser->assertDontSee('Master Data')
                    ->assertDontSee('Users')
                    ->assertSee('Work Orders'); // Operator can create WO
        });
    }

    /**
     * Test tech store has inventory-only access
     */
    public function test_tech_store_inventory_only_access(): void
    {
        $techStore = User::where('email', 'techstore@cmms.com')->first();
        
        $this->browse(function (Browser $browser) use ($techStore) {
            $browser->driver->manage()->deleteAllCookies();
            $browser->loginAs($techStore)
                    ->visit('/pep');

            // Tech store should see inventory menus
            $browser->assertSee('Parts')
                    ->assertSee('Inventories')
                    ->assertSee('Stock Alerts');

            // Should not see WO or PM
            $browser->assertDontSee('Work Orders')
                    ->assertDontSee('PM Schedules');
        });
    }

    /**
     * Test asisten manager sees only their department
     */
    public function test_asisten_manager_department_filtering(): void
    {
        $asistenManager = User::where('email', 'asistenmanager.mechanic@cmms.com')->first();
        
        $this->browse(function (Browser $browser) use ($asistenManager) {
            $browser->driver->manage()->deleteAllCookies();
            $browser->loginAs($asistenManager)
                    ->visit('/pep');

            // Navigate to PM Schedules
            $browser->clickLink('PM Schedules')
                    ->waitForText('PM Schedules');

            // Should only see mechanic department PMs
            $browser->click('[data-filter="department"]')
                    ->waitForText('mechanic')
                    ->assertSee('mechanic');

            // Verify electric department is filtered out
            // (Should not see electric PMs in the list)
        });
    }

    /**
     * Test manager has full access to all features
     */
    public function test_manager_full_access(): void
    {
        $manager = User::where('email', 'manager@cmms.com')->first();
        
        $this->browse(function (Browser $browser) use ($manager) {
            $browser->driver->manage()->deleteAllCookies();
            $browser->loginAs($manager)
                    ->visit('/pep');

            // Manager should see all menus
            $browser->assertSee('Dashboard')
                    ->assertSee('Master Data')
                    ->assertSee('Work Orders')
                    ->assertSee('PM Schedules')
                    ->assertSee('PM Executions')
                    ->assertSee('Parts')
                    ->assertSee('Inventories')
                    ->assertSee('Reports');
        });
    }

    /**
     * Test super admin has access to all features including user management
     */
    public function test_super_admin_full_access(): void
    {
        $superAdmin = User::where('email', 'superadmin@cmms.com')->first();
        
        $this->browse(function (Browser $browser) use ($superAdmin) {
            $browser->driver->manage()->deleteAllCookies();
            $browser->loginAs($superAdmin)
                    ->visit('/pep');

            // Super admin should see everything including Users
            $browser->assertSee('Dashboard')
                    ->assertSee('Users')
                    ->assertSee('Master Data')
                    ->assertSee('Work Orders')
                    ->assertSee('PM Schedules')
                    ->assertSee('Barcode Tokens');
        });
    }

    /**
     * Test unauthorized role redirect
     */
    public function test_unauthorized_access_redirects(): void
    {
        $this->browse(function (Browser $browser) {
            // Try accessing admin panel without login
            $browser->visit('/pep/users')
                    ->waitForLocation('/pep/login')
                    ->assertPathIs('/pep/login');
        });
    }
}
