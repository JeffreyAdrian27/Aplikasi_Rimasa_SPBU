<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DatePaginationBar from '@/Components/DatePaginationBar.vue';
import { useDatePagination } from '@/composables/useDatePagination';
import { formatRupiah, formatNumber, formatDate } from '@/utils/format';
import { Plus, Pencil, CheckCircle2, RotateCcw, Trash2, X, Search } from 'lucide-vue-next';

const props = defineProps({
    sales: Array,
    companyRecap: Array,
    totals: Object,
    filters: Object,
    products: Array,
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const branches = computed(() => page.props.branches || []);

const activeTab = ref('tagihan'); // tagihan | rekap
const showForm = ref(false);
const editingId = ref(null);
const searchInput = ref(props.filters.search || '');

const defaultBranchId = computed(() =>
    user.value?.role === 'admin_cabang'
        ? user.value.branch_id
        : (props.filters.branch_id || branches.value[0]?.id || '')
);

const form = useForm({
    branch_id: defaultBranchId.value,
    sale_date: '2026-11-05',
    client_name: '',
    product_name: 'Solar',
    volume_liters: 4000,
    price_per_liter: 11200,
    credit_term_days: 7,
    paid_date: '',
    notes: '',
});

const previewTotal = computed(() => (Number(form.volume_liters) || 0) * (Number(form.price_per_liter) || 0));

const previewDueDate = computed(() => {
    if (!form.sale_date) return '-';
    const d = new Date(form.sale_date + 'T00:00:00');
    d.setDate(d.getDate() + (Number(form.credit_term_days) || 0));
    return formatDate(d.toISOString().slice(0, 10));
});

const openCreate = () => {
    if (showForm.value && !editingId.value) {
        showForm.value = false;
        return;
    }
    editingId.value = null;
    form.branch_id = defaultBranchId.value;
    form.sale_date = '2026-11-05';
    form.client_name = '';
    form.product_name = 'Solar';
    form.volume_liters = 4000;
    form.price_per_liter = 11200;
    form.credit_term_days = 7;
    form.paid_date = '';
    form.notes = '';
    showForm.value = true;
};

const startEdit = (row) => {
    editingId.value = row.id;
    form.branch_id = row.branch_id;
    form.sale_date = row.sale_date;
    form.client_name = row.client_name;
    form.product_name = row.product_name;
    form.volume_liters = Number(row.volume_liters);
    form.price_per_liter = Number(row.price_per_liter);
    form.credit_term_days = Number(row.credit_term_days);
    form.paid_date = row.paid_date || '';
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
        form.put(route('penjualan.update', editingId.value), {
            preserveScroll: true,
            onSuccess: () => closeForm(),
        });
        return;
    }
    form.post(route('penjualan.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeForm();
            form.client_name = '';
            form.paid_date = '';
            form.notes = '';
        },
    });
};

const applyFilter = (key, value) => {
    router.get(route('penjualan.index'), {
        ...props.filters,
        [key]: value || undefined,
    }, { preserveState: true, replace: true });
};

const markPaid = (row) => {
    const defaultDate = new Date().toISOString().slice(0, 10);
    const input = prompt(`Tanggal pelunasan untuk ${row.client_name} (YYYY-MM-DD):`, defaultDate);
    if (!input) return;
    router.patch(route('penjualan.mark-paid', row.id), { paid_date: input }, { preserveScroll: true });
};

const markUnpaid = (row) => {
    if (!confirm(`Kembalikan tagihan ${row.client_name} ke status Belum Lunas?`)) return;
    router.patch(route('penjualan.mark-unpaid', row.id), {}, { preserveScroll: true });
};

const removeRow = (row) => {
    if (!confirm(`Hapus tagihan ${row.client_name} senilai ${formatRupiah(row.total_amount)}?`)) return;
    router.delete(route('penjualan.destroy', row.id), { preserveScroll: true });
};

const salesPagination = useDatePagination(() => props.sales, 'sale_date', 15);
</script>

<template>
    <Head title="Penjualan & Piutang" />
    <AppLayout title="Penjualan & Piutang Klien">
        <template #header-actions>
            <button
                type="button"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-teal-700 hover:bg-teal-800 text-white text-xs font-semibold transition-colors"
                @click="openCreate"
            >
                <X v-if="showForm && !editingId" class="w-4 h-4" />
                <Plus v-else class="w-4 h-4" />
                <span>{{ showForm && !editingId ? 'Tutup Form' : 'Tambah Tagihan Klien' }}</span>
            </button>
        </template>

        <!-- Inline Add / Edit Form -->
        <div v-if="showForm" class="bg-white border border-teal-200 rounded-xl p-5">
            <h2 class="text-sm font-semibold text-slate-900 mb-4">
                {{ editingId ? 'Edit Tagihan Penjualan Klien' : 'Catat Penjualan Tempo / Tunai Klien' }}
            </h2>
            <form class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4" @submit.prevent="submit">
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Tanggal Jual</label>
                    <input v-model="form.sale_date" type="date" required class="w-full text-sm rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div v-if="user?.role === 'owner'">
                    <label class="block text-xs font-medium text-slate-700 mb-1">Cabang</label>
                    <select v-model="form.branch_id" required class="w-full text-sm rounded-lg border border-slate-300 px-3 py-2 bg-white">
                        <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Nama Klien / Perusahaan</label>
                    <input v-model="form.client_name" type="text" required placeholder="Contoh: Zhenglu, Rajawali, RPT" class="w-full text-sm rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Jenis Produk</label>
                    <select v-model="form.product_name" class="w-full text-sm rounded-lg border border-slate-300 px-3 py-2 bg-white">
                        <option v-for="p in products" :key="p" :value="p">{{ p }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Volume (Liter)</label>
                    <input v-model.number="form.volume_liters" type="number" step="1" min="1" required class="w-full num text-sm rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Harga Jual / L (Rp)</label>
                    <input v-model.number="form.price_per_liter" type="number" step="1" min="0" required class="w-full num text-sm rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Tempo (Hari)</label>
                    <input v-model.number="form.credit_term_days" type="number" min="0" max="365" required class="w-full num text-sm rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Tanggal Lunas (Kosongkan jika tempo)</label>
                    <input v-model="form.paid_date" type="date" class="w-full text-sm rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div class="sm:col-span-2 lg:col-span-4 flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-slate-100">
                    <div class="flex flex-wrap items-center gap-6 text-xs">
                        <div>
                            <span class="text-slate-500">Total Tagihan: </span>
                            <span class="font-bold text-teal-800 num text-sm">{{ formatRupiah(previewTotal) }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500">Jatuh Tempo: </span>
                            <span class="font-semibold text-slate-800">{{ previewDueDate }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" class="px-3.5 py-2 text-xs font-medium text-slate-600 hover:text-slate-900" @click="closeForm">Batal</button>
                        <button type="submit" :disabled="form.processing" class="px-4 py-2 rounded-lg bg-teal-700 hover:bg-teal-800 text-white text-xs font-semibold">
                            {{ form.processing ? 'Menyimpan...' : (editingId ? 'Simpan Perubahan' : 'Simpan Tagihan') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Summary Strip -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white border border-slate-200 rounded-xl p-4">
                <div class="text-xs font-medium text-slate-500">Total Penjualan</div>
                <div class="mt-1 text-lg font-bold text-slate-900 num">{{ formatRupiah(totals.total_amount) }}</div>
                <div class="mt-0.5 text-xs text-slate-500 num">{{ formatNumber(totals.liters) }} Liter</div>
            </div>
            <div class="bg-white border border-slate-200 rounded-xl p-4">
                <div class="text-xs font-medium text-slate-500">Sudah Lunas</div>
                <div class="mt-1 text-lg font-bold text-emerald-700 num">{{ formatRupiah(totals.paid_amount) }}</div>
                <div class="mt-0.5 text-xs text-slate-500">Masuk ke arus kas Rek 1</div>
            </div>
            <div class="bg-amber-50/70 border border-amber-200 rounded-xl p-4">
                <div class="text-xs font-medium text-amber-800">Sisa Piutang Belum Lunas</div>
                <div class="mt-1 text-lg font-bold text-amber-800 num">{{ formatRupiah(totals.unpaid_amount) }}</div>
                <div class="mt-0.5 text-xs text-amber-700">Menunggu pelunasan klien</div>
            </div>
        </div>

        <!-- Tabs: Daftar Tagihan vs Rekap Perusahaan -->
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="px-5 py-3.5 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
                <div class="inline-flex rounded-lg bg-slate-100 p-1">
                    <button
                        type="button"
                        :class="[
                            'px-3 py-1.5 rounded-md text-xs font-semibold transition-colors',
                            activeTab === 'tagihan' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900',
                        ]"
                        @click="activeTab = 'tagihan'"
                    >
                        Daftar Tagihan ({{ sales.length }})
                    </button>
                    <button
                        type="button"
                        :class="[
                            'px-3 py-1.5 rounded-md text-xs font-semibold transition-colors',
                            activeTab === 'rekap' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900',
                        ]"
                        @click="activeTab = 'rekap'"
                    >
                        Rekap Perusahaan ({{ companyRecap.length }})
                    </button>
                </div>

                <div v-if="activeTab === 'tagihan'" class="flex flex-wrap items-center gap-2">
                    <div class="relative">
                        <Search class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" />
                        <input
                            v-model="searchInput"
                            type="text"
                            placeholder="Cari klien..."
                            class="pl-8 pr-3 py-1.5 text-xs rounded-lg border border-slate-300 w-40"
                            @keyup.enter="applyFilter('search', searchInput)"
                            @blur="applyFilter('search', searchInput)"
                        >
                    </div>
                    <select
                        v-if="user?.role === 'owner'"
                        :value="filters.branch_id ?? ''"
                        class="text-xs rounded-lg border border-slate-300 bg-white px-2.5 py-1.5"
                        @change="applyFilter('branch_id', $event.target.value)"
                    >
                        <option value="">Semua Cabang</option>
                        <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                    <select
                        :value="filters.status ?? ''"
                        class="text-xs rounded-lg border border-slate-300 bg-white px-2.5 py-1.5"
                        @change="applyFilter('status', $event.target.value)"
                    >
                        <option value="">Semua Status</option>
                        <option value="Belum Lunas">Belum Lunas</option>
                        <option value="Lunas">Lunas</option>
                    </select>
                </div>
            </div>

            <!-- Tab 1: Daftar Tagihan -->
            <div v-if="activeTab === 'tagihan'" class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold text-slate-600 border-b border-slate-200">
                        <tr>
                            <th class="text-left px-5 py-3">Tgl Jual</th>
                            <th class="text-left px-4 py-3 min-w-[160px]">Klien / Perusahaan</th>
                            <th class="text-left px-4 py-3">Cabang</th>
                            <th class="text-left px-4 py-3">Produk</th>
                            <th class="text-right px-4 py-3">Volume</th>
                            <th class="text-right px-4 py-3">Total Tagihan</th>
                            <th class="text-left px-4 py-3">Jatuh Tempo</th>
                            <th class="text-left px-4 py-3">Status</th>
                            <th class="text-right px-5 py-3">Aksi Cepat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="salesPagination.paginatedItems.value.length === 0">
                            <td colspan="9" class="px-5 py-8 text-center text-sm text-slate-500">Tidak ada data penjualan pada filter ini.</td>
                        </tr>
                        <tr v-for="row in salesPagination.paginatedItems.value" :key="row.id" class="hover:bg-slate-50/70">
                            <td class="px-5 py-3 whitespace-nowrap num text-xs text-slate-700">{{ formatDate(row.sale_date) }}</td>
                            <td class="px-4 py-3 font-semibold text-slate-900">{{ row.client_name }}</td>
                            <td class="px-4 py-3 text-xs text-slate-700">{{ row.branch?.name }}</td>
                            <td class="px-4 py-3 text-xs text-slate-700">{{ row.product_name }}</td>
                            <td class="px-4 py-3 text-right num text-xs text-slate-700">{{ formatNumber(row.volume_liters) }} L</td>
                            <td class="px-4 py-3 text-right num font-semibold text-slate-900">{{ formatRupiah(row.total_amount) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-600">
                                <div>{{ formatDate(row.due_date) }}</div>
                                <div class="text-[11px] text-slate-400">Tempo {{ row.credit_term_days }} hr</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span
                                    v-if="row.payment_status === 'Lunas'"
                                    class="inline-flex flex-col"
                                >
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 w-fit">Lunas</span>
                                    <span class="text-[11px] text-slate-400 mt-0.5">{{ formatDate(row.paid_date) }}</span>
                                </span>
                                <span
                                    v-else
                                    class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800"
                                >
                                    Belum Lunas
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <button
                                        v-if="row.payment_status !== 'Lunas'"
                                        type="button"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium transition-colors"
                                        @click="markPaid(row)"
                                    >
                                        <CheckCircle2 class="w-3.5 h-3.5" />
                                        <span>Tandai Lunas</span>
                                    </button>
                                    <button
                                        v-else
                                        type="button"
                                        class="inline-flex items-center gap-1 px-2 py-1 rounded-md border border-slate-200 text-slate-600 hover:bg-slate-100 text-xs font-medium"
                                        title="Kembalikan ke Belum Lunas"
                                        @click="markUnpaid(row)"
                                    >
                                        <RotateCcw class="w-3.5 h-3.5" />
                                    </button>
                                    <button
                                        type="button"
                                        class="p-1.5 rounded-md text-slate-400 hover:text-teal-700 hover:bg-teal-50"
                                        aria-label="Edit tagihan"
                                        @click="startEdit(row)"
                                    >
                                        <Pencil class="w-4 h-4" />
                                    </button>
                                    <button
                                        type="button"
                                        class="p-1.5 rounded-md text-slate-400 hover:text-red-600 hover:bg-red-50"
                                        aria-label="Hapus tagihan"
                                        @click="removeRow(row)"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <DatePaginationBar
                    :current-page="salesPagination.currentPage.value"
                    :total-pages="salesPagination.totalPages.value"
                    :total-dates="salesPagination.totalDates.value"
                    :page-date-count="salesPagination.pageDates.value.length"
                    :date-range-label="salesPagination.dateRangeLabel.value"
                    :dates-per-page="15"
                    @change="salesPagination.goToPage"
                />
            </div>

            <!-- Tab 2: Rekap Perusahaan -->
            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold text-slate-600 border-b border-slate-200">
                        <tr>
                            <th class="text-left px-5 py-3">Perusahaan / Klien</th>
                            <th class="text-right px-4 py-3">Jumlah Tagihan</th>
                            <th class="text-right px-4 py-3">Total Penjualan</th>
                            <th class="text-right px-4 py-3">Sudah Lunas</th>
                            <th class="text-right px-5 py-3">Sisa Piutang</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="c in companyRecap" :key="c.client_name" class="hover:bg-slate-50/70">
                            <td class="px-5 py-3 font-semibold text-slate-900">{{ c.client_name }}</td>
                            <td class="px-4 py-3 text-right num text-xs text-slate-600">
                                {{ c.invoice_count }} transaksi
                                <span v-if="c.unpaid_count > 0" class="text-amber-700">({{ c.unpaid_count }} belum lunas)</span>
                            </td>
                            <td class="px-4 py-3 text-right num text-slate-900">{{ formatRupiah(c.total_sales) }}</td>
                            <td class="px-4 py-3 text-right num text-emerald-700">{{ formatRupiah(c.total_paid) }}</td>
                            <td class="px-5 py-3 text-right num font-bold" :class="c.remaining_receivable > 0 ? 'text-amber-700' : 'text-slate-400'">
                                {{ formatRupiah(c.remaining_receivable) }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-slate-50 border-t border-slate-200 font-semibold">
                        <tr>
                            <td class="px-5 py-3 text-slate-900">TOTAL SEMUA PERUSAHAAN</td>
                            <td class="px-4 py-3" />
                            <td class="px-4 py-3 text-right num text-slate-900">{{ formatRupiah(totals.total_amount) }}</td>
                            <td class="px-4 py-3 text-right num text-emerald-700">{{ formatRupiah(totals.paid_amount) }}</td>
                            <td class="px-5 py-3 text-right num text-amber-800">{{ formatRupiah(totals.unpaid_amount) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
