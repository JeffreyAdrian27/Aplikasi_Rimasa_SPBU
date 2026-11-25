<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\FuelRedemption;
use App\Models\ProductBasePrice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            ->orderBy('transaction_date')
            ->orderBy('id');

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
                'tax' => (float) $redemptions->sum('tax_amount'),
                'amount' => (float) $redemptions->sum('total_amount'),
            ],
            'filters' => [
                'branch_id' => $branchId,
                'product' => $product,
            ],
            'products' => ['Solar', 'Pertalite', 'Pertamax', 'Pertamina Dex', 'LPG', 'Oli'],
            'basePrices' => ProductBasePrice::getMap(),
        ]);
    }

    public function updateBasePrices(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'prices' => ['required', 'array'],
            'prices.*' => ['required', 'numeric', 'min:0'],
        ]);

        foreach ($data['prices'] as $productName => $price) {
            ProductBasePrice::updateOrCreate(
                ['product_name' => $productName],
                ['base_price' => round((float) $price, 2)]
            );
        }

        return back()->with('success', 'Harga dasar PPh 22 produk berhasil diperbarui.');
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
            'tax_per_8kl' => ['nullable', 'numeric', 'min:0'],
            'payment_status' => ['required', 'string', 'max:48'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        if ($user->isAdminCabang()) {
            $data['branch_id'] = $user->branch_id;
        }

        $basePrice = array_key_exists('tax_per_8kl', $data) && $data['tax_per_8kl'] !== null
            ? (float) $data['tax_per_8kl']
            : FuelRedemption::defaultBasePrice($data['product_name']);
        $taxAmount = FuelRedemption::calculateTax((float) $data['volume_liters'], $basePrice);

        $data['is_non_bbm'] = FuelRedemption::detectNonBbm($data['product_name']);
        $data['tax_per_8kl'] = $basePrice;
        $data['tax_amount'] = $taxAmount;
        $data['total_amount'] = round(((float) $data['volume_liters'] * (float) $data['price_per_liter']) + $taxAmount);
        $data['created_by'] = $user->id;

        FuelRedemption::create($data);

        return back()->with('success', 'Transaksi tebus Pertamina berhasil disimpan.');
    }

    public function storeBatch(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'transaction_date' => ['required', 'date'],
            'payment_status' => ['required', 'string', 'max:48'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.do_number' => ['nullable', 'string', 'max:64'],
            'items.*.product_name' => ['required', 'string', 'max:64'],
            'items.*.volume_liters' => ['required', 'numeric', 'min:0.01'],
            'items.*.price_per_liter' => ['required', 'numeric', 'min:0'],
            'items.*.tax_per_8kl' => ['nullable', 'numeric', 'min:0'],
        ]);

        $branchId = $user->isAdminCabang() ? $user->branch_id : (int) $data['branch_id'];

        DB::transaction(function () use ($data, $branchId, $user) {
            foreach ($data['items'] as $item) {
                $vol = (float) $item['volume_liters'];
                $price = (float) $item['price_per_liter'];
                $basePrice = array_key_exists('tax_per_8kl', $item) && $item['tax_per_8kl'] !== null
                    ? (float) $item['tax_per_8kl']
                    : FuelRedemption::defaultBasePrice($item['product_name']);
                $taxAmount = FuelRedemption::calculateTax($vol, $basePrice);

                FuelRedemption::create([
                    'branch_id' => $branchId,
                    'transaction_date' => $data['transaction_date'],
                    'do_number' => $item['do_number'] ? trim($item['do_number']) : null,
                    'product_name' => $item['product_name'],
                    'is_non_bbm' => FuelRedemption::detectNonBbm($item['product_name']),
                    'volume_liters' => $vol,
                    'price_per_liter' => $price,
                    'tax_per_8kl' => $basePrice,
                    'tax_amount' => $taxAmount,
                    'total_amount' => round(($vol * $price) + $taxAmount),
                    'payment_status' => $data['payment_status'],
                    'created_by' => $user->id,
                ]);
            }
        });

        $count = count($data['items']);

        return back()->with('success', "{$count} transaksi tebus Pertamina berhasil disimpan sekaligus.");
    }

    public function update(Request $request, FuelRedemption $fuelRedemption): RedirectResponse
    {
        $user = $request->user();
        if ($user->isAdminCabang() && $fuelRedemption->branch_id !== $user->branch_id) {
            abort(403);
        }

        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'transaction_date' => ['required', 'date'],
            'do_number' => ['nullable', 'string', 'max:64'],
            'product_name' => ['required', 'string', 'max:64'],
            'volume_liters' => ['required', 'numeric', 'min:0.01'],
            'price_per_liter' => ['required', 'numeric', 'min:0'],
            'tax_per_8kl' => ['nullable', 'numeric', 'min:0'],
            'payment_status' => ['required', 'string', 'max:48'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        if ($user->isAdminCabang()) {
            $data['branch_id'] = $user->branch_id;
        }

        $basePrice = array_key_exists('tax_per_8kl', $data) && $data['tax_per_8kl'] !== null
            ? (float) $data['tax_per_8kl']
            : FuelRedemption::defaultBasePrice($data['product_name']);
        $taxAmount = FuelRedemption::calculateTax((float) $data['volume_liters'], $basePrice);

        $data['is_non_bbm'] = FuelRedemption::detectNonBbm($data['product_name']);
        $data['tax_per_8kl'] = $basePrice;
        $data['tax_amount'] = $taxAmount;
        $data['total_amount'] = round(((float) $data['volume_liters'] * (float) $data['price_per_liter']) + $taxAmount);

        $fuelRedemption->update($data);

        return back()->with('success', 'Transaksi tebus Pertamina berhasil diperbarui.');
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
