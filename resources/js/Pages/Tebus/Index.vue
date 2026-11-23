<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DatePaginationBar from '@/Components/DatePaginationBar.vue';
import { useDatePagination } from '@/composables/useDatePagination';
import { formatRupiah, formatNumber, formatDate } from '@/utils/format';
import { Plus, Pencil, Trash2, X } from 'lucide-vue-next';

const props = defineProps({
    redemptions: Array,
    branchTotals: Array,
    totals: Object,
    filters: Object,
    products: Array,
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const branches = computed(() => page.props.branches || []);

const showForm = ref(false);
const editingId = ref(null);

const defaultBranchId = computed(() =>
    user.value?.role === 'admin_cabang'
        ? user.value.branch_id
        : (props.filters.branch_id || branches.value[0]?.id || '')
);

const form = useForm({
    branch_id: defaultBranchId.value,
    transaction_date: '2026-11-05',
    do_number: '',
    product_name: 'Solar',
    volume_liters: 8000,
    price_per_liter: 6800,
    payment_status: 'Lunas (Tunai)',
    notes: '',
});

const defaultPrices = {
    Solar: 6800,
    Pertalite: 10000,
    Pertamax: 12500,
    Dexlite: 13000,
};

const onProductChange = () => {
    if (!editingId.value && defaultPrices[form.product_name]) {
        form.price_per_liter = defaultPrices[form.product_name];
    }
};

const previewTotal = computed(() => (Number(form.volume_liters) || 0) * (Number(form.price_per_liter) || 0));

const openCreate = () => {
    if (showForm.value && !editingId.value) {
        showForm.value = false;
        return;
    }
    editingId.value = null;
    form.branch_id = defaultBranchId.value;
    form.transaction_date = '2026-11-05';
    form.do_number = '';
    form.product_name = 'Solar';
    form.volume_liters = 8000;
    form.price_per_liter = 6800;
    form.payment_status = 'Lunas (Tunai)';
    form.notes = '';
    showForm.value = true;
};

const startEdit = (row) => {
    editingId.value = row.id;
    form.branch_id = row.branch_id;
    form.transaction_date = row.transaction_date;
    form.do_number = row.do_number || '';
    form.product_name = row.product_name;
    form.volume_liters = Number(row.volume_liters);
    form.price_per_liter = Number(row.price_per_liter);
    form.payment_status = row.payment_status;
    form.notes = row.notes || '';
    showForm.value = true;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const closeForm = () => {
    showForm.value = false;
    editingId.value = null;
};

const submit = () => {
    if (editingId.value) {
        form.put(route('tebus.update', editingId.value), {
            preserveScroll: true,
            onSuccess: () => closeForm(),
        });
        return;
    }
    form.post(route('tebus.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeForm();
            form.do_number = '';
            form.notes = '';
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
            <button
                type="button"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-teal-700 hover:bg-teal-800 text-white text-xs font-semibold transition-colors"
                @click="openCreate"
            >
                <X v-if="showForm && !editingId" class="w-4 h-4" />
                <Plus v-else class="w-4 h-4" />
                <span>{{ showForm && !editingId ? 'Tutup Form' : 'Catat Tebus BBM' }}</span>
            </button>
        </template>

        <!-- Inline Collapsible Add / Edit Form -->
        <div v-if="showForm" class="bg-white border border-teal-200 rounded-xl p-5">
            <h2 class="text-sm font-semibold text-slate-900 mb-4">
                {{ editingId ? 'Edit Transaksi Tebus / DO Pertamina' : 'Input Transaksi Tebus / DO Pertamina' }}
            </h2>
            <form class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4" @submit.prevent="submit">
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Tanggal</label>
                    <input v-model="form.transaction_date" type="date" required class="w-full text-sm rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div v-if="user?.role === 'owner'">
                    <label class="block text-xs font-medium text-slate-700 mb-1">Cabang</label>
                    <select v-model="form.branch_id" required class="w-full text-sm rounded-lg border border-slate-300 px-3 py-2 bg-white">
                        <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Nomor DO (Opsional)</label>
                    <input v-model="form.do_number" type="text" placeholder="Contoh: DO-261105-01" class="w-full text-sm rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Jenis Produk</label>
                    <select v-model="form.product_name" class="w-full text-sm rounded-lg border border-slate-300 px-3 py-2 bg-white" @change="onProductChange">
                        <option v-for="p in products" :key="p" :value="p">{{ p }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Volume (Liter)</label>
                    <input v-model.number="form.volume_liters" type="number" step="1" min="1" required class="w-full num text-sm rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Harga / Liter (Rp)</label>
                    <input v-model.number="form.price_per_liter" type="number" step="1" min="0" required class="w-full num text-sm rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Status Pembayaran</label>
                    <select v-model="form.payment_status" class="w-full text-sm rounded-lg border border-slate-300 px-3 py-2 bg-white">
                        <option value="Lunas (Tunai)">Lunas (Tunai)</option>
                        <option value="Transfer Bank">Transfer Bank</option>
                    </select>
                </div>
                <div class="bg-slate-50 border border-slate-200 rounded-lg px-3.5 py-2 flex flex-col justify-center">
                    <span class="text-[11px] font-medium text-slate-500">Total Tebus Otomatis</span>
                    <span class="text-base font-bold text-teal-800 num">{{ formatRupiah(previewTotal) }}</span>
                </div>
                <div class="sm:col-span-2 lg:col-span-4 flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" class="px-3.5 py-2 text-xs font-medium text-slate-600 hover:text-slate-900" @click="closeForm">Batal</button>
                    <button type="submit" :disabled="form.processing" class="px-4 py-2 rounded-lg bg-teal-700 hover:bg-teal-800 text-white text-xs font-semibold">
                        {{ form.processing ? 'Menyimpan...' : (editingId ? 'Simpan Perubahan' : 'Simpan Transaksi Tebus') }}
                    </button>
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
                <div class="mt-0.5 text-xs text-teal-700 num">{{ formatNumber(totals.liters) }} Liter</div>
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
                            <th class="text-right px-4 py-3">Total Tebus</th>
                            <th class="text-left px-4 py-3">Status</th>
                            <th class="text-right px-5 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="tebusPagination.paginatedItems.value.length === 0">
                            <td colspan="9" class="px-5 py-8 text-center text-sm text-slate-500">Belum ada transaksi tebus Pertamina. Klik "Catat Tebus BBM" untuk menambahkan.</td>
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
