<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CashEntry;
use App\Services\SpbuFinanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CashEntryController extends Controller
{
    public function __construct(private readonly SpbuFinanceService $finance) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $availableBranches = Branch::query()
            ->where('is_active', true)
            ->when($user->isAdminCabang(), fn ($q) => $q->where('id', $user->branch_id))
            ->orderBy('id')
            ->get();

        $selectedBranchId = $user->isAdminCabang()
            ? $user->branch_id
            : ($request->filled('branch_id') ? (int) $request->integer('branch_id') : $availableBranches->first()?->id);

        $branch = $availableBranches->firstWhere('id', $selectedBranchId) ?? $availableBranches->firstOrFail();

        $accountType = in_array($request->string('account')->toString(), ['rek1', 'rek2'], true)
            ? $request->string('account')->toString()
            : 'rek1';

        $year = (int) $request->integer('year', 2026);
        $month = (int) $request->integer('month', 11);

        $start = sprintf('%04d-%02d-01', $year, $month);
        $end = date('Y-m-t', strtotime($start));

        $entries = CashEntry::query()
            ->where('branch_id', $branch->id)
            ->where('account_type', $accountType)
            ->whereBetween('entry_date', [$start, $end])
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $dailySummary = $accountType === 'rek1'
            ? $this->finance->branchCashDailyRek1($branch, $year, $month)
            : $this->finance->branchCashDailyRek2($branch, $year, $month);

        return Inertia::render('Kas/Index', [
            'branch' => $branch,
            'accountType' => $accountType,
            'entries' => $entries,
            'dailySummary' => $dailySummary,
            'filters' => [
                'branch_id' => $branch->id,
                'account' => $accountType,
                'year' => $year,
                'month' => $month,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'account_type' => ['required', 'in:rek1,rek2'],
            'entry_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:200'],
            'category' => ['required', 'in:shift_omzet,tsps_tukar,non_bbm_income,operational_expense,bca_deposit,other'],
            'debit' => ['required', 'numeric', 'min:0'],
            'credit' => ['required', 'numeric', 'min:0'],
        ]);

        if ($user->isAdminCabang()) {
            $data['branch_id'] = $user->branch_id;
        }

        $data['created_by'] = $user->id;

        CashEntry::create($data);

        return back()->with('success', 'Mutasi kas harian berhasil dicatat.');
    }

    public function storeBatch(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'account_type' => ['required', 'in:rek1,rek2'],
            'entry_date' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:200'],
            'items.*.category' => ['required', 'in:shift_omzet,tsps_tukar,non_bbm_income,operational_expense,bca_deposit,other'],
            'items.*.debit' => ['nullable', 'numeric', 'min:0'],
            'items.*.credit' => ['nullable', 'numeric', 'min:0'],
        ]);

        $branchId = $user->isAdminCabang() ? $user->branch_id : (int) $data['branch_id'];

        $validItems = collect($data['items'])
            ->filter(fn (array $item) => ((float) ($item['debit'] ?? 0)) > 0 || ((float) ($item['credit'] ?? 0)) > 0)
            ->values();

        if ($validItems->isEmpty()) {
            return back()->withErrors([
                'items' => 'Isi minimal satu baris dengan nominal Debet atau Kredit lebih dari 0.',
            ]);
        }

        DB::transaction(function () use ($validItems, $branchId, $data, $user) {
            foreach ($validItems as $item) {
                CashEntry::create([
                    'branch_id' => $branchId,
                    'account_type' => $data['account_type'],
                    'entry_date' => $data['entry_date'],
                    'description' => trim($item['description']),
                    'category' => $item['category'],
                    'debit' => (float) ($item['debit'] ?? 0),
                    'credit' => (float) ($item['credit'] ?? 0),
                    'created_by' => $user->id,
                ]);
            }
        });

        return back()->with('success', "{$validItems->count()} baris mutasi kas berhasil disimpan sekaligus.");
    }

    public function update(Request $request, CashEntry $cashEntry): RedirectResponse
    {
        $user = $request->user();
        if ($user->isAdminCabang() && $cashEntry->branch_id !== $user->branch_id) {
            abort(403);
        }

        $data = $request->validate([
            'entry_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:200'],
            'debit' => ['required', 'numeric', 'min:0'],
            'credit' => ['required', 'numeric', 'min:0'],
        ]);

        $cashEntry->update([
            'entry_date' => $data['entry_date'],
            'description' => trim($data['description']),
            'debit' => (float) $data['debit'],
            'credit' => (float) $data['credit'],
        ]);

        return back()->with('success', 'Mutasi kas berhasil diperbarui.');
    }

    public function destroy(Request $request, CashEntry $cashEntry): RedirectResponse
    {
        $user = $request->user();
        if ($user->isAdminCabang() && $cashEntry->branch_id !== $user->branch_id) {
            abort(403);
        }

        $cashEntry->delete();

        return back()->with('success', 'Mutasi kas berhasil dihapus.');
    }
}
