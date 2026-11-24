<?php

namespace App\Http\Controllers;

use App\Models\CashProjectionSetting;
use App\Services\SpbuFinanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CashProjectionController extends Controller
{
    public function __construct(private readonly SpbuFinanceService $finance) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $year = (int) $request->integer('year', 2026);
        $month = (int) $request->integer('month', 11);

        $branchId = $user->isAdminCabang() ? $user->branch_id : null;

        $projection = $this->finance->dailyCashProjection($year, $month, $branchId);

        return Inertia::render('Proyeksi/Index', [
            'projection' => $projection,
            'filters' => [
                'year' => $year,
                'month' => $month,
            ],
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2099'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'opening_balance_rek1' => ['required', 'numeric'],
            'opening_balance_rek2' => ['required', 'numeric'],
        ]);

        CashProjectionSetting::updateOrCreate(
            ['year' => $data['year'], 'month' => $data['month']],
            [
                'opening_balance_rek1' => $data['opening_balance_rek1'],
                'opening_balance_rek2' => $data['opening_balance_rek2'],
            ]
        );

        return back()->with('success', 'Saldo awal periode berhasil diperbarui.');
    }
}
