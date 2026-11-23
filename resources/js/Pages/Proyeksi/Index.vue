<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatRupiah, formatDate, MONTHS } from '@/utils/format';
import { Save } from 'lucide-vue-next';

const props = defineProps({
    projection: Object,
    filters: Object,
});

const page = usePage();
const user = computed(() => page.props.auth?.user);

const activeAccount = ref('rek1'); // rek1 | rek2
const onlyActiveDays = ref(false);

const settingsForm = useForm({
    year: props.filters.year,
    month: props.filters.month,
    opening_balance_rek1: props.projection.setting.opening_balance_rek1,
    opening_balance_rek2: props.projection.setting.opening_balance_rek2,
});

const changePeriod = (overrides) => {
    router.get(route('proyeksi.index'), {
        year: props.filters.year,
        month: props.filters.month,
        ...overrides,
    }, { preserveState: false });
};

const saveSettings = () => {
    settingsForm.post(route('proyeksi.settings'), { preserveScroll: true });
};

const visibleRek1Rows = computed(() => {
    if (!onlyActiveDays.value) return props.projection.rek1.rows;
    return props.projection.rek1.rows.filter(
        (r) => r.tebus_bbm !== 0 || r.pembayaran_masuk !== 0 || r.total_stor_bca !== 0
    );
});

const visibleRek2Rows = computed(() => {
    if (!onlyActiveDays.value) return props.projection.rek2.rows;
    return props.projection.rek2.rows.filter((r) => r.total_stor_bca !== 0);
});
</script>

<template>
    <Head title="Proyeksi Kas Harian" />
    <AppLayout title="Proyeksi Kas Harian (Rek 1 & Rek 2)">
        <!-- Period & Opening Balance Control Bar -->
        <div class="bg-white border border-slate-200 rounded-xl p-5 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Periode {{ projection.period.label }} ({{ projection.period.days_in_month }} Hari)</h2>
                    <p class="text-xs text-slate-500">Simulasi arus kas harian otomatis dari Tebus Pertamina, Pelunasan Piutang, dan Kas Cabang.</p>
                </div>
                <div class="flex items-center gap-2">
                    <select
                        :value="filters.month"
                        class="text-xs rounded-lg border border-slate-300 bg-white px-3 py-1.5"
                        @change="changePeriod({ month: Number($event.target.value) })"
                    >
                        <option v-for="m in MONTHS" :key="m.value" :value="m.value">{{ m.label }}</option>
                    </select>
                    <input
                        :value="filters.year"
                        type="number"
                        class="w-24 num text-xs rounded-lg border border-slate-300 px-3 py-1.5"
                        @change="changePeriod({ year: Number($event.target.value) })"
                    >
                </div>
            </div>

            <!-- Owner Opening Balance Editor -->
            <form
                v-if="user?.role === 'owner'"
                class="pt-4 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-3 gap-3 items-end"
                @submit.prevent="saveSettings"
            >
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Saldo Awal Rekening 1 (Rp)</label>
                    <input v-model.number="settingsForm.opening_balance_rek1" type="number" step="1000" class="w-full num text-sm rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Saldo Awal Rekening 2 (Rp)</label>
                    <input v-model.number="settingsForm.opening_balance_rek2" type="number" step="1000" class="w-full num text-sm rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <button
                        type="submit"
                        :disabled="settingsForm.processing"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg bg-teal-700 hover:bg-teal-800 text-white text-xs font-semibold"
                    >
                        <Save class="w-3.5 h-3.5" />
                        <span>{{ settingsForm.processing ? 'Menyimpan...' : 'Simpan Saldo Awal' }}</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Summary Strip -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white border border-slate-200 rounded-xl p-4">
                <div class="text-xs font-medium text-slate-500">Saldo Akhir Rek 1</div>
                <div class="mt-1 text-lg font-bold text-slate-900 num">{{ formatRupiah(projection.rek1.summary.closing_balance) }}</div>
                <div class="mt-0.5 text-xs text-slate-500 num">Dana Neto Puncak: {{ formatRupiah(projection.rek1.summary.peak_net_used) }}</div>
            </div>
            <div class="bg-white border border-slate-200 rounded-xl p-4">
                <div class="text-xs font-medium text-slate-500">Saldo Akhir Rek 2</div>
                <div class="mt-1 text-lg font-bold text-slate-900 num">{{ formatRupiah(projection.rek2.summary.closing_balance) }}</div>
                <div class="mt-0.5 text-xs text-slate-500 num">Stor BCA Rek 2: {{ formatRupiah(projection.rek2.summary.total_stor_bca) }}</div>
            </div>
            <div class="bg-teal-50/70 border border-teal-200 rounded-xl p-4">
                <div class="text-xs font-medium text-teal-800">Saldo Akhir Gabungan</div>
                <div class="mt-1 text-lg font-bold text-teal-900 num">{{ formatRupiah(projection.combined.closing_balance) }}</div>
                <div class="mt-0.5 text-xs num" :class="projection.combined.peak_additional_cash > 0 ? 'text-red-700 font-semibold' : 'text-teal-700'">
                    Tambahan Kas Puncak: {{ formatRupiah(projection.combined.peak_additional_cash) }}
                </div>
            </div>
        </div>

        <!-- Daily Cashflow Table -->
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="px-5 py-3.5 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
                <div class="inline-flex rounded-lg bg-slate-100 p-1">
                    <button
                        type="button"
                        :class="[
                            'px-3 py-1.5 rounded-md text-xs font-semibold transition-colors',
                            activeAccount === 'rek1' ? 'bg-blue-700 text-white' : 'text-slate-600 hover:text-slate-900',
                        ]"
                        @click="activeAccount = 'rek1'"
                    >
                        Rekening 1 (BBM)
                    </button>
                    <button
                        type="button"
                        :class="[
                            'px-3 py-1.5 rounded-md text-xs font-semibold transition-colors',
                            activeAccount === 'rek2' ? 'bg-violet-700 text-white' : 'text-slate-600 hover:text-slate-900',
                        ]"
                        @click="activeAccount = 'rek2'"
                    >
                        Rekening 2 (Margin & Ops)
                    </button>
                </div>

                <label class="inline-flex items-center gap-2 text-xs text-slate-600 select-none">
                    <input v-model="onlyActiveDays" type="checkbox" class="rounded border-slate-300 text-teal-700 focus:ring-teal-700">
                    <span>Hanya tampilkan hari dengan transaksi</span>
                </label>
            </div>

            <div class="overflow-x-auto">
                <!-- Rekening 1 Daily Table -->
                <table v-if="activeAccount === 'rek1'" class="w-full text-xs">
                    <thead class="bg-slate-50 font-semibold text-slate-600 border-b border-slate-200">
                        <tr>
                            <th class="text-left px-4 py-3">Tanggal</th>
                            <th class="text-right px-3 py-3">Tebus BBM</th>
                            <th class="text-right px-3 py-3">Pembayaran Klien</th>
                            <th v-for="b in projection.branches" :key="b.id" class="text-right px-3 py-3">Stor BCA {{ b.code }}</th>
                            <th class="text-right px-3 py-3">Arus Kas Bersih</th>
                            <th class="text-right px-3 py-3">Saldo Awal</th>
                            <th class="text-right px-3 py-3">Saldo Akhir</th>
                            <th class="text-right px-3 py-3">Dana Neto</th>
                            <th class="text-right px-4 py-3">Tambahan Kas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr
                            v-for="row in visibleRek1Rows"
                            :key="row.date"
                            :class="[
                                'hover:bg-slate-50/80',
                                row.net_flow !== 0 ? 'bg-white' : 'bg-slate-50/30 text-slate-400',
                            ]"
                        >
                            <td class="px-4 py-2.5 num whitespace-nowrap text-slate-700">{{ formatDate(row.date) }}</td>
                            <td class="px-3 py-2.5 text-right num" :class="row.tebus_bbm > 0 ? 'text-red-700' : ''">{{ row.tebus_bbm ? formatRupiah(row.tebus_bbm) : '-' }}</td>
                            <td class="px-3 py-2.5 text-right num" :class="row.pembayaran_masuk > 0 ? 'text-emerald-700' : ''">{{ row.pembayaran_masuk ? formatRupiah(row.pembayaran_masuk) : '-' }}</td>
                            <td v-for="b in projection.branches" :key="b.id" class="px-3 py-2.5 text-right num" :class="row.stor_by_branch[b.code] > 0 ? 'text-emerald-700' : ''">
                                {{ row.stor_by_branch[b.code] ? formatRupiah(row.stor_by_branch[b.code]) : '-' }}
                            </td>
                            <td class="px-3 py-2.5 text-right num font-medium" :class="row.net_flow < 0 ? 'text-red-700' : (row.net_flow > 0 ? 'text-emerald-700' : '')">
                                {{ row.net_flow !== 0 ? formatRupiah(row.net_flow, true) : '-' }}
                            </td>
                            <td class="px-3 py-2.5 text-right num text-slate-600">{{ formatRupiah(row.opening_balance) }}</td>
                            <td class="px-3 py-2.5 text-right num font-semibold" :class="row.closing_balance < 0 ? 'text-red-700' : 'text-slate-900'">
                                {{ formatRupiah(row.closing_balance) }}
                            </td>
                            <td class="px-3 py-2.5 text-right num text-amber-700">{{ row.net_used > 0 ? formatRupiah(row.net_used) : '-' }}</td>
                            <td class="px-4 py-2.5 text-right num font-semibold" :class="row.additional_cash > 0 ? 'text-red-700 bg-red-50' : ''">
                                {{ row.additional_cash > 0 ? formatRupiah(row.additional_cash) : '-' }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Rekening 2 Daily Table -->
                <table v-else class="w-full text-xs">
                    <thead class="bg-slate-50 font-semibold text-slate-600 border-b border-slate-200">
                        <tr>
                            <th class="text-left px-4 py-3">Tanggal</th>
                            <th v-for="b in projection.branches" :key="b.id" class="text-right px-3 py-3">Stor BCA {{ b.code }} (Rek 2)</th>
                            <th class="text-right px-3 py-3">Arus Kas Bersih</th>
                            <th class="text-right px-3 py-3">Saldo Awal</th>
                            <th class="text-right px-3 py-3">Saldo Akhir</th>
                            <th class="text-right px-4 py-3">Tambahan Kas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr
                            v-for="row in visibleRek2Rows"
                            :key="row.date"
                            :class="[
                                'hover:bg-slate-50/80',
                                row.net_flow !== 0 ? 'bg-white' : 'bg-slate-50/30 text-slate-400',
                            ]"
                        >
                            <td class="px-4 py-2.5 num whitespace-nowrap text-slate-700">{{ formatDate(row.date) }}</td>
                            <td v-for="b in projection.branches" :key="b.id" class="px-3 py-2.5 text-right num" :class="row.stor_by_branch[b.code] < 0 ? 'text-red-700' : (row.stor_by_branch[b.code] > 0 ? 'text-emerald-700' : '')">
                                {{ row.stor_by_branch[b.code] ? formatRupiah(row.stor_by_branch[b.code]) : '-' }}
                            </td>
                            <td class="px-3 py-2.5 text-right num font-medium" :class="row.net_flow < 0 ? 'text-red-700' : (row.net_flow > 0 ? 'text-emerald-700' : '')">
                                {{ row.net_flow !== 0 ? formatRupiah(row.net_flow, true) : '-' }}
                            </td>
                            <td class="px-3 py-2.5 text-right num text-slate-600">{{ formatRupiah(row.opening_balance) }}</td>
                            <td class="px-3 py-2.5 text-right num font-semibold text-slate-900">{{ formatRupiah(row.closing_balance) }}</td>
                            <td class="px-4 py-2.5 text-right num font-semibold" :class="row.additional_cash > 0 ? 'text-red-700 bg-red-50' : ''">
                                {{ row.additional_cash > 0 ? formatRupiah(row.additional_cash) : '-' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
