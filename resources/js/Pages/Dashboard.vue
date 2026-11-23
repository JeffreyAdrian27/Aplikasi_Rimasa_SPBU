<script setup>
import { computed, reactive } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatRupiah, formatNumber, formatDate, MONTHS } from '@/utils/format';
import { ArrowUpRight, AlertTriangle, CheckCircle2, Clock } from 'lucide-vue-next';

const props = defineProps({
    summary: Object,
    filters: Object,
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const branches = computed(() => page.props.branches || []);

const filterForm = reactive({
    year: props.filters.year,
    month: props.filters.month,
    branch_id: props.filters.branch_id ?? '',
});

const applyFilter = () => {
    router.get(route('dashboard'), {
        year: filterForm.year,
        month: filterForm.month,
        branch_id: filterForm.branch_id || undefined,
    }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Dashboard Keuangan" />
    <AppLayout title="Dashboard Keuangan & Arus Kas">
        <!-- Period & Branch Filter Bar -->
        <div class="bg-white border border-slate-200 rounded-xl p-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Ringkasan Periode {{ summary.period.label }}</h2>
                <p class="text-xs text-slate-500">Monitoring modal tebus BBM, piutang tempo klien, dan proyeksi saldo Rek 1 & Rek 2.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <select
                    v-if="user?.role === 'owner'"
                    v-model="filterForm.branch_id"
                    class="text-sm rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-slate-800"
                    @change="applyFilter"
                >
                    <option value="">Semua Cabang</option>
                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
                <select
                    v-model="filterForm.month"
                    class="text-sm rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-slate-800"
                    @change="applyFilter"
                >
                    <option v-for="m in MONTHS" :key="m.value" :value="m.value">{{ m.label }}</option>
                </select>
                <input
                    v-model.number="filterForm.year"
                    type="number"
                    min="2020"
                    max="2099"
                    class="w-24 num text-sm rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-slate-800"
                    @change="applyFilter"
                >
            </div>
        </div>

        <!-- Key Financial Ledger Strip (4 columns, clean borders, no nested cards) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <div class="text-xs font-medium text-slate-500">Total Modal Tebus (BBM)</div>
                <div class="mt-2 text-xl font-bold text-slate-900 num">{{ formatRupiah(summary.kpis.total_modal_tebus) }}</div>
                <div class="mt-1 text-xs text-slate-500 num">{{ formatNumber(summary.kpis.total_volume_tebus) }} Liter ditebus</div>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <div class="text-xs font-medium text-slate-500">Penjualan Kotor (Klien)</div>
                <div class="mt-2 text-xl font-bold text-slate-900 num">{{ formatRupiah(summary.kpis.total_penjualan_kotor) }}</div>
                <div class="mt-1 text-xs text-emerald-700 num">Lunas: {{ formatRupiah(summary.kpis.total_piutang_sudah_lunas) }}</div>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <div class="text-xs font-medium text-slate-500">Piutang Belum Lunas (Tempo)</div>
                <div class="mt-2 text-xl font-bold text-amber-700 num">{{ formatRupiah(summary.kpis.total_piutang_belum_lunas) }}</div>
                <div class="mt-1 text-xs text-slate-500">Tagihan klien menunggu pembayaran</div>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <div class="text-xs font-medium text-slate-500">Saldo Akhir Gabungan (Rek 1 + 2)</div>
                <div class="mt-2 text-xl font-bold text-teal-800 num">{{ formatRupiah(summary.accounts.combined.closing_balance) }}</div>
                <div class="mt-1 text-xs flex items-center gap-1" :class="summary.accounts.combined.peak_additional_cash > 0 ? 'text-red-700 font-medium' : 'text-emerald-700'">
                    <AlertTriangle v-if="summary.accounts.combined.peak_additional_cash > 0" class="w-3.5 h-3.5" />
                    <CheckCircle2 v-else class="w-3.5 h-3.5" />
                    <span>
                        {{ summary.accounts.combined.peak_additional_cash > 0
                            ? `Tambahan kas puncak: ${formatRupiah(summary.accounts.combined.peak_additional_cash)}`
                            : 'Kas aman (tanpa tambahan kas)' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Proyeksi Rekening Table (Matches Excel "PROYEKSI REKENING" exactly) -->
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="text-base font-semibold text-slate-900">Proyeksi Rekening — {{ summary.period.label }}</h2>
                    <p class="text-xs text-slate-500">Rekap arus kas Rek 1 (BBM), Rek 2 (Margin, LPG/Oli & Biaya), dan Gabungan.</p>
                </div>
                <Link
                    :href="route('proyeksi.index', { year: filterForm.year, month: filterForm.month })"
                    class="inline-flex items-center gap-1 shrink-0 whitespace-nowrap px-2.5 py-1.5 rounded-lg bg-teal-50 hover:bg-teal-100 text-xs font-semibold text-teal-800 transition-colors"
                >
                    <span>Lihat Harian 1–{{ summary.period.days_in_month }}</span>
                    <ArrowUpRight class="w-3.5 h-3.5 shrink-0" />
                </Link>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold text-slate-600 border-b border-slate-200">
                        <tr>
                            <th class="text-left px-5 py-3">Keterangan</th>
                            <th class="text-right px-5 py-3">Rekening 1 (BBM)</th>
                            <th class="text-right px-5 py-3">Rekening 2 (Margin & Ops)</th>
                            <th class="text-right px-5 py-3">Gabungan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr>
                            <td class="px-5 py-3 font-medium text-slate-700">Saldo Awal</td>
                            <td class="px-5 py-3 text-right num">{{ formatRupiah(summary.accounts.rek1.opening_balance) }}</td>
                            <td class="px-5 py-3 text-right num">{{ formatRupiah(summary.accounts.rek2.opening_balance) }}</td>
                            <td class="px-5 py-3 text-right num font-semibold">{{ formatRupiah(summary.accounts.combined.opening_balance) }}</td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 font-medium text-slate-700">Total Pemasukan</td>
                            <td class="px-5 py-3 text-right num text-emerald-700">{{ formatRupiah(summary.accounts.rek1.total_pemasukan) }}</td>
                            <td class="px-5 py-3 text-right num text-emerald-700">{{ formatRupiah(summary.accounts.rek2.total_pemasukan) }}</td>
                            <td class="px-5 py-3 text-right num font-semibold text-emerald-800">{{ formatRupiah(summary.accounts.combined.total_pemasukan) }}</td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 font-medium text-slate-700">Total Pengeluaran</td>
                            <td class="px-5 py-3 text-right num text-red-700">{{ formatRupiah(summary.accounts.rek1.total_pengeluaran) }}</td>
                            <td class="px-5 py-3 text-right num text-red-700">{{ formatRupiah(summary.accounts.rek2.total_pengeluaran) }}</td>
                            <td class="px-5 py-3 text-right num font-semibold text-red-800">{{ formatRupiah(summary.accounts.combined.total_pengeluaran) }}</td>
                        </tr>
                        <tr class="bg-slate-50/70">
                            <td class="px-5 py-3 font-semibold text-slate-900">Saldo Akhir</td>
                            <td class="px-5 py-3 text-right num font-bold text-slate-900">{{ formatRupiah(summary.accounts.rek1.closing_balance) }}</td>
                            <td class="px-5 py-3 text-right num font-bold text-slate-900">{{ formatRupiah(summary.accounts.rek2.closing_balance) }}</td>
                            <td class="px-5 py-3 text-right num font-bold text-teal-800">{{ formatRupiah(summary.accounts.combined.closing_balance) }}</td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 text-slate-600 whitespace-nowrap">Dana Neto Terpakai (Puncak)</td>
                            <td class="px-5 py-3 text-right num text-slate-700">{{ formatRupiah(summary.accounts.rek1.peak_net_used) }}</td>
                            <td class="px-5 py-3 text-right num text-slate-700">{{ formatRupiah(summary.accounts.rek2.peak_net_used) }}</td>
                            <td class="px-5 py-3 text-right num text-slate-500">—</td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 text-slate-600 whitespace-nowrap">Tambahan Kas Puncak</td>
                            <td class="px-5 py-3 text-right num" :class="summary.accounts.rek1.peak_additional_cash > 0 ? 'text-red-700 font-semibold' : 'text-slate-700'">
                                {{ formatRupiah(summary.accounts.rek1.peak_additional_cash) }}
                            </td>
                            <td class="px-5 py-3 text-right num" :class="summary.accounts.rek2.peak_additional_cash > 0 ? 'text-red-700 font-semibold' : 'text-slate-700'">
                                {{ formatRupiah(summary.accounts.rek2.peak_additional_cash) }}
                            </td>
                            <td class="px-5 py-3 text-right num font-semibold" :class="summary.accounts.combined.peak_additional_cash > 0 ? 'text-red-700' : 'text-emerald-700'">
                                {{ formatRupiah(summary.accounts.combined.peak_additional_cash) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Ringkasan per Produk BBM -->
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="text-base font-semibold text-slate-900">Ringkasan per Produk BBM</h2>
                    <p class="text-xs text-slate-500">Perbandingan volume & nilai Tebus Pertamina terhadap Penjualan Klien untuk setiap jenis produk.</p>
                </div>
                <Link
                    :href="route('tebus.index')"
                    class="inline-flex items-center gap-1 shrink-0 whitespace-nowrap px-2.5 py-1.5 rounded-lg bg-teal-50 hover:bg-teal-100 text-xs font-semibold text-teal-800 transition-colors"
                >
                    <span>Kelola Tebus</span>
                    <ArrowUpRight class="w-3.5 h-3.5 shrink-0" />
                </Link>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold text-slate-600 border-b border-slate-200">
                        <tr>
                            <th class="text-left px-5 py-3">Produk</th>
                            <th class="text-right px-4 py-3">Tebus (Liter)</th>
                            <th class="text-right px-4 py-3">Modal Tebus</th>
                            <th class="text-right px-4 py-3">Jual Klien (Liter)</th>
                            <th class="text-right px-4 py-3">Tagihan Klien</th>
                            <th class="text-right px-5 py-3">Sisa Volume</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="p in summary.product_summary" :key="p.product_name" class="hover:bg-slate-50/70">
                            <td class="px-5 py-3 font-semibold text-slate-900 whitespace-nowrap">{{ p.product_name }}</td>
                            <td class="px-4 py-3 text-right num text-slate-800">{{ formatNumber(p.tebus_liters) }} L</td>
                            <td class="px-4 py-3 text-right num text-slate-900">{{ formatRupiah(p.tebus_amount) }}</td>
                            <td class="px-4 py-3 text-right num text-slate-800">{{ formatNumber(p.sales_liters) }} L</td>
                            <td class="px-4 py-3 text-right num text-emerald-700">{{ formatRupiah(p.sales_amount) }}</td>
                            <td
                                class="px-5 py-3 text-right num font-semibold"
                                :class="p.volume_diff < 0 ? 'text-red-700' : 'text-teal-800'"
                            >
                                {{ formatNumber(p.volume_diff) }} L
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Bottom 2-column operational view: Branch Breakdown + Unpaid Receivables -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Ringkasan per Cabang -->
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-slate-900">Ringkasan per Cabang</h2>
                    <Link :href="route('kas.index')" class="shrink-0 whitespace-nowrap text-xs font-semibold text-teal-700 hover:text-teal-900">Buka Kas Harian</Link>
                </div>
                <div class="divide-y divide-slate-100">
                    <div v-for="b in summary.branch_cards" :key="b.id" class="p-5 space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900">{{ b.name }}</h3>
                                <p class="text-xs text-slate-500">Margin BBM: {{ (b.margin_rate * 100).toFixed(1) }}%</p>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 num">{{ b.code }}</span>
                        </div>
                        <div class="grid grid-cols-2 gap-3 text-xs">
                            <div class="bg-slate-50 rounded-lg p-3">
                                <div class="text-slate-500">Omzet Tunai Rek 1</div>
                                <div class="mt-0.5 font-semibold text-slate-900 num">{{ formatRupiah(b.omzet_rek1) }}</div>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-3">
                                <div class="text-slate-500">Stor BCA Rek 1</div>
                                <div class="mt-0.5 font-semibold text-teal-800 num">{{ formatRupiah(b.stor_bca_rek1) }}</div>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-3">
                                <div class="text-slate-500">Total Tebus BBM</div>
                                <div class="mt-0.5 font-semibold text-slate-900 num">{{ formatRupiah(b.tebus_total) }}</div>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-3">
                                <div class="text-slate-500">Piutang Belum Lunas</div>
                                <div class="mt-0.5 font-semibold text-amber-700 num">{{ formatRupiah(b.piutang_belum_lunas) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Piutang Menunggu Pelunasan -->
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden flex flex-col">
                <div class="px-5 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-slate-900">Piutang Tempo Belum Lunas</h2>
                    <Link :href="route('penjualan.index', { status: 'Belum Lunas' })" class="shrink-0 whitespace-nowrap text-xs font-semibold text-teal-700 hover:text-teal-900">Kelola Semua</Link>
                </div>
                <div v-if="summary.unpaid_receivables.length === 0" class="p-8 text-center text-sm text-slate-500">
                    Semua tagihan klien sudah lunas.
                </div>
                <div v-else class="divide-y divide-slate-100">
                    <div
                        v-for="item in summary.unpaid_receivables"
                        :key="item.id"
                        class="px-5 py-3.5 flex items-center justify-between gap-4 hover:bg-slate-50/70 transition-colors"
                    >
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-slate-900 truncate">{{ item.client_name }}</div>
                            <div class="text-xs text-slate-500 flex items-center gap-2 mt-0.5">
                                <span>{{ item.product_name }} • {{ formatNumber(item.volume_liters) }} L</span>
                                <span>•</span>
                                <span>{{ item.branch?.name }}</span>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-sm font-semibold text-slate-900 num">{{ formatRupiah(item.total_amount) }}</div>
                            <div class="text-[11px] text-amber-700 inline-flex items-center gap-1 mt-0.5">
                                <Clock class="w-3 h-3" />
                                <span>Jatuh tempo {{ formatDate(item.due_date) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
