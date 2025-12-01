<?php

namespace Tests\Browser;

use App\Models\BarcodeToken;
use App\Models\Asset;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class BarcodeFormTest extends DuskTestCase
{
    /**
     * Test barcode form can be accessed with valid token
     */
    public function test_barcode_form_accessible_with_valid_token(): void
    {
        $token = BarcodeToken::factory()->create([
            'equipment_type' => 'all',
            'is_active' => true,
        ]);

        $this->browse(function (Browser $browser) use ($token) {
            $url = "/barcode/wo/{$token->token}";
            $browser->visit($url)
                    ->waitForText('Work Order Form')
                    ->assertSee('Submit Work Order')
                    ->assertSee('Operator Name')
                    ->assertSee('Problem Description');
        });
    }

    /**
     * Test barcode form submission creates work order
     */
    public function test_barcode_form_submission_creates_wo(): void
    {
        $token = BarcodeToken::factory()->create([
            'equipment_type' => 'all',
            'is_active' => true,
        ]);

        $asset = Asset::first();

        $this->browse(function (Browser $browser) use ($token, $asset) {
            $url = "/barcode/wo/{$token->token}";
            $browser->visit($url)
                    ->waitForText('Work Order Form')
                    ->type('operator_name', 'Barcode Test Operator')
                    ->select('shift', '1')
                    ->select('problem_type', 'breakdown')
                    ->select('asset_id', $asset->id)
                    ->type('description', 'Machine malfunction detected via barcode scan')
                    ->select('priority', 'high')
                    ->press('Submit Work Order')
                    ->waitForText('Work order submitted successfully')
                    ->assertSee('WO-'); // Should show generated WO number
        });
    }

    /**
     * Test inactive barcode token shows error
     */
    public function test_inactive_barcode_token_shows_error(): void
    {
        $token = BarcodeToken::factory()->create([
            'equipment_type' => 'all',
            'is_active' => false,
        ]);

        $this->browse(function (Browser $browser) use ($token) {
            $url = "/barcode/wo/{$token->token}";
            $browser->visit($url)
                    ->waitForText('Token is not active')
                    ->assertSee('Token is not active');
        });
    }

    /**
     * Test invalid barcode token shows 404
     */
    public function test_invalid_barcode_token_shows_404(): void
    {
        $this->browse(function (Browser $browser) {
            $url = "/barcode/wo/invalid-token-12345";
            $browser->visit($url)
                    ->waitForText('404')
                    ->assertSee('404');
        });
    }

    /**
     * Test barcode form with photo upload
     */
    public function test_barcode_form_with_photo_upload(): void
    {
        $token = BarcodeToken::factory()->create([
            'equipment_type' => 'all',
            'is_active' => true,
        ]);

        $asset = Asset::first();

        $this->browse(function (Browser $browser) use ($token, $asset) {
            $url = "/barcode/wo/{$token->token}";
            
            // Create a dummy image file for testing
            $testImagePath = storage_path('app/test-image.jpg');
            if (!file_exists($testImagePath)) {
                copy(public_path('images/logo.png'), $testImagePath);
            }

            $browser->visit($url)
                    ->waitForText('Work Order Form')
                    ->type('operator_name', 'Photo Upload Test')
                    ->select('shift', '2')
                    ->select('problem_type', 'abnormality')
                    ->select('asset_id', $asset->id)
                    ->type('description', 'Testing photo upload functionality')
                    ->attach('photos[]', $testImagePath)
                    ->press('Submit Work Order')
                    ->waitForText('Work order submitted successfully')
                    ->assertSee('WO-');
        });
    }
}
