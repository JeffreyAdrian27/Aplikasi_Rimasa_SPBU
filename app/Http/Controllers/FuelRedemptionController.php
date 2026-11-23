<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\FuelRedemption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FuelRedemptionController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $branchId = $user->isAdminCabang()
            ? $user->branch_id
            : ($request->filled('branch_id') ? (int) $request->integer('branch_id') : null);

        $product = $request->string('product')->toString();

        $query = FuelRedemption::query()
            ->with('branch:id,code,name')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($product !== '', fn ($q) => $q->where('product_name', $product))
            ->orderByDesc('transaction_date')
            ->orderByDesc('id');

        $redemptions = $query->get();

        $branchTotals = Branch::query()
            ->where('is_active', true)
            ->when($user->isAdminCabang(), fn ($q) => $q->where('id', $user->branch_id))
            ->orderBy('id')
            ->get()
            ->map(function (Branch $b) {
                $rows = FuelRedemption::where('branch_id', $b->id);

                return [
                    'id' => $b->id,
                    'name' => $b->name,
                    'total_liters' => (float) (clone $rows)->sum('volume_liters'),
                    'total_amount' => (float) (clone $rows)->sum('total_amount'),
                ];
            });

        return Inertia::render('Tebus/Index', [
            'redemptions' => $redemptions,
            'branchTotals' => $branchTotals,
            'totals' => [
                'liters' => (float) $redemptions->sum('volume_liters'),
                'amount' => (float) $redemptions->sum('total_amount'),
            ],
            'filters' => [
                'branch_id' => $branchId,
                'product' => $product,
            ],
            'products' => ['Solar', 'Pertalite', 'Pertamax', 'Dexlite', 'LPG', 'Oli'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'transaction_date' => ['required', 'date'],
            'do_number' => ['nullable', 'string', 'max:64'],
            'product_name' => ['required', 'string', 'max:64'],
            'volume_liters' => ['required', 'numeric', 'min:0.01'],
            'price_per_liter' => ['required', 'numeric', 'min:0'],
            'payment_status' => ['required', 'string', 'max:48'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        if ($user->isAdminCabang()) {
            $data['branch_id'] = $user->branch_id;
        }

        $data['is_non_bbm'] = FuelRedemption::detectNonBbm($data['product_name']);
        $data['total_amount'] = round((float) $data['volume_liters'] * (float) $data['price_per_liter']);
        $data['created_by'] = $user->id;

        FuelRedemption::create($data);

        return back()->with('success', 'Transaksi tebus Pertamina berhasil disimpan.');
    }

    public function destroy(Request $request, FuelRedemption $fuelRedemption): RedirectResponse
    {
        $user = $request->user();
        if ($user->isAdminCabang() && $fuelRedemption->branch_id !== $user->branch_id) {
            abort(403);
        }

        $fuelRedemption->delete();

        return back()->with('success', 'Data tebus Pertamina berhasil dihapus.');
    }
}
