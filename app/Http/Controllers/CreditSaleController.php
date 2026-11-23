<?php

namespace App\Http\Controllers;

use App\Models\CreditSale;
use App\Models\FuelRedemption;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CreditSaleController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $branchId = $user->isAdminCabang()
            ? $user->branch_id
            : ($request->filled('branch_id') ? (int) $request->integer('branch_id') : null);

        $status = $request->string('status')->toString();
        $search = trim($request->string('search')->toString());

        $sales = CreditSale::query()
            ->with('branch:id,code,name')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($status !== '', fn ($q) => $q->where('payment_status', $status))
            ->when($search !== '', fn ($q) => $q->where('client_name', 'like', "%{$search}%"))
            ->orderBy('sale_date')
            ->orderBy('id')
            ->get();

        // Rekap Perusahaan (matching Excel "Rekap Perusahaan" sheet)
        $allScopedSales = CreditSale::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->get();

        $companyRecap = $allScopedSales
            ->groupBy('client_name')
            ->map(function ($rows, $clientName) {
                $total = (float) $rows->sum('total_amount');
                $paid = (float) $rows->where('payment_status', 'Lunas')->sum('total_amount');
                $unpaid = (float) $rows->where('payment_status', 'Belum Lunas')->sum('total_amount');

                return [
                    'client_name' => $clientName,
                    'invoice_count' => $rows->count(),
                    'unpaid_count' => $rows->where('payment_status', 'Belum Lunas')->count(),
                    'total_sales' => $total,
                    'total_paid' => $paid,
                    'remaining_receivable' => $unpaid,
                ];
            })
            ->sortByDesc('remaining_receivable')
            ->values();

        return Inertia::render('Penjualan/Index', [
            'sales' => $sales,
            'companyRecap' => $companyRecap,
            'totals' => [
                'liters' => (float) $allScopedSales->sum('volume_liters'),
                'total_amount' => (float) $allScopedSales->sum('total_amount'),
                'unpaid_amount' => (float) $allScopedSales->where('payment_status', 'Belum Lunas')->sum('total_amount'),
                'paid_amount' => (float) $allScopedSales->where('payment_status', 'Lunas')->sum('total_amount'),
            ],
            'filters' => [
                'branch_id' => $branchId,
                'status' => $status,
                'search' => $search,
            ],
            'products' => ['Solar', 'Pertalite', 'Pertamax', 'Dexlite', 'LPG', 'Oli'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'sale_date' => ['required', 'date'],
            'client_name' => ['required', 'string', 'max:120'],
            'product_name' => ['required', 'string', 'max:64'],
            'volume_liters' => ['required', 'numeric', 'min:0.01'],
            'price_per_liter' => ['required', 'numeric', 'min:0'],
            'credit_term_days' => ['required', 'integer', 'min:0', 'max:365'],
            'paid_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        if ($user->isAdminCabang()) {
            $data['branch_id'] = $user->branch_id;
        }

        $saleDate = Carbon::parse($data['sale_date']);
        $data['is_non_bbm'] = FuelRedemption::detectNonBbm($data['product_name']);
        $data['total_amount'] = round((float) $data['volume_liters'] * (float) $data['price_per_liter']);
        $data['due_date'] = $saleDate->copy()->addDays((int) $data['credit_term_days'])->toDateString();
        $data['payment_status'] = ! empty($data['paid_date']) ? 'Lunas' : 'Belum Lunas';
        $data['created_by'] = $user->id;

        CreditSale::create($data);

        return back()->with('success', 'Tagihan penjualan klien berhasil ditambahkan.');
    }

    public function update(Request $request, CreditSale $creditSale): RedirectResponse
    {
        $user = $request->user();
        if ($user->isAdminCabang() && $creditSale->branch_id !== $user->branch_id) {
            abort(403);
        }

        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'sale_date' => ['required', 'date'],
            'client_name' => ['required', 'string', 'max:120'],
            'product_name' => ['required', 'string', 'max:64'],
            'volume_liters' => ['required', 'numeric', 'min:0.01'],
            'price_per_liter' => ['required', 'numeric', 'min:0'],
            'credit_term_days' => ['required', 'integer', 'min:0', 'max:365'],
            'paid_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        if ($user->isAdminCabang()) {
            $data['branch_id'] = $user->branch_id;
        }

        $saleDate = Carbon::parse($data['sale_date']);
        $data['is_non_bbm'] = FuelRedemption::detectNonBbm($data['product_name']);
        $data['total_amount'] = round((float) $data['volume_liters'] * (float) $data['price_per_liter']);
        $data['due_date'] = $saleDate->copy()->addDays((int) $data['credit_term_days'])->toDateString();
        $data['payment_status'] = ! empty($data['paid_date']) ? 'Lunas' : 'Belum Lunas';

        $creditSale->update($data);

        return back()->with('success', 'Tagihan penjualan klien berhasil diperbarui.');
    }

    public function markPaid(Request $request, CreditSale $creditSale): RedirectResponse
    {
        $user = $request->user();
        if ($user->isAdminCabang() && $creditSale->branch_id !== $user->branch_id) {
            abort(403);
        }

        $validated = $request->validate([
            'paid_date' => ['nullable', 'date'],
        ]);

        $paidDate = $validated['paid_date'] ?? now()->toDateString();

        $creditSale->update([
            'paid_date' => $paidDate,
            'payment_status' => 'Lunas',
        ]);

        return back()->with('success', "Tagihan {$creditSale->client_name} ditandai Lunas.");
    }

    public function markUnpaid(Request $request, CreditSale $creditSale): RedirectResponse
    {
        $user = $request->user();
        if ($user->isAdminCabang() && $creditSale->branch_id !== $user->branch_id) {
            abort(403);
        }

        $creditSale->update([
            'paid_date' => null,
            'payment_status' => 'Belum Lunas',
        ]);

        return back()->with('success', "Status tagihan {$creditSale->client_name} dikembalikan ke Belum Lunas.");
    }

    public function destroy(Request $request, CreditSale $creditSale): RedirectResponse
    {
        $user = $request->user();
        if ($user->isAdminCabang() && $creditSale->branch_id !== $user->branch_id) {
            abort(403);
        }

        $creditSale->delete();

        return back()->with('success', 'Data penjualan berhasil dihapus.');
    }
}
