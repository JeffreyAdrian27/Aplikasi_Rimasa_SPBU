<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DatePaginationBar from '@/Components/DatePaginationBar.vue';
import { useDatePagination } from '@/composables/useDatePagination';
import { formatRupiah, formatNumber, formatDate } from '@/utils/format';
import { Plus, Minus, Pencil, Trash2, X, SlidersHorizontal, Save } from 'lucide-vue-next';

const props = defineProps({
    redemptions: Array,
    branchTotals: Array,
    totals: Object,
    filters: Object,
    products: Array,
    basePrices: Object,
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const branches = computed(() => page.props.branches || []);

const showBatchForm = ref(false);
const showBasePriceForm = ref(false);
const editingId = ref(null);

const defaultBranchId = computed(() =>
    user.value?.role === 'admin_cabang'
        ? user.value.branch_id
        : (props.filters.branch_id || branches.value[0]?.id || '')
);

const defaultBasePrices = computed(() => ({
    Solar: 5862.07,
    Pertalite: 8620.69,
    Pertamax: 13750,
    'Pertamina Dex': 21724.14,
    LPG: 0,
    Oli: 0,
    ...(props.basePrices || {}),
}));

const basePriceForm = useForm({
    prices: {
        Solar: props.basePrices?.Solar ?? 5862.07,
        Pertalite: props.basePrices?.Pertalite ?? 8620.69,
        Pertamax: props.basePrices?.Pertamax ?? 13750,
        'Pertamina Dex': props.basePrices?.['Pertamina Dex'] ?? 21724.14,
    },
});

const saveBasePrices = () => {
    basePriceForm.patch(route('tebus.base-prices.update'), {
        preserveScroll: true,
        onSuccess: () => {
            showBasePriceForm.value = false;
            batchForm.items.forEach((item) => {
                if (basePriceForm.prices[item.product_name] !== undefined) {
                    item.tax_per_8kl = Number(basePriceForm.prices[item.product_name]);
                }
            });
        },
    });
};

const PPH22_RATE = 0.0025; // 0,25%

const calcTaxAmount = (vol, basePrice) =>
    Math.round((Number(vol) || 0) * (Number(basePrice) || 0) * PPH22_RATE);
const calcRowTotal = (vol, price, basePrice) =>
    Math.round(((Number(vol) || 0) * (Number(price) || 0)) + calcTaxAmount(vol, basePrice));

const makeItem = (product = 'Solar') => ({
    do_number: '',
    product_name: product,
    volume_liters: 8000,
    price_per_liter: '',
    tax_per_8kl: defaultBasePrices.value[product] ?? 0,
});

// Multi-product batch create form
const batchForm = useForm({
    branch_id: defaultBranchId.value,
    transaction_date: '2026-11-05',
    payment_status: 'Lunas (Tunai)',
    items: [makeItem('Solar')],
});

// Single-row edit form
const editForm = useForm({
    branch_id: defaultBranchId.value,
    transaction_date: '2026-11-05',
    do_number: '',
    product_name: 'Solar',
    volume_liters: 8000,
    price_per_liter: '',
    tax_per_8kl: defaultBasePrices.value.Solar ?? 5862.07,
    payment_status: 'Lunas (Tunai)',
    notes: '',
});

const isBbmProduct = (name) => !['LPG', 'Oli'].includes(name);

const stepVolume = (target, delta) => {
    const step = isBbmProduct(target.product_name) ? 8000 : 1;
    const min = step;
    const next = (Number(target.volume_liters) || 0) + (delta * step);
    target.volume_liters = Math.max(min, Math.round(next / step) * step);
};

const onBatchProductChange = (item) => {
    if (defaultBasePrices.value[item.product_name] !== undefined) {
        item.tax_per_8kl = defaultBasePrices.value[item.product_name];
    }
    if (isBbmProduct(item.product_name) && (Number(item.volume_liters) < 8000 || Number(item.volume_liters) % 8000 !== 0)) {
        item.volume_liters = 8000;
    }
};

const addBatchItem = (product = 'Solar') => {
    batchForm.items.push(makeItem(product));
};

const removeBatchItem = (idx) => {
    if (batchForm.items.length === 1) return;
    batchForm.items.splice(idx, 1);
};

const batchSummary = computed(() => {
    let liters = 0;
    let tax = 0;
    let total = 0;
    for (const item of batchForm.items) {
        const v = Number(item.volume_liters) || 0;
        const t = calcTaxAmount(v, item.tax_per_8kl);
        liters += v;
        tax += t;
        total += calcRowTotal(v, item.price_per_liter, item.tax_per_8kl);
    }
    return { liters, tax, total };
});

const editPreviewTax = computed(() => calcTaxAmount(editForm.volume_liters, editForm.tax_per_8kl));
const editPreviewTotal = computed(() => calcRowTotal(editForm.volume_liters, editForm.price_per_liter, editForm.tax_per_8kl));

const openCreate = () => {
    editingId.value = null;
    if (showBatchForm.value) {
        showBatchForm.value = false;
        return;
    }
    batchForm.branch_id = defaultBranchId.value;
    batchForm.items = [makeItem('Solar')];
    showBatchForm.value = true;
};

const startEdit = (row) => {
    showBatchForm.value = false;
    editingId.value = row.id;
    editForm.branch_id = row.branch_id;
    editForm.transaction_date = row.transaction_date;
    editForm.do_number = row.do_number || '';
    editForm.product_name = row.product_name;
    editForm.volume_liters = Number(row.volume_liters);
    editForm.price_per_liter = Number(row.price_per_liter);
    editForm.tax_per_8kl = Number(row.tax_per_8kl ?? defaultBasePrices.value[row.product_name] ?? 0);
    editForm.payment_status = row.payment_status;
    editForm.notes = row.notes || '';
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const submitBatch = () => {
    batchForm.post(route('tebus.store-batch'), {
        preserveScroll: true,
        onSuccess: () => {
            showBatchForm.value = false;
            batchForm.items = [makeItem('Solar')];
        },
    });
};

const submitEdit = () => {
    editForm.put(route('tebus.update', editingId.value), {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
        },
    });
};

const applyFilter = (key, value) => {
    router.get(route('tebus.index'), {
        ...props.filters,
        [key]: value || undefined,
    }, { preserveState: true, replace: true });
};

const removeRow = (row) => {
    if (!confirm(`Hapus transaksi tebus ${row.product_name} (${formatNumber(row.volume_liters)} L) tanggal ${formatDate(row.transaction_date)}?`)) return;
    router.delete(route('tebus.destroy', row.id), { preserveScroll: true });
};

const tebusPagination = useDatePagination(() => props.redemptions, 'transaction_date', 15);
</script>

<template>
    <Head title="Tebus Pertamina" />
    <AppLayout title="Tebus Pertamina (Modal Tunai)">
        <template #header-actions>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition-colors"
                    @click="showBasePriceForm = !showBasePriceForm"
                >
                    <SlidersHorizontal class="w-3.5 h-3.5" />
                    <span>Harga Dasar PPh</span>
                </button>
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-teal-700 hover:bg-teal-800 text-white text-xs font-semibold transition-colors"
                    @click="openCreate"
                >
                    <X v-if="showBatchForm" class="w-4 h-4" />
                    <Plus v-else class="w-4 h-4" />
                    <span>{{ showBatchForm ? 'Tutup Form' : 'Catat Tebus BBM' }}</span>
                </button>
            </div>
        </template>

        <!-- Editable Base Prices for PPh 22 (0,25%) -->
        <div v-if="showBasePriceForm" class="bg-white border border-slate-200 rounded-xl p-5 space-y-4">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Edit Harga Dasar PPh 22 (0,25%) per Produk</h2>
                    <p class="text-xs text-slate-500">Perubahan harga dasar otomatis berlaku untuk perhitungan PPh 22 pada transaksi tebus berikutnya.</p>
                </div>
                <button type="button" class="p-1.5 rounded-md text-slate-400 hover:text-slate-700" @click="showBasePriceForm = false">
                    <X class="w-4 h-4" />
                </button>
            </div>
            <form class="space-y-4" @submit.prevent="saveBasePrices">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div v-for="prod in ['Solar', 'Pertalite', 'Pertamax', 'Pertamina Dex']" :key="prod" class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                        <label class="block text-xs font-semibold text-slate-800 mb-1.5">{{ prod }}</label>
                        <input
                            v-model.number="basePriceForm.prices[prod]"
                            type="number"
                            step="0.01"
                            min="0"
                            required
                            class="w-full text-right num text-sm rounded-md border border-slate-300 bg-white px-2.5 py-1.5"
                        >
                        <div class="mt-1 text-[11px] text-slate-500 num">
                            PPh 22 / 8k L: <span class="font-semibold text-amber-800">{{ formatRupiah(calcTaxAmount(8000, basePriceForm.prices[prod])) }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2">
                    <button type="button" class="px-3.5 py-2 text-xs font-medium text-slate-600" @click="showBasePriceForm = false">Batal</button>
                    <button type="submit" :disabled="basePriceForm.processing" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-teal-700 hover:bg-teal-800 text-white text-xs font-semibold">
                        <Save class="w-3.5 h-3.5" />
                        <span>{{ basePriceForm.processing ? 'Menyimpan...' : 'Simpan Harga Dasar' }}</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Multi-Product Batch Add Form per Date -->
        <div v-if="showBatchForm" class="bg-white border border-teal-200 rounded-xl p-5 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Input Tebus / DO Pertamina per Tanggal (Multi-Produk)</h2>
                    <p class="text-xs text-slate-500">Pilih tanggal dan cabang, lalu tambahkan 1, 2, atau lebih produk sekaligus. PPh 22 otomatis dihitung 0,25% × Harga Dasar × Volume.</p>
                </div>
            </div>

            <form class="space-y-4" @submit.prevent="submitBatch">
                <!-- Shared Date, Branch, Payment Status -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Tanggal Tebus</label>
                        <input v-model="batchForm.transaction_date" type="date" required class="w-full num text-sm rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div v-if="user?.role === 'owner'">
                        <label class="block text-xs font-medium text-slate-700 mb-1">Cabang</label>
                        <select v-model="batchForm.branch_id" required class="w-full text-sm rounded-lg border border-slate-300 px-3 py-2 bg-white">
                            <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Status Pembayaran</label>
                        <select v-model="batchForm.payment_status" class="w-full text-sm rounded-lg border border-slate-300 px-3 py-2 bg-white">
                            <option value="Lunas (Tunai)">Lunas (Tunai)</option>
                            <option value="Transfer Bank">Transfer Bank</option>
                        </select>
                    </div>
                </div>

                <!-- Quick-Add Product Buttons -->
                <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-slate-100">
                    <span class="text-[11px] font-semibold text-slate-500">Tambah produk di tanggal ini:</span>
                    <button type="button" class="px-2.5 py-1 rounded-md bg-teal-50 hover:bg-teal-100 border border-teal-200 text-xs font-medium text-teal-800" @click="addBatchItem('Solar')">+ Solar</button>
                    <button type="button" class="px-2.5 py-1 rounded-md bg-teal-50 hover:bg-teal-100 border border-teal-200 text-xs font-medium text-teal-800" @click="addBatchItem('Pertalite')">+ Pertalite</button>
                    <button type="button" class="px-2.5 py-1 rounded-md bg-teal-50 hover:bg-teal-100 border border-teal-200 text-xs font-medium text-teal-800" @click="addBatchItem('Pertamax')">+ Pertamax</button>
                    <button type="button" class="px-2.5 py-1 rounded-md bg-slate-100 hover:bg-slate-200 text-xs font-medium text-slate-700" @click="addBatchItem('Pertamina Dex')">+ Pertamina Dex</button>
                </div>

                <!-- Product Rows Table -->
                <div class="overflow-x-auto border border-slate-200 rounded-lg">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-50 font-semibold text-slate-600 border-b border-slate-200">
                            <tr>
                                <th class="text-left px-3 py-2.5">Produk</th>
                                <th class="text-left px-3 py-2.5">No. DO (Opsional)</th>
                                <th class="text-right px-3 py-2.5">Volume (L)</th>
                                <th class="text-right px-3 py-2.5">Harga / L (Rp)</th>
                                <th class="text-right px-3 py-2.5">PPh 22 (0,25%)</th>
                                <th class="text-right px-3 py-2.5">Subtotal Tebus</th>
                                <th class="px-2 py-2.5 w-10" />
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(item, idx) in batchForm.items" :key="idx" class="bg-white">
                                <td class="px-3 py-2 min-w-[130px]">
                                    <select
                                        v-model="item.product_name"
                                        class="w-full text-xs rounded-md border border-slate-300 px-2 py-1.5 bg-white"
                                        @change="onBatchProductChange(item)"
                                    >
                                        <option v-for="p in products" :key="p" :value="p">{{ p }}</option>
                                    </select>
                                </td>
                                <td class="px-3 py-2 min-w-[130px]">
                                    <input v-model="item.do_number" type="text" placeholder="DO-..." class="w-full text-xs rounded-md border border-slate-300 px-2 py-1.5">
                                </td>
                                <td class="px-3 py-2 min-w-[155px]">
                                    <div class="flex items-center gap-1">
                                        <button
                                            type="button"
                                            class="w-7 h-7 shrink-0 inline-flex items-center justify-center rounded-md border border-slate-300 bg-slate-50 hover:bg-slate-100 text-slate-700"
                                            aria-label="Kurangi 8.000 liter"
                                            @click="stepVolume(item, -1)"
                                        >
                                            <Minus class="w-3.5 h-3.5" />
                                        </button>
                                        <input
                                            v-model.number="item.volume_liters"
                                            type="number"
                                            :step="isBbmProduct(item.product_name) ? 8000 : 1"
                                            :min="isBbmProduct(item.product_name) ? 8000 : 1"
                                            required
                                            class="w-full text-right num text-xs rounded-md border border-slate-300 px-2 py-1.5"
                                        >
                                        <button
                                            type="button"
                                            class="w-7 h-7 shrink-0 inline-flex items-center justify-center rounded-md border border-slate-300 bg-slate-50 hover:bg-slate-100 text-slate-700"
                                            aria-label="Tambah 8.000 liter"
                                            @click="stepVolume(item, 1)"
                                        >
                                            <Plus class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                                <td class="px-3 py-2 min-w-[115px]">
                                    <input v-model.number="item.price_per_liter" type="number" step="1" min="0" required placeholder="Isi harga/L" class="w-full text-right num text-xs rounded-md border border-slate-300 px-2 py-1.5">
                                </td>
                                <td class="px-3 py-2 text-right num whitespace-nowrap">
                                    <div class="font-medium text-amber-800">{{ formatRupiah(calcTaxAmount(item.volume_liters, item.tax_per_8kl)) }}</div>
                                    <div class="text-[10px] text-slate-400">0,25% × HD {{ formatNumber(item.tax_per_8kl) }}</div>
                                </td>
                                <td class="px-3 py-2 text-right num font-semibold text-teal-800 whitespace-nowrap">
                                    {{ formatRupiah(calcRowTotal(item.volume_liters, item.price_per_liter, item.tax_per_8kl)) }}
                                </td>
                                <td class="px-2 py-2 text-right">
                                    <button
                                        type="button"
                                        :disabled="batchForm.items.length === 1"
                                        class="p-1.5 rounded-md text-slate-400 hover:text-red-600 hover:bg-red-50 disabled:opacity-30"
                                        aria-label="Hapus baris produk"
                                        @click="removeBatchItem(idx)"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Live Totals & Submit -->
                <div class="flex flex-wrap items-center justify-between gap-4 bg-slate-50 border border-slate-200 rounded-lg px-4 py-3">
                    <div class="flex flex-wrap items-center gap-x-6 gap-y-1 text-xs">
                        <div>
                            <span class="text-slate-500">Total Volume: </span>
                            <span class="font-semibold text-slate-900 num">{{ formatNumber(batchSummary.liters) }} L</span>
                        </div>
                        <div>
                            <span class="text-slate-500">Total Pajak: </span>
                            <span class="font-semibold text-amber-800 num">{{ formatRupiah(batchSummary.tax) }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500">Total Tebus: </span>
                            <span class="font-bold text-teal-800 num text-sm">{{ formatRupiah(batchSummary.total) }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" class="px-3.5 py-2 text-xs font-medium text-slate-600 hover:text-slate-900" @click="showBatchForm = false">Batal</button>
                        <button type="submit" :disabled="batchForm.processing" class="px-4 py-2 rounded-lg bg-teal-700 hover:bg-teal-800 text-white text-xs font-semibold">
                            {{ batchForm.processing ? 'Menyimpan...' : `Simpan ${batchForm.items.length} Produk Tebus` }}
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Single-Row Edit Form -->
        <div v-if="editingId" class="bg-white border border-teal-200 rounded-xl p-5">
            <h2 class="text-sm font-semibold text-slate-900 mb-4">Edit Transaksi Tebus / DO Pertamina</h2>
            <form class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4" @submit.prevent="submitEdit">
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Tanggal</label>
                    <input v-model="editForm.transaction_date" type="date" required class="w-full text-sm rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div v-if="user?.role === 'owner'">
                    <label class="block text-xs font-medium text-slate-700 mb-1">Cabang</label>
                    <select v-model="editForm.branch_id" required class="w-full text-sm rounded-lg border border-slate-300 px-3 py-2 bg-white">
                        <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Nomor DO (Opsional)</label>
                    <input v-model="editForm.do_number" type="text" class="w-full text-sm rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Jenis Produk</label>
                    <select
                        v-model="editForm.product_name"
                        class="w-full text-sm rounded-lg border border-slate-300 px-3 py-2 bg-white"
                        @change="editForm.tax_per_8kl = defaultBasePrices[editForm.product_name] ?? 0"
                    >
                        <option v-for="p in products" :key="p" :value="p">{{ p }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Volume (Kelipatan 8.000 L)</label>
                    <div class="flex items-center gap-1.5">
                        <button
                            type="button"
                            class="w-9 h-9 shrink-0 inline-flex items-center justify-center rounded-lg border border-slate-300 bg-slate-50 hover:bg-slate-100 text-slate-700"
                            aria-label="Kurangi 8.000 liter"
                            @click="stepVolume(editForm, -1)"
                        >
                            <Minus class="w-4 h-4" />
                        </button>
                        <input
                            v-model.number="editForm.volume_liters"
                            type="number"
                            :step="isBbmProduct(editForm.product_name) ? 8000 : 1"
                            :min="isBbmProduct(editForm.product_name) ? 8000 : 1"
                            required
                            class="w-full text-right num text-sm rounded-lg border border-slate-300 px-3 py-2"
                        >
                        <button
                            type="button"
                            class="w-9 h-9 shrink-0 inline-flex items-center justify-center rounded-lg border border-slate-300 bg-slate-50 hover:bg-slate-100 text-slate-700"
                            aria-label="Tambah 8.000 liter"
                            @click="stepVolume(editForm, 1)"
                        >
                            <Plus class="w-4 h-4" />
                        </button>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Harga / Liter (Rp)</label>
                    <input v-model.number="editForm.price_per_liter" type="number" step="1" min="0" required class="w-full num text-sm rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Status Pembayaran</label>
                    <select v-model="editForm.payment_status" class="w-full text-sm rounded-lg border border-slate-300 px-3 py-2 bg-white">
                        <option value="Lunas (Tunai)">Lunas (Tunai)</option>
                        <option value="Transfer Bank">Transfer Bank</option>
                    </select>
                </div>
                <div class="sm:col-span-2 lg:col-span-4 flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-slate-100">
                    <div class="flex flex-wrap items-center gap-6 text-xs">
                        <div>
                            <span class="text-slate-500">PPh 22 (0,25%): </span>
                            <span class="font-semibold text-amber-800 num">{{ formatRupiah(editPreviewTax) }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500">Total Tebus: </span>
                            <span class="font-bold text-teal-800 num text-sm">{{ formatRupiah(editPreviewTotal) }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" class="px-3.5 py-2 text-xs font-medium text-slate-600 hover:text-slate-900" @click="editingId = null">Batal</button>
                        <button type="submit" :disabled="editForm.processing" class="px-4 py-2 rounded-lg bg-teal-700 hover:bg-teal-800 text-white text-xs font-semibold">
                            {{ editForm.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Branch Summary Strip -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div v-for="bt in branchTotals" :key="bt.id" class="bg-white border border-slate-200 rounded-xl p-4">
                <div class="text-xs font-medium text-slate-500">Tebus Cabang {{ bt.name }}</div>
                <div class="mt-1 text-lg font-bold text-slate-900 num">{{ formatRupiah(bt.total_amount) }}</div>
                <div class="mt-0.5 text-xs text-slate-500 num">{{ formatNumber(bt.total_liters) }} Liter</div>
            </div>
            <div class="bg-teal-50/70 border border-teal-200 rounded-xl p-4">
                <div class="text-xs font-medium text-teal-800">Total Ditampilkan</div>
                <div class="mt-1 text-lg font-bold text-teal-900 num">{{ formatRupiah(totals.amount) }}</div>
                <div class="mt-0.5 text-xs text-teal-700 num">
                    {{ formatNumber(totals.liters) }} L
                    <span v-if="totals.tax > 0"> • Pajak: {{ formatRupiah(totals.tax) }}</span>
                </div>
            </div>
        </div>

        <!-- Filter & Table -->
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="px-5 py-3.5 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-sm font-semibold text-slate-900">Daftar Transaksi Tebus ({{ redemptions.length }})</h2>
                <div class="flex flex-wrap items-center gap-2">
                    <select
                        v-if="user?.role === 'owner'"
                        :value="filters.branch_id ?? ''"
                        class="text-xs rounded-lg border border-slate-300 bg-white px-3 py-1.5"
                        @change="applyFilter('branch_id', $event.target.value)"
                    >
                        <option value="">Semua Cabang</option>
                        <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                    <select
                        :value="filters.product ?? ''"
                        class="text-xs rounded-lg border border-slate-300 bg-white px-3 py-1.5"
                        @change="applyFilter('product', $event.target.value)"
                    >
                        <option value="">Semua Produk</option>
                        <option v-for="p in products" :key="p" :value="p">{{ p }}</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold text-slate-600 border-b border-slate-200">
                        <tr>
                            <th class="text-left px-5 py-3">Tanggal</th>
                            <th class="text-left px-4 py-3">Nomor DO</th>
                            <th class="text-left px-4 py-3">Cabang</th>
                            <th class="text-left px-4 py-3">Produk</th>
                            <th class="text-right px-4 py-3">Volume (L)</th>
                            <th class="text-right px-4 py-3">Harga / L</th>
                            <th class="text-right px-4 py-3">PPh 22 (0,25%)</th>
                            <th class="text-right px-4 py-3">Total Tebus</th>
                            <th class="text-left px-4 py-3">Status</th>
                            <th class="text-right px-5 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="tebusPagination.paginatedItems.value.length === 0">
                            <td colspan="10" class="px-5 py-8 text-center text-sm text-slate-500">Belum ada transaksi tebus Pertamina. Klik "Catat Tebus BBM" untuk menambahkan.</td>
                        </tr>
                        <tr v-for="row in tebusPagination.paginatedItems.value" :key="row.id" class="hover:bg-slate-50/70">
                            <td class="px-5 py-3 whitespace-nowrap num text-xs text-slate-700">{{ formatDate(row.transaction_date) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap num text-xs text-slate-600">{{ row.do_number || '-' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs font-medium text-slate-800">{{ row.branch?.name }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-md text-xs font-medium bg-slate-100 text-slate-800">{{ row.product_name }}</span>
                            </td>
                            <td class="px-4 py-3 text-right num text-slate-800">{{ formatNumber(row.volume_liters) }}</td>
                            <td class="px-4 py-3 text-right num text-slate-600">{{ formatRupiah(row.price_per_liter) }}</td>
                            <td class="px-4 py-3 text-right num text-xs" :class="row.tax_amount > 0 ? 'text-amber-800 font-medium' : 'text-slate-400'">
                                <template v-if="row.tax_amount > 0">
                                    <div>{{ formatRupiah(row.tax_amount) }}</div>
                                    <div class="text-[10px] text-slate-400">HD {{ formatNumber(row.tax_per_8kl) }}</div>
                                </template>
                                <span v-else>-</span>
                            </td>
                            <td class="px-4 py-3 text-right num font-semibold text-slate-900">{{ formatRupiah(row.total_amount) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">{{ row.payment_status }}</span>
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1">
                                    <button
                                        type="button"
                                        class="p-1.5 rounded-md text-slate-400 hover:text-teal-700 hover:bg-teal-50 transition-colors"
                                        aria-label="Edit transaksi"
                                        @click="startEdit(row)"
                                    >
                                        <Pencil class="w-4 h-4" />
                                    </button>
                                    <button
                                        type="button"
                                        class="p-1.5 rounded-md text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                                        aria-label="Hapus transaksi"
                                        @click="removeRow(row)"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <DatePaginationBar
                :current-page="tebusPagination.currentPage.value"
                :total-pages="tebusPagination.totalPages.value"
                :total-dates="tebusPagination.totalDates.value"
                :page-date-count="tebusPagination.pageDates.value.length"
                :date-range-label="tebusPagination.dateRangeLabel.value"
                :dates-per-page="15"
                @change="tebusPagination.goToPage"
            />
        </div>
    </AppLayout>
</template>
