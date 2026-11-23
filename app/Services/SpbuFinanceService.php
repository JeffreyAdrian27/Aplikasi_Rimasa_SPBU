<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\CashEntry;
use App\Models\CashProjectionSetting;
use App\Models\CreditSale;
use App\Models\FuelRedemption;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SpbuFinanceService
{
    public function branchCashDailyRek1(Branch $branch, ?int $year = null, ?int $month = null): Collection
    {
        $query = CashEntry::query()
            ->where('branch_id', $branch->id)
            ->where('account_type', 'rek1');

        if ($year && $month) {
            $start = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
            $end = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();
            $query->whereBetween('entry_date', [$start, $end]);
        }

        $entries = $query->orderBy('entry_date')->orderBy('id')->get();
        $marginRate = (float) $branch->margin_rate;

        return $entries
            ->groupBy(fn (CashEntry $e) => $e->entry_date->format('Y-m-d'))
            ->map(function (Collection $dayEntries, string $date) use ($marginRate) {
                $totalOmzet = $dayEntries
                    ->filter(fn (CashEntry $e) => ! str_contains(strtolower($e->description), 'total'))
                    ->sum('debit');

                $marginAmount = round($totalOmzet * $marginRate);
                $afterMargin = $totalOmzet - $marginAmount;

                $tspsTukar = $dayEntries
                    ->filter(fn (CashEntry $e) => $e->category === 'tsps_tukar' || str_contains(strtoupper($e->description), 'TSPS'))
                    ->sum('credit');

                $storBca = $afterMargin - $tspsTukar;

                return [
                    'date' => $date,
                    'total_omzet' => (float) $totalOmzet,
                    'margin_rate' => $marginRate,
                    'margin_amount' => (float) $marginAmount,
                    'after_margin' => (float) $afterMargin,
                    'tsps_tukar' => (float) $tspsTukar,
                    'stor_bca' => (float) $storBca,
                ];
            })
            ->sortKeys()
            ->values();
    }

    public function branchCashDailyRek2(Branch $branch, ?int $year = null, ?int $month = null): Collection
    {
        $rek1Map = $this->branchCashDailyRek1($branch, $year, $month)->keyBy('date');

        $query = CashEntry::query()
            ->where('branch_id', $branch->id)
            ->where('account_type', 'rek2');

        if ($year && $month) {
            $start = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
            $end = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();
            $query->whereBetween('entry_date', [$start, $end]);
        }

        $entries = $query->orderBy('entry_date')->orderBy('id')->get();
        $rek2Grouped = $entries->groupBy(fn (CashEntry $e) => $e->entry_date->format('Y-m-d'));

        $allDates = $rek1Map->keys()->merge($rek2Grouped->keys())->unique()->sort()->values();

        return $allDates->map(function (string $date) use ($rek1Map, $rek2Grouped) {
            $dayEntries = $rek2Grouped->get($date, collect());
            $marginFromRek1 = (float) ($rek1Map->get($date)['margin_amount'] ?? 0);

            $otherIncome = $dayEntries
                ->filter(fn (CashEntry $e) => ! str_contains(strtolower($e->description), 'margin'))
                ->sum('debit');

            $otherExpense = $dayEntries
                ->filter(function (CashEntry $e) {
                    $upper = strtoupper($e->description);
                    $isBcaDeposit = $e->category === 'bca_deposit'
                        || (str_contains($upper, 'BCA') && (str_contains($upper, 'SETOR') || str_contains($upper, 'STOR')));

                    return ! $isBcaDeposit;
                })
                ->sum('credit');

            $storBca = $marginFromRek1 + $otherIncome - $otherExpense;

            return [
                'date' => $date,
                'margin_from_rek1' => (float) $marginFromRek1,
                'other_income' => (float) $otherIncome,
                'other_expense' => (float) $otherExpense,
                'stor_bca' => (float) $storBca,
            ];
        });
    }

    public function dailyCashProjection(int $year, int $month, ?int $branchId = null): array
    {
        $setting = CashProjectionSetting::firstOrCreate(
            ['year' => $year, 'month' => $month],
            ['opening_balance_rek1' => 0, 'opening_balance_rek2' => 0]
        );

        $branches = Branch::query()
            ->where('is_active', true)
            ->when($branchId, fn ($q) => $q->where('id', $branchId))
            ->orderBy('id')
            ->get();

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $daysInMonth = $start->daysInMonth;

        // Preload Tebus BBM (non-LPG/Oli)
        $tebusByDate = FuelRedemption::query()
            ->where('is_non_bbm', false)
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->get()
            ->groupBy(fn (FuelRedemption $r) => $r->transaction_date->format('Y-m-d'))
            ->map(fn (Collection $rows) => (float) $rows->sum('total_amount'));

        // Preload Paid Credit Sales (BBM)
        $paidByDate = CreditSale::query()
            ->where('is_non_bbm', false)
            ->whereNotNull('paid_date')
            ->whereBetween('paid_date', [$start->toDateString(), $end->toDateString()])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->get()
            ->groupBy(fn (CreditSale $s) => $s->paid_date->format('Y-m-d'))
            ->map(fn (Collection $rows) => (float) $rows->sum('total_amount'));

        // Preload Branch Daily Rek 1 & Rek 2 maps
        $rek1BranchMaps = [];
        $rek2BranchMaps = [];
        $rek2TotalIncome = 0;
        $rek2TotalExpense = 0;

        foreach ($branches as $branch) {
            $r1 = $this->branchCashDailyRek1($branch, $year, $month)->keyBy('date');
            $r2 = $this->branchCashDailyRek2($branch, $year, $month)->keyBy('date');
            $rek1BranchMaps[$branch->id] = $r1;
            $rek2BranchMaps[$branch->id] = $r2;

            $rek2TotalIncome += $r2->sum('margin_from_rek1') + $r2->sum('other_income');
            $rek2TotalExpense += $r2->sum('other_expense');
        }

        $openRek1 = (float) $setting->opening_balance_rek1;
        $openRek2 = (float) $setting->opening_balance_rek2;
        $runningRek1 = $openRek1;
        $runningRek2 = $openRek2;

        $rek1Rows = [];
        $rek2Rows = [];

        $rek1Summary = [
            'opening_balance' => $openRek1,
            'total_tebus' => 0.0,
            'total_pembayaran' => 0.0,
            'total_stor_bca' => 0.0,
            'total_pemasukan' => 0.0,
            'total_pengeluaran' => 0.0,
            'net_flow' => 0.0,
            'closing_balance' => $openRek1,
            'peak_net_used' => 0.0,
            'peak_additional_cash' => 0.0,
        ];

        $rek2Summary = [
            'opening_balance' => $openRek2,
            'total_stor_bca' => 0.0,
            'total_pemasukan' => (float) $rek2TotalIncome,
            'total_pengeluaran' => (float) $rek2TotalExpense,
            'net_flow' => 0.0,
            'closing_balance' => $openRek2,
            'peak_net_used' => 0.0,
            'peak_additional_cash' => 0.0,
        ];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::create($year, $month, $day)->format('Y-m-d');

            // Rek 1
            $tebus = (float) ($tebusByDate->get($date) ?? 0);
            $pembayaran = (float) ($paidByDate->get($date) ?? 0);

            $storByBranch1 = [];
            $totalStor1 = 0.0;
            foreach ($branches as $branch) {
                $val = (float) ($rek1BranchMaps[$branch->id]->get($date)['stor_bca'] ?? 0);
                $storByBranch1[$branch->code] = $val;
                $totalStor1 += $val;
            }

            $net1 = $pembayaran + $totalStor1 - $tebus;
            $start1 = $runningRek1;
            $end1 = $start1 + $net1;
            $netUsed1 = max(0, $openRek1 - $end1);
            $addCash1 = max(0, -$end1);
            $runningRek1 = $end1;

            $rek1Rows[] = [
                'date' => $date,
                'tebus_bbm' => $tebus,
                'pembayaran_masuk' => $pembayaran,
                'stor_by_branch' => $storByBranch1,
                'total_stor_bca' => $totalStor1,
                'net_flow' => $net1,
                'opening_balance' => $start1,
                'closing_balance' => $end1,
                'net_used' => $netUsed1,
                'additional_cash' => $addCash1,
            ];

            $rek1Summary['total_tebus'] += $tebus;
            $rek1Summary['total_pembayaran'] += $pembayaran;
            $rek1Summary['total_stor_bca'] += $totalStor1;
            $rek1Summary['total_pemasukan'] += ($pembayaran + $totalStor1);
            $rek1Summary['total_pengeluaran'] += $tebus;
            $rek1Summary['net_flow'] += $net1;
            $rek1Summary['closing_balance'] = $end1;
            $rek1Summary['peak_net_used'] = max($rek1Summary['peak_net_used'], $netUsed1);
            $rek1Summary['peak_additional_cash'] = max($rek1Summary['peak_additional_cash'], $addCash1);

            // Rek 2
            $storByBranch2 = [];
            $totalStor2 = 0.0;
            foreach ($branches as $branch) {
                $val = (float) ($rek2BranchMaps[$branch->id]->get($date)['stor_bca'] ?? 0);
                $storByBranch2[$branch->code] = $val;
                $totalStor2 += $val;
            }

            $net2 = $totalStor2;
            $start2 = $runningRek2;
            $end2 = $start2 + $net2;
            $netUsed2 = max(0, $openRek2 - $end2);
            $addCash2 = max(0, -$end2);
            $runningRek2 = $end2;

            $rek2Rows[] = [
                'date' => $date,
                'stor_by_branch' => $storByBranch2,
                'total_stor_bca' => $totalStor2,
                'net_flow' => $net2,
                'opening_balance' => $start2,
                'closing_balance' => $end2,
                'net_used' => $netUsed2,
                'additional_cash' => $addCash2,
            ];

            $rek2Summary['total_stor_bca'] += $totalStor2;
            $rek2Summary['net_flow'] += $net2;
            $rek2Summary['closing_balance'] = $end2;
            $rek2Summary['peak_net_used'] = max($rek2Summary['peak_net_used'], $netUsed2);
            $rek2Summary['peak_additional_cash'] = max($rek2Summary['peak_additional_cash'], $addCash2);
        }

        return [
            'period' => [
                'year' => $year,
                'month' => $month,
                'days_in_month' => $daysInMonth,
                'label' => $start->translatedFormat('F Y'),
            ],
            'setting' => $setting,
            'branches' => $branches->map(fn (Branch $b) => [
                'id' => $b->id,
                'code' => $b->code,
                'name' => $b->name,
                'margin_rate' => $b->margin_rate,
            ])->values(),
            'rek1' => [
                'rows' => $rek1Rows,
                'summary' => $rek1Summary,
            ],
            'rek2' => [
                'rows' => $rek2Rows,
                'summary' => $rek2Summary,
            ],
            'combined' => [
                'opening_balance' => $rek1Summary['opening_balance'] + $rek2Summary['opening_balance'],
                'total_pemasukan' => $rek1Summary['total_pemasukan'] + $rek2Summary['total_pemasukan'],
                'total_pengeluaran' => $rek1Summary['total_pengeluaran'] + $rek2Summary['total_pengeluaran'],
                'closing_balance' => $rek1Summary['closing_balance'] + $rek2Summary['closing_balance'],
                'peak_additional_cash' => $rek1Summary['peak_additional_cash'] + $rek2Summary['peak_additional_cash'],
            ],
        ];
    }

    public function dashboardSummary(int $year, int $month, ?int $branchId = null): array
    {
        $tebusQuery = FuelRedemption::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        $salesQuery = CreditSale::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        $totalModalTebus = (float) (clone $tebusQuery)->sum('total_amount');
        $totalVolumeTebus = (float) (clone $tebusQuery)->sum('volume_liters');

        $totalPenjualanKotor = (float) (clone $salesQuery)->sum('total_amount');
        $totalVolumeJual = (float) (clone $salesQuery)->sum('volume_liters');
        $totalPiutangBelumLunas = (float) (clone $salesQuery)->where('payment_status', 'Belum Lunas')->sum('total_amount');
        $totalPiutangSudahLunas = (float) (clone $salesQuery)->where('payment_status', 'Lunas')->sum('total_amount');
        $estimasiKeuntunganKotor = $totalPenjualanKotor - $totalModalTebus;

        $projection = $this->dailyCashProjection($year, $month, $branchId);

        // Branch breakdown
        $branches = Branch::query()
            ->where('is_active', true)
            ->when($branchId, fn ($q) => $q->where('id', $branchId))
            ->orderBy('id')
            ->get();

        $branchCards = $branches->map(function (Branch $branch) use ($year, $month) {
            $tebusTotal = (float) FuelRedemption::where('branch_id', $branch->id)->sum('total_amount');
            $tebusLiters = (float) FuelRedemption::where('branch_id', $branch->id)->sum('volume_liters');
            $piutangOpen = (float) CreditSale::where('branch_id', $branch->id)->where('payment_status', 'Belum Lunas')->sum('total_amount');
            $rek1Daily = $this->branchCashDailyRek1($branch, $year, $month);
            $rek2Daily = $this->branchCashDailyRek2($branch, $year, $month);

            return [
                'id' => $branch->id,
                'code' => $branch->code,
                'name' => $branch->name,
                'margin_rate' => $branch->margin_rate,
                'tebus_total' => $tebusTotal,
                'tebus_liters' => $tebusLiters,
                'piutang_belum_lunas' => $piutangOpen,
                'omzet_rek1' => (float) $rek1Daily->sum('total_omzet'),
                'margin_rek1' => (float) $rek1Daily->sum('margin_amount'),
                'stor_bca_rek1' => (float) $rek1Daily->sum('stor_bca'),
                'stor_bca_rek2' => (float) $rek2Daily->sum('stor_bca'),
            ];
        });

        // Unpaid receivables list (urgent / nearest due date)
        $unpaidReceivables = CreditSale::query()
            ->with('branch:id,code,name')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('payment_status', 'Belum Lunas')
            ->orderBy('due_date')
            ->limit(6)
            ->get();

        return [
            'kpis' => [
                'total_modal_tebus' => $totalModalTebus,
                'total_volume_tebus' => $totalVolumeTebus,
                'total_penjualan_kotor' => $totalPenjualanKotor,
                'total_volume_jual' => $totalVolumeJual,
                'total_piutang_belum_lunas' => $totalPiutangBelumLunas,
                'total_piutang_sudah_lunas' => $totalPiutangSudahLunas,
                'estimasi_keuntungan_kotor' => $estimasiKeuntunganKotor,
                'dana_neto_rek1_puncak' => $projection['rek1']['summary']['peak_net_used'],
                'tambahan_kas_rek1_puncak' => $projection['rek1']['summary']['peak_additional_cash'],
            ],
            'accounts' => [
                'rek1' => $projection['rek1']['summary'],
                'rek2' => $projection['rek2']['summary'],
                'combined' => $projection['combined'],
            ],
            'period' => $projection['period'],
            'branch_cards' => $branchCards,
            'unpaid_receivables' => $unpaidReceivables,
        ];
    }
}
