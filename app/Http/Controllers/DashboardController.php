<?php

namespace App\Http\Controllers;

use App\Services\SpbuFinanceService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly SpbuFinanceService $finance) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $year = (int) $request->integer('year', 2026);
        $month = (int) $request->integer('month', 11);

        $branchId = $user->isAdminCabang()
            ? $user->branch_id
            : ($request->filled('branch_id') ? (int) $request->integer('branch_id') : null);

        $summary = $this->finance->dashboardSummary($year, $month, $branchId);

        return Inertia::render('Dashboard', [
            'summary' => $summary,
            'filters' => [
                'year' => $year,
                'month' => $month,
                'branch_id' => $branchId,
            ],
        ]);
    }
}
