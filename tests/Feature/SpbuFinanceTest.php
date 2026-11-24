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

    public function test_excel_calculation_parity_for_november_2026(): void
    {
        $service = app(SpbuFinanceService::class);
        $summary = $service->dashboardSummary(2026, 11, null);

        // Exact matches against Manajemen_Keuangan_BBM_Multi_Produk.xlsx
        $this->assertEquals(468800000, $summary['kpis']['total_modal_tebus']);
        $this->assertEquals(277900000, $summary['kpis']['total_penjualan_kotor']);
        $this->assertEquals(179100000, $summary['kpis']['total_piutang_belum_lunas']);
        $this->assertEquals(98800000, $summary['kpis']['total_piutang_sudah_lunas']);
        $this->assertEquals(-190900000, $summary['kpis']['estimasi_keuntungan_kotor']);
        $this->assertEquals(35600000, $summary['kpis']['dana_neto_rek1_puncak']);
        $this->assertEquals(0, $summary['kpis']['tambahan_kas_rek1_puncak']);

        // Rek 1 Projection exact match with Excel Dashboard B17..B21
        $this->assertEquals(200000000, $summary['accounts']['rek1']['opening_balance']);
        $this->assertEquals(953136491, $summary['accounts']['rek1']['total_pemasukan']);
        $this->assertEquals(468800000, $summary['accounts']['rek1']['total_pengeluaran']);
        $this->assertEquals(684336491, $summary['accounts']['rek1']['closing_balance']);
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
