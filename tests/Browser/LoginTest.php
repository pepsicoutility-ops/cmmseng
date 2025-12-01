<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LoginTest extends DuskTestCase
{
    /**
     * Test super admin can access dashboard
     */
    public function test_super_admin_can_access_dashboard(): void
    {
        $user = User::where('email', 'superadmin@cmms.com')->first();
        
        $this->browse(function (Browser $browser) use ($user) {
            $browser->driver->manage()->deleteAllCookies();
            $browser->loginAs($user)
                    ->visit('/pep')
                    ->waitForText('Dashboard', 10)
                    ->assertSee('Dashboard');
        });
    }

    /**
     * Test manager can access dashboard
     */
    public function test_manager_can_access_dashboard(): void
    {
        $user = User::where('email', 'manager@cmms.com')->first();
        
        $this->browse(function (Browser $browser) use ($user) {
            $browser->driver->manage()->deleteAllCookies();
            $browser->loginAs($user)
                    ->visit('/pep')
                    ->waitForText('Dashboard', 10)
                    ->assertSee('Dashboard');
        });
    }

    /**
     * Test technician can access dashboard
     */
    public function test_technician_can_access_dashboard(): void
    {
        $user = User::where('email', 'techmechanic1@cmms.com')->first();
        
        $this->browse(function (Browser $browser) use ($user) {
            $browser->driver->manage()->deleteAllCookies();
            $browser->loginAs($user)
                    ->visit('/pep')
                    ->waitForText('Dashboard', 10)
                    ->assertSee('Dashboard');
        });
    }

    /**
     * Test tech store can access dashboard
     */
    public function test_tech_store_can_access_dashboard(): void
    {
        $user = User::where('email', 'techstore@cmms.com')->first();
        
        $this->browse(function (Browser $browser) use ($user) {
            $browser->driver->manage()->deleteAllCookies();
            $browser->loginAs($user)
                    ->visit('/pep')
                    ->waitForText('Dashboard', 10)
                    ->assertSee('Dashboard');
        });
    }
}
