<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\CashEntry;
use App\Models\CashProjectionSetting;
use App\Models\CreditSale;
use App\Models\FuelRedemption;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Branches (matching Excel: Tanjung Sari & Kakap, Margin 3%)
        $ts = Branch::updateOrCreate(
            ['code' => 'TS'],
            ['name' => 'Tanjung Sari', 'margin_rate' => 0.0300, 'is_active' => true]
        );

        $kakap = Branch::updateOrCreate(
            ['code' => 'KKP'],
            ['name' => 'Kakap', 'margin_rate' => 0.0300, 'is_active' => true]
        );

        // 2. Multi-role Users
        $owner = User::updateOrCreate(
            ['email' => 'owner@rimasaspbu.com'],
            [
                'name' => 'Owner Rimasa SPBU',
                'password' => Hash::make('password'),
                'role' => 'owner',
                'branch_id' => null,
            ]
        );

        $adminTs = User::updateOrCreate(
            ['email' => 'ts@rimasaspbu.com'],
            [
                'name' => 'Admin Tanjung Sari',
                'password' => Hash::make('password'),
                'role' => 'admin_cabang',
                'branch_id' => $ts->id,
            ]
        );

        $adminKakap = User::updateOrCreate(
            ['email' => 'kakap@rimasaspbu.com'],
            [
                'name' => 'Admin Kakap',
                'password' => Hash::make('password'),
                'role' => 'admin_cabang',
                'branch_id' => $kakap->id,
            ]
        );

        // 3. Cash Projection Settings (Excel: November 2026, Saldo Awal Rek 1 = 200.000.000, Rek 2 = 0)
        CashProjectionSetting::updateOrCreate(
            ['year' => 2026, 'month' => 11],
            ['opening_balance_rek1' => 200000000, 'opening_balance_rek2' => 0]
        );

        // Only seed transactions if tables are empty (idempotent)
        if (FuelRedemption::count() === 0) {
            $tebusRows = [
                [$ts->id, '2026-11-01', 'DO-261101-01', 'Solar', 8000, 6800, 'Lunas (Tunai)', $adminTs->id],
                [$kakap->id, '2026-11-01', 'DO-261101-02', 'Pertalite', 8000, 10000, 'Lunas (Tunai)', $adminKakap->id],
                [$ts->id, '2026-11-02', 'DO-261102-01', 'Pertamax', 8000, 12500, 'Lunas (Tunai)', $adminTs->id],
                [$ts->id, '2026-11-02', 'DO-261102-02', 'Solar', 8000, 6800, 'Lunas (Tunai)', $adminTs->id],
                [$kakap->id, '2026-11-03', 'DO-261103-01', 'Pertalite', 8000, 10000, 'Lunas (Tunai)', $adminKakap->id],
                [$ts->id, '2026-11-04', 'DO-261104-01', 'Pertamax', 8000, 12500, 'Lunas (Tunai)', $adminTs->id],
            ];

            foreach ($tebusRows as [$branchId, $date, $doNo, $product, $vol, $price, $status, $createdBy]) {
                FuelRedemption::create([
                    'branch_id' => $branchId,
                    'transaction_date' => $date,
                    'do_number' => $doNo,
                    'product_name' => $product,
                    'is_non_bbm' => FuelRedemption::detectNonBbm($product),
                    'volume_liters' => $vol,
                    'price_per_liter' => $price,
                    'total_amount' => $vol * $price,
                    'payment_status' => $status,
                    'created_by' => $createdBy,
                ]);
            }
        }

        if (CreditSale::count() === 0) {
            $saleRows = [
                [$ts->id, '2026-11-01', 'Zhenglu', 'Solar', 5000, 11200, 14, '2026-11-15', 'Lunas', '2026-11-01', $adminTs->id],
                [$kakap->id, '2026-11-01', 'Rajawali', 'Pertalite', 4000, 10700, 7, '2026-11-08', 'Lunas', '2026-11-01', $adminKakap->id],
                [$ts->id, '2026-11-02', 'RPT', 'Pertamax', 3000, 13300, 7, '2026-11-09', 'Belum Lunas', null, $adminTs->id],
                [$kakap->id, '2026-11-02', 'Wanto', 'Solar', 5000, 11300, 14, '2026-11-16', 'Belum Lunas', null, $adminKakap->id],
                [$ts->id, '2026-11-03', 'Rajawali', 'Pertalite', 4000, 10700, 7, '2026-11-10', 'Belum Lunas', null, $adminTs->id],
                [$ts->id, '2026-11-04', 'RPT', 'Pertamax', 3000, 13300, 7, '2026-11-11', 'Belum Lunas', null, $adminTs->id],
            ];

            foreach ($saleRows as [$branchId, $date, $client, $product, $vol, $price, $term, $due, $status, $paidDate, $createdBy]) {
                CreditSale::create([
                    'branch_id' => $branchId,
                    'sale_date' => $date,
                    'client_name' => $client,
                    'product_name' => $product,
                    'is_non_bbm' => FuelRedemption::detectNonBbm($product),
                    'volume_liters' => $vol,
                    'price_per_liter' => $price,
                    'total_amount' => $vol * $price,
                    'credit_term_days' => $term,
                    'due_date' => $due,
                    'payment_status' => $status,
                    'paid_date' => $paidDate,
                    'created_by' => $createdBy,
                ]);
            }
        }

        if (CashEntry::count() === 0) {
            $cashRows = [
                // Kas TS Rek 1
                [$ts->id, 'rek1', '2026-11-02', 'Tunai ex 31/10 Shift I', 'shift_omzet', 56405100, 0, $adminTs->id],
                [$ts->id, 'rek1', '2026-11-02', 'Shift II', 'shift_omzet', 51385500, 0, $adminTs->id],
                [$ts->id, 'rek1', '2026-11-02', 'Shift III', 'shift_omzet', 15077300, 0, $adminTs->id],
                [$ts->id, 'rek1', '2026-11-02', 'Tunai ex 1/11 Shift I', 'shift_omzet', 36526700, 0, $adminTs->id],
                [$ts->id, 'rek1', '2026-11-02', 'Shift II', 'shift_omzet', 31652900, 0, $adminTs->id],
                [$ts->id, 'rek1', '2026-11-02', 'Shift III', 'shift_omzet', 16404700, 0, $adminTs->id],
                [$ts->id, 'rek1', '2026-11-02', 'Stor BCA ex TSPS Tukar', 'tsps_tukar', 0, 25000000, $adminTs->id],
                [$ts->id, 'rek1', '2026-11-03', 'Tunai ex 2/11 Shift I', 'shift_omzet', 65834300, 0, $adminTs->id],
                [$ts->id, 'rek1', '2026-11-03', 'Shift II', 'shift_omzet', 56971000, 0, $adminTs->id],
                [$ts->id, 'rek1', '2026-11-03', 'Shift III', 'shift_omzet', 23995500, 0, $adminTs->id],
                [$ts->id, 'rek1', '2026-11-03', 'Stor BCA ex TSPS Tukar', 'tsps_tukar', 0, 15000000, $adminTs->id],

                // Kas TS Rek 2
                [$ts->id, 'rek2', '2026-11-02', 'Tunai ex O. LPG M 31/10', 'non_bbm_income', 357500, 0, $adminTs->id],
                [$ts->id, 'rek2', '2026-11-02', 'U/ Kas SPBU', 'operational_expense', 0, 100000, $adminTs->id],
                [$ts->id, 'rek2', '2026-11-02', 'Tunai ex O. LPG M 1/11', 'non_bbm_income', 570000, 0, $adminTs->id],
                [$ts->id, 'rek2', '2026-11-02', 'U/ Kas SPBU', 'operational_expense', 0, 4200000, $adminTs->id],
                [$ts->id, 'rek2', '2026-11-03', 'Tunai ex O. LPG M 2/11', 'non_bbm_income', 411500, 0, $adminTs->id],
                [$ts->id, 'rek2', '2026-11-03', 'U/ Kas SPBU', 'operational_expense', 0, 200000, $adminTs->id],

                // Kas Kakap Rek 1
                [$kakap->id, 'rek1', '2026-11-02', 'Tunai ex 31/10 Shift I', 'shift_omzet', 96695000, 0, $adminKakap->id],
                [$kakap->id, 'rek1', '2026-11-02', 'Shift II', 'shift_omzet', 90310500, 0, $adminKakap->id],
                [$kakap->id, 'rek1', '2026-11-02', 'Shift III', 'shift_omzet', 15774300, 0, $adminKakap->id],
                [$kakap->id, 'rek1', '2026-11-02', 'Tunai ex 1/10 Shift I', 'shift_omzet', 60158100, 0, $adminKakap->id],
                [$kakap->id, 'rek1', '2026-11-02', 'Shift II', 'shift_omzet', 45453000, 0, $adminKakap->id],
                [$kakap->id, 'rek1', '2026-11-02', 'Shift III', 'shift_omzet', 41551400, 0, $adminKakap->id],
                [$kakap->id, 'rek1', '2026-11-03', 'Tunai ex 2/11 Shift I', 'shift_omzet', 119296700, 0, $adminKakap->id],
                [$kakap->id, 'rek1', '2026-11-03', 'Shift II', 'shift_omzet', 79528600, 0, $adminKakap->id],
                [$kakap->id, 'rek1', '2026-11-03', 'Shift III', 'shift_omzet', 34439700, 0, $adminKakap->id],
                [$kakap->id, 'rek1', '2026-11-03', 'Stor BCA ex TSPS Tukar', 'tsps_tukar', 0, 15000000, $adminKakap->id],

                // Kas Kakap Rek 2
                [$kakap->id, 'rek2', '2026-11-02', 'Tunai ex O. LPG M 31/10', 'non_bbm_income', 357500, 0, $adminKakap->id],
                [$kakap->id, 'rek2', '2026-11-02', 'U/ Kas SPBU', 'operational_expense', 0, 400000, $adminKakap->id],
                [$kakap->id, 'rek2', '2026-11-02', 'Tunai ex O. LPG M 01/11', 'non_bbm_income', 570000, 0, $adminKakap->id],
                [$kakap->id, 'rek2', '2026-11-02', 'U/ Kas SPBU', 'operational_expense', 0, 4200000, $adminKakap->id],
                [$kakap->id, 'rek2', '2026-11-03', 'Tunai ex O. LPG M', 'non_bbm_income', 411500, 0, $adminKakap->id],
                [$kakap->id, 'rek2', '2026-11-03', 'U/ Kas SPBU', 'operational_expense', 0, 200000, $adminKakap->id],
                [$kakap->id, 'rek2', '2026-11-03', 'Kotak toilet', 'non_bbm_income', 609000, 0, $adminKakap->id],
                [$kakap->id, 'rek2', '2026-11-03', 'Bensin + Parkir', 'operational_expense', 0, 60000, $adminKakap->id],
                [$kakap->id, 'rek2', '2026-11-03', 'Pulsa', 'operational_expense', 0, 25000, $adminKakap->id],
                [$kakap->id, 'rek2', '2026-11-03', 'Kulakan LPG', 'operational_expense', 0, 1000000, $adminKakap->id],
                [$kakap->id, 'rek2', '2026-11-30', 'Tunai ex O. LPG M 31/10', 'non_bbm_income', 357500, 0, $adminKakap->id],
                [$kakap->id, 'rek2', '2026-11-30', 'U/ Kas SPBU', 'operational_expense', 0, 400000, $adminKakap->id],
                [$kakap->id, 'rek2', '2026-11-30', 'Tunai ex O. LPG M 01/11', 'non_bbm_income', 570000, 0, $adminKakap->id],
                [$kakap->id, 'rek2', '2026-11-30', 'U/ Kas SPBU', 'operational_expense', 0, 4200000, $adminKakap->id],
            ];

            foreach ($cashRows as [$branchId, $acct, $date, $desc, $cat, $debit, $credit, $createdBy]) {
                CashEntry::create([
                    'branch_id' => $branchId,
                    'account_type' => $acct,
                    'entry_date' => $date,
                    'description' => $desc,
                    'category' => $cat,
                    'debit' => $debit,
                    'credit' => $credit,
                    'created_by' => $createdBy,
                ]);
            }
        }
    }
}
