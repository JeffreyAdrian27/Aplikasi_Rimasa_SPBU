<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SpbuFinanceService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpbuFinanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_dashboard_summary_runs_cleanly_on_empty_transactions(): void
    {
        $service = app(SpbuFinanceService::class);
        $summary = $service->dashboardSummary(2026, 11, null);

        $this->assertEquals(0, $summary['kpis']['total_modal_tebus']);
        $this->assertEquals(0, $summary['kpis']['total_penjualan_kotor']);
        $this->assertEquals(0, $summary['kpis']['total_piutang_belum_lunas']);
        $this->assertEquals(0, $summary['accounts']['rek1']['closing_balance']);
        $this->assertCount(2, $summary['branch_cards']);
        $this->assertCount(3, $summary['product_summary']);
    }

    public function test_owner_and_admin_cabang_access_control(): void
    {
        $owner = User::where('username', 'owner')->firstOrFail();
        $adminTs = User::where('username', 'admin_ts')->firstOrFail();

        // Guest redirected to login
        $this->get('/')->assertRedirect('/login');

        // Login with username works
        $this->post('/login', ['username' => 'owner', 'password' => 'password'])
            ->assertRedirect('/');
        $this->assertAuthenticatedAs($owner);
        $this->post('/logout')->assertRedirect('/login');

        // Owner can access all pages including settings
        $this->actingAs($owner)->get('/')->assertOk();
        $this->actingAs($owner)->get('/tebus')->assertOk();
        $this->actingAs($owner)->get('/penjualan')->assertOk();
        $this->actingAs($owner)->get('/kas')->assertOk();
        $this->actingAs($owner)->get('/proyeksi')->assertOk();
        $this->actingAs($owner)->get('/pengaturan')->assertOk();

        // Admin Cabang can access operational pages but NOT owner settings
        $this->actingAs($adminTs)->get('/')->assertOk();
        $this->actingAs($adminTs)->get('/tebus')->assertOk();
        $this->actingAs($adminTs)->get('/penjualan')->assertOk();
        $this->actingAs($adminTs)->get('/kas')->assertOk();
        $this->actingAs($adminTs)->get('/pengaturan')->assertForbidden();

        // Batch cash entry per date skips zero rows and saves non-zero rows
        $this->actingAs($adminTs)->post('/kas/batch', [
            'branch_id' => $adminTs->branch_id,
            'account_type' => 'rek1',
            'entry_date' => '2026-11-05',
            'items' => [
                ['description' => 'Tunai ex 4/11 Shift I', 'category' => 'shift_omzet', 'debit' => 50000000, 'credit' => 0],
                ['description' => 'Shift II', 'category' => 'shift_omzet', 'debit' => 40000000, 'credit' => 0],
                ['description' => 'Shift III', 'category' => 'shift_omzet', 'debit' => 0, 'credit' => 0],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('cash_entries', [
            'entry_date' => '2026-11-05',
            'description' => 'Tunai ex 4/11 Shift I',
            'debit' => 50000000,
        ]);
        $this->assertDatabaseMissing('cash_entries', [
            'entry_date' => '2026-11-05',
            'description' => 'Shift III',
        ]);

        // Inline edit cash entry
        $entryId = \App\Models\CashEntry::where('entry_date', '2026-11-05')
            ->where('description', 'Shift II')
            ->value('id');
        $this->actingAs($adminTs)->put("/kas/{$entryId}", [
            'entry_date' => '2026-11-05',
            'description' => 'Shift II (Revisi)',
            'debit' => 42000000,
            'credit' => 0,
        ])->assertRedirect();
        $this->assertDatabaseHas('cash_entries', [
            'id' => $entryId,
            'description' => 'Shift II (Revisi)',
            'debit' => 42000000,
        ]);

        // Batch multi-product Tebus with auto 0.25% PPh 22 from product base price
        $this->actingAs($adminTs)->post('/tebus/batch', [
            'branch_id' => $adminTs->branch_id,
            'transaction_date' => '2026-11-06',
            'payment_status' => 'Lunas (Tunai)',
            'items' => [
                ['do_number' => 'DO-TEST-1', 'product_name' => 'Pertalite', 'volume_liters' => 8000, 'price_per_liter' => 10000, 'tax_per_8kl' => 8620.69],
                ['do_number' => 'DO-TEST-2', 'product_name' => 'Solar', 'volume_liters' => 16000, 'price_per_liter' => 6800, 'tax_per_8kl' => 5862.07],
            ],
        ])->assertRedirect();

        // Pertalite 8,000 L * 8620.69 = 68,965,520 * 0.25% = 172,414; total = 80,000,000 + 172,414 = 80,172,414
        $this->assertDatabaseHas('fuel_redemptions', [
            'do_number' => 'DO-TEST-1',
            'tax_per_8kl' => 8620.69,
            'tax_amount' => 172414,
            'total_amount' => 80172414,
        ]);

        // Solar 16,000 L * 5862.07 = 93,793,120 * 0.25% = 234,483; total = 108,800,000 + 234,483 = 109,034,483
        $this->assertDatabaseHas('fuel_redemptions', [
            'do_number' => 'DO-TEST-2',
            'tax_per_8kl' => 5862.07,
            'tax_amount' => 234483,
            'total_amount' => 109034483,
        ]);

        // Update product base price for PPh 22
        $this->actingAs($adminTs)->patch('/tebus/harga-dasar', [
            'prices' => [
                'Solar' => 6000,
                'Pertalite' => 8620.69,
                'Pertamax' => 13750,
                'Pertamina Dex' => 21724.14,
            ],
        ])->assertRedirect();
        $this->assertDatabaseHas('product_base_prices', [
            'product_name' => 'Solar',
            'base_price' => 6000,
        ]);

        // Simplified credit sale (without product/volume)
        $this->actingAs($adminTs)->post('/penjualan', [
            'branch_id' => $adminTs->branch_id,
            'sale_date' => '2026-11-06',
            'client_name' => 'PT Maju Jaya',
            'total_amount' => 75000000,
            'credit_term_days' => 14,
        ])->assertRedirect();
        $this->assertDatabaseHas('credit_sales', [
            'client_name' => 'PT Maju Jaya',
            'total_amount' => 75000000,
            'payment_status' => 'Belum Lunas',
        ]);

        // Admin Cabang can update opening balance settings too
        $this->actingAs($adminTs)->post('/proyeksi/settings', [
            'year' => 2026,
            'month' => 11,
            'opening_balance_rek1' => 210000000,
            'opening_balance_rek2' => 5000000,
        ])->assertRedirect();
        $this->assertDatabaseHas('cash_projection_settings', [
            'year' => 2026,
            'month' => 11,
            'opening_balance_rek1' => 210000000,
            'opening_balance_rek2' => 5000000,
        ]);

        // Admin Cabang can change their own password
        $this->actingAs($adminTs)->get('/akun')->assertOk();
        $this->actingAs($adminTs)->put('/akun/password', [
            'current_password' => 'salah',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($adminTs)->put('/akun/password', [
            'current_password' => 'password',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->post('/logout');
        $this->post('/login', ['username' => 'admin_ts', 'password' => 'rahasia123'])
            ->assertRedirect('/');
        $this->assertAuthenticatedAs($adminTs);
    }
}
