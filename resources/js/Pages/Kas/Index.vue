<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DatePaginationBar from '@/Components/DatePaginationBar.vue';
import { useDatePagination } from '@/composables/useDatePagination';
import { formatRupiah, formatDate, MONTHS } from '@/utils/format';
import { Plus, Pencil, Check, Trash2, X, Layers } from 'lucide-vue-next';

const props = defineProps({
    branch: Object,
    accountType: String,
    entries: Array,
    dailySummary: Array,
    filters: Object,
});

const page = usePage();
const branches = computed(() => page.props.branches || []);

const showForm = ref(false);

const formatShortDM = (dateStr, offsetDays = -1) => {
    if (!dateStr) return '';
    const d = new Date(dateStr + 'T00:00:00');
    if (Number.isNaN(d.getTime())) return '';
    d.setDate(d.getDate() + offsetDays);
    return `${d.getDate()}/${d.getMonth() + 1}`;
};

const defaultEntryDate = `${props.filters.year}-${String(props.filters.month).padStart(2, '0')}-04`;

const buildInitialRows = (account, entryDate) => {
    const exLabel = formatShortDM(entryDate, -1);
    if (account === 'rek1') {
        return [
            { description: `Tunai ex ${exLabel} Shift I`, category: 'shift_omzet', debit: 0, credit: 0, autoEx: 'rek1_shift1' },
            { description: 'Shift II', category: 'shift_omzet', debit: 0, credit: 0, autoEx: null },
            { description: 'Shift III', category: 'shift_omzet', debit: 0, credit: 0, autoEx: null },
        ];
    }
    return [
        { description: `Tunai ex O. LPG M ${exLabel}`, category: 'non_bbm_income', debit: 0, credit: 0, autoEx: 'rek2_lpg' },
        { description: 'U/ Kas SPBU', category: 'operational_expense', debit: 0, credit: 0, autoEx: null },
    ];
};

const form = useForm({
    branch_id: props.branch.id,
    account_type: props.accountType,
    entry_date: defaultEntryDate,
    items: buildInitialRows(props.accountType, defaultEntryDate),
});

watch(() => [props.branch.id, props.accountType], ([bid, acc]) => {
    form.branch_id = bid;
    form.account_type = acc;
    form.items = buildInitialRows(acc, form.entry_date);
});

// Automatically update "Tunai ex D/M ..." rows whenever the user changes entry_date
watch(() => form.entry_date, (newDate) => {
    const exLabel = formatShortDM(newDate, -1);
    form.items.forEach((item) => {
        if (item.autoEx === 'rek1_shift1') {
            item.description = `Tunai ex ${exLabel} Shift I`;
        } else if (item.autoEx === 'rek2_lpg') {
            item.description = `Tunai ex O. LPG M ${exLabel}`;
        }
    });
});

const navigateFilter = (overrides = {}) => {
    router.get(route('kas.index'), {
        branch_id: props.filters.branch_id,
        account: props.filters.account,
        year: props.filters.year,
        month: props.filters.month,
        ...overrides,
    }, { preserveState: true, replace: true });
};

const addShiftPackage = (offsetDays = -1) => {
    const exLabel = formatShortDM(form.entry_date, offsetDays);
    form.items.push(
        { description: `Tunai ex ${exLabel} Shift I`, category: 'shift_omzet', debit: 0, credit: 0, autoEx: offsetDays === -1 ? 'rek1_shift1' : null },
        { description: 'Shift II', category: 'shift_omzet', debit: 0, credit: 0, autoEx: null },
        { description: 'Shift III', category: 'shift_omzet', debit: 0, credit: 0, autoEx: null },
    );
};

const addRow = (description = '', category = null, autoEx = null) => {
    const defaultCat = category || (props.accountType === 'rek1' ? 'shift_omzet' : 'non_bbm_income');
    form.items.push({
        description,
        category: defaultCat,
        debit: 0,
        credit: 0,
        autoEx,
    });
};

const addLpgRow = (offsetDays = -1) => {
    const exLabel = formatShortDM(form.entry_date, offsetDays);
    addRow(`Tunai ex O. LPG M ${exLabel}`, 'non_bbm_income', offsetDays === -1 ? 'rek2_lpg' : null);
};

const removeDraftRow = (index) => {
    if (form.items.length === 1) {
        form.items[0].description = '';
        form.items[0].debit = 0;
        form.items[0].credit = 0;
        return;
    }
    form.items.splice(index, 1);
};

const batchPreview = computed(() => {
    const activeItems = form.items.filter((i) => (Number(i.debit) || 0) > 0 || (Number(i.credit) || 0) > 0);
    if (props.accountType === 'rek1') {
        const totalOmzet = activeItems
            .filter((i) => !i.description.toLowerCase().includes('total'))
            .reduce((s, i) => s + (Number(i.debit) || 0), 0);
        const margin = Math.round(totalOmzet * Number(props.branch.margin_rate || 0));
        const tsps = activeItems
            .filter((i) => i.category === 'tsps_tukar' || i.description.toUpperCase().includes('TSPS'))
            .reduce((s, i) => s + (Number(i.credit) || 0), 0);
        return {
            count: activeItems.length,
            totalOmzet,
            margin,
            tsps,
            storBca: totalOmzet - margin - tsps,
        };
    }
    const income = activeItems.reduce((s, i) => s + (Number(i.debit) || 0), 0);
    const expense = activeItems.reduce((s, i) => s + (Number(i.credit) || 0), 0);
    return {
        count: activeItems.length,
        income,
        expense,
        net: income - expense,
    };
});

const submitBatch = () => {
    form.post(route('kas.store-batch'), {
        preserveScroll: true,
        onSuccess: () => {
            form.items = buildInitialRows(props.accountType, form.entry_date);
            showForm.value = false;
        },
    });
};

const removeEntry = (entry) => {
    if (!confirm(`Hapus mutasi "${entry.description}"?`)) return;
    router.delete(route('kas.destroy', entry.id), { preserveScroll: true });
};

const editingEntryId = ref(null);
const editEntryForm = useForm({
    entry_date: '',
    description: '',
    debit: 0,
    credit: 0,
});

const startEditEntry = (entry) => {
    editingEntryId.value = entry.id;
    editEntryForm.entry_date = entry.entry_date;
    editEntryForm.description = entry.description;
    editEntryForm.debit = Number(entry.debit);
    editEntryForm.credit = Number(entry.credit);
};

const cancelEditEntry = () => {
    editingEntryId.value = null;
};

const saveEditEntry = (entryId) => {
    editEntryForm.put(route('kas.update', entryId), {
        preserveScroll: true,
        onSuccess: () => {
            editingEntryId.value = null;
        },
    });
};

const summaryTotals = computed(() => {
    if (props.accountType === 'rek1') {
        return {
            total_omzet: props.dailySummary.reduce((s, r) => s + r.total_omzet, 0),
            margin_amount: props.dailySummary.reduce((s, r) => s + r.margin_amount, 0),
            after_margin: props.dailySummary.reduce((s, r) => s + r.after_margin, 0),
            tsps_tukar: props.dailySummary.reduce((s, r) => s + r.tsps_tukar, 0),
            stor_bca: props.dailySummary.reduce((s, r) => s + r.stor_bca, 0),
        };
    }
    return {
        margin_from_rek1: props.dailySummary.reduce((s, r) => s + r.margin_from_rek1, 0),
        other_income: props.dailySummary.reduce((s, r) => s + r.other_income, 0),
        other_expense: props.dailySummary.reduce((s, r) => s + r.other_expense, 0),
        stor_bca: props.dailySummary.reduce((s, r) => s + r.stor_bca, 0),
    };
});

const summaryPagination = useDatePagination(() => props.dailySummary, 'date', 15);
const entriesPagination = useDatePagination(() => props.entries, 'entry_date', 15);
</script>

<template>
    <Head title="Kas Harian Cabang" />
    <AppLayout title="Kas Harian Cabang (Rek 1 & Rek 2)">
        <template #header-actions>
            <button
                type="button"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-teal-700 hover:bg-teal-800 text-white text-xs font-semibold transition-colors"
                @click="showForm = !showForm"
            >
                <X v-if="showForm" class="w-4 h-4" />
                <Plus v-else class="w-4 h-4" />
                <span>{{ showForm ? 'Tutup Form' : 'Catat Mutasi per Tanggal' }}</span>
            </button>
        </template>

        <!-- Unified Branch + Account + Period Switcher -->
        <div class="bg-white border border-slate-200 rounded-xl p-4 flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-3">
                <div class="inline-flex rounded-lg bg-slate-100 p-1">
                    <button
                        v-for="b in branches"
                        :key="b.id"
                        type="button"
                        :class="[
                            'px-3 py-1.5 rounded-md text-xs font-semibold transition-colors',
                            branch.id === b.id ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900',
                        ]"
                        @click="navigateFilter({ branch_id: b.id })"
                    >
                        {{ b.name }}
                    </button>
                </div>

                <div class="inline-flex rounded-lg bg-slate-100 p-1">
                    <button
                        type="button"
                        :class="[
                            'px-3 py-1.5 rounded-md text-xs font-semibold transition-colors',
                            accountType === 'rek1' ? 'bg-blue-700 text-white' : 'text-slate-600 hover:text-slate-900',
                        ]"
                        @click="navigateFilter({ account: 'rek1' })"
                    >
                        Rek 1 • Omzet BBM & TSPS
                    </button>
                    <button
                        type="button"
                        :class="[
                            'px-3 py-1.5 rounded-md text-xs font-semibold transition-colors',
                            accountType === 'rek2' ? 'bg-violet-700 text-white' : 'text-slate-600 hover:text-slate-900',
                        ]"
                        @click="navigateFilter({ account: 'rek2' })"
                    >
                        Rek 2 • Margin, LPG & Biaya
                    </button>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <select
                    :value="filters.month"
                    class="text-xs rounded-lg border border-slate-300 bg-white px-2.5 py-1.5"
                    @change="navigateFilter({ month: Number($event.target.value) })"
                >
                    <option v-for="m in MONTHS" :key="m.value" :value="m.value">{{ m.label }}</option>
                </select>
                <input
                    :value="filters.year"
                    type="number"
                    class="w-20 num text-xs rounded-lg border border-slate-300 px-2.5 py-1.5"
                    @change="navigateFilter({ year: Number($event.target.value) })"
                >
            </div>
        </div>

        <!-- Batch Multi-Row Input per Date -->
        <div v-if="showForm" class="bg-white border border-teal-200 rounded-xl p-5 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">
                        Input Mutasi per Tanggal — {{ branch.name }} ({{ accountType === 'rek1' ? 'Rekening 1' : 'Rekening 2' }})
                    </h2>
                    <p class="text-xs text-slate-500">
                        Pilih tanggal setor, lalu isi beberapa baris sekaligus. Uraian <span class="num text-slate-700">ex {{ formatShortDM(form.entry_date, -1) }}</span> otomatis mengikuti H-1. Baris bernilai 0 otomatis dilewati.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <label for="batch-date" class="text-xs font-semibold text-slate-700">Tanggal Setor:</label>
                    <input
                        id="batch-date"
                        v-model="form.entry_date"
                        type="date"
                        required
                        class="text-sm num rounded-lg border border-slate-300 px-3 py-1.5 bg-white"
                    >
                </div>
            </div>

            <!-- Quick-Add Preset Toolbar -->
            <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-slate-100">
                <span class="text-[11px] font-semibold text-slate-500">Tambah cepat:</span>
                <template v-if="accountType === 'rek1'">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-teal-50 hover:bg-teal-100 border border-teal-200 text-xs font-medium text-teal-800"
                        @click="addShiftPackage(-1)"
                    >
                        <Layers class="w-3.5 h-3.5" />
                        <span>+ 3 Shift (ex {{ formatShortDM(form.entry_date, -1) }})</span>
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-slate-100 hover:bg-slate-200 text-xs font-medium text-slate-700"
                        @click="addShiftPackage(-2)"
                    >
                        <Layers class="w-3.5 h-3.5" />
                        <span>+ 3 Shift (ex {{ formatShortDM(form.entry_date, -2) }})</span>
                    </button>
                    <button
                        type="button"
                        class="px-2.5 py-1 rounded-md bg-amber-50 hover:bg-amber-100 border border-amber-200 text-xs font-medium text-amber-900"
                        @click="addRow('Stor BCA ex TSPS Tukar', 'tsps_tukar')"
                    >
                        + Stor BCA ex TSPS Tukar
                    </button>
                    <button
                        type="button"
                        class="px-2.5 py-1 rounded-md bg-slate-100 hover:bg-slate-200 text-xs font-medium text-slate-700"
                        @click="addRow('', 'shift_omzet')"
                    >
                        + Baris Kosong
                    </button>
                </template>
                <template v-else>
                    <button
                        type="button"
                        class="px-2.5 py-1 rounded-md bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-xs font-medium text-emerald-900"
                        @click="addLpgRow(-1)"
                    >
                        + Tunai ex O. LPG M {{ formatShortDM(form.entry_date, -1) }}
                    </button>
                    <button
                        type="button"
                        class="px-2.5 py-1 rounded-md bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-xs font-medium text-emerald-900"
                        @click="addRow('Kotak toilet', 'non_bbm_income')"
                    >
                        + Kotak Toilet
                    </button>
                    <button
                        type="button"
                        class="px-2.5 py-1 rounded-md bg-red-50 hover:bg-red-100 border border-red-200 text-xs font-medium text-red-900"
                        @click="addRow('U/ Kas SPBU', 'operational_expense')"
                    >
                        + U/ Kas SPBU
                    </button>
                    <button
                        type="button"
                        class="px-2.5 py-1 rounded-md bg-slate-100 hover:bg-slate-200 text-xs font-medium text-slate-700"
                        @click="addRow('', 'other')"
                    >
                        + Baris Kosong
                    </button>
                </template>
            </div>

            <!-- Editable Multi-Row Ledger -->
            <form class="space-y-4" @submit.prevent="submitBatch">
                <div class="overflow-x-auto border border-slate-200 rounded-lg">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-xs font-semibold text-slate-600 border-b border-slate-200">
                            <tr>
                                <th class="text-left px-3 py-2.5 w-10">#</th>
                                <th class="text-left px-3 py-2.5 min-w-[220px]">Uraian</th>
                                <th class="text-right px-3 py-2.5 w-44">Debet / Masuk (Rp)</th>
                                <th class="text-right px-3 py-2.5 w-44">Kredit / Keluar (Rp)</th>
                                <th class="text-right px-3 py-2.5 w-12" />
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(row, idx) in form.items" :key="idx" class="bg-white">
                                <td class="px-3 py-2 text-xs num text-slate-400">{{ idx + 1 }}</td>
                                <td class="px-3 py-2">
                                    <input
                                        v-model="row.description"
                                        type="text"
                                        required
                                        placeholder="Tulis uraian mutasi..."
                                        class="w-full text-sm rounded-md border border-slate-300 px-2.5 py-1.5 focus:border-teal-700 focus:ring-1 focus:ring-teal-700 outline-none"
                                        @input="row.autoEx = null"
                                    >
                                </td>
                                <td class="px-3 py-2">
                                    <input
                                        v-model.number="row.debit"
                                        type="number"
                                        min="0"
                                        step="1"
                                        class="w-full text-right num text-sm rounded-md border border-slate-300 px-2.5 py-1.5 focus:border-teal-700 focus:ring-1 focus:ring-teal-700 outline-none"
                                    >
                                </td>
                                <td class="px-3 py-2">
                                    <input
                                        v-model.number="row.credit"
                                        type="number"
                                        min="0"
                                        step="1"
                                        class="w-full text-right num text-sm rounded-md border border-slate-300 px-2.5 py-1.5 focus:border-teal-700 focus:ring-1 focus:ring-teal-700 outline-none"
                                    >
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <button
                                        type="button"
                                        class="p-1.5 rounded-md text-slate-400 hover:text-red-600 hover:bg-red-50"
                                        aria-label="Hapus baris"
                                        @click="removeDraftRow(idx)"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p v-if="form.errors.items" class="text-xs text-red-600">{{ form.errors.items }}</p>

                <!-- Live Summary & Submit Bar -->
                <div class="flex flex-wrap items-center justify-between gap-4 bg-slate-50 border border-slate-200 rounded-lg px-4 py-3">
                    <div v-if="accountType === 'rek1'" class="flex flex-wrap items-center gap-x-6 gap-y-1 text-xs">
                        <div>
                            <span class="text-slate-500">Omzet: </span>
                            <span class="font-semibold text-slate-900 num">{{ formatRupiah(batchPreview.totalOmzet) }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500">Margin {{ (branch.margin_rate * 100).toFixed(1) }}%: </span>
                            <span class="font-semibold text-violet-700 num">{{ formatRupiah(batchPreview.margin) }}</span>
                        </div>
                        <div v-if="batchPreview.tsps > 0">
                            <span class="text-slate-500">TSPS: </span>
                            <span class="font-semibold text-amber-700 num">{{ formatRupiah(batchPreview.tsps) }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500">Estimasi Stor BCA: </span>
                            <span class="font-bold text-teal-800 num text-sm">{{ formatRupiah(batchPreview.storBca) }}</span>
                        </div>
                    </div>
                    <div v-else class="flex flex-wrap items-center gap-x-6 gap-y-1 text-xs">
                        <div>
                            <span class="text-slate-500">Pemasukan Lain: </span>
                            <span class="font-semibold text-emerald-700 num">{{ formatRupiah(batchPreview.income) }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500">Pengeluaran: </span>
                            <span class="font-semibold text-red-700 num">{{ formatRupiah(batchPreview.expense) }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500">Selisih Mutasi: </span>
                            <span class="font-bold num text-sm" :class="batchPreview.net < 0 ? 'text-red-700' : 'text-teal-800'">
                                {{ formatRupiah(batchPreview.net) }}
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" class="px-3.5 py-2 text-xs font-medium text-slate-600 hover:text-slate-900" @click="showForm = false">Batal</button>
                        <button
                            type="submit"
                            :disabled="form.processing || batchPreview.count === 0"
                            class="px-4 py-2 rounded-lg bg-teal-700 hover:bg-teal-800 disabled:opacity-50 text-white text-xs font-semibold transition-colors"
                        >
                            {{ form.processing ? 'Menyimpan...' : `Simpan ${batchPreview.count} Baris Mutasi` }}
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Ringkasan Otomatis Harian -->
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="px-5 py-3.5 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">
                        Ringkasan Otomatis Harian — {{ branch.name }} ({{ accountType === 'rek1' ? 'Rek 1' : 'Rek 2' }})
                    </h2>
                    <p class="text-xs text-slate-500">
                        <template v-if="accountType === 'rek1'">
                            Margin otomatis {{ (branch.margin_rate * 100).toFixed(1) }}% dari total omzet, dikurangi TSPS Tukar = Stor Tunai ke BCA.
                        </template>
                        <template v-else>
                            Margin otomatis masuk dari Rek 1 + Pemasukan Lain/LPG − Pengeluaran Biaya = Stor Tunai ke BCA Rek 2.
                        </template>
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table v-if="accountType === 'rek1'" class="w-full text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold text-slate-600 border-b border-slate-200">
                        <tr>
                            <th class="text-left px-5 py-3">Tanggal</th>
                            <th class="text-right px-4 py-3">Total Omzet</th>
                            <th class="text-right px-4 py-3">Margin ({{ (branch.margin_rate * 100).toFixed(1) }}%)</th>
                            <th class="text-right px-4 py-3">Setelah Margin</th>
                            <th class="text-right px-4 py-3">TSPS Tukar</th>
                            <th class="text-right px-5 py-3">Stor Tunai ke BCA</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="summaryPagination.paginatedItems.value.length === 0">
                            <td colspan="6" class="px-5 py-6 text-center text-xs text-slate-500">Belum ada ringkasan kas pada bulan ini.</td>
                        </tr>
                        <tr v-for="row in summaryPagination.paginatedItems.value" :key="row.date" class="hover:bg-slate-50/70">
                            <td class="px-5 py-3 num text-xs font-medium text-slate-800">{{ formatDate(row.date) }}</td>
                            <td class="px-4 py-3 text-right num text-slate-900">{{ formatRupiah(row.total_omzet) }}</td>
                            <td class="px-4 py-3 text-right num text-violet-700">{{ formatRupiah(row.margin_amount) }}</td>
                            <td class="px-4 py-3 text-right num text-slate-700">{{ formatRupiah(row.after_margin) }}</td>
                            <td class="px-4 py-3 text-right num text-amber-700">{{ formatRupiah(row.tsps_tukar) }}</td>
                            <td class="px-5 py-3 text-right num font-bold text-teal-800">{{ formatRupiah(row.stor_bca) }}</td>
                        </tr>
                    </tbody>
                    <tfoot v-if="dailySummary.length > 0" class="bg-slate-50 border-t border-slate-200 font-semibold">
                        <tr>
                            <td class="px-5 py-3 text-slate-900">TOTAL</td>
                            <td class="px-4 py-3 text-right num">{{ formatRupiah(summaryTotals.total_omzet) }}</td>
                            <td class="px-4 py-3 text-right num text-violet-700">{{ formatRupiah(summaryTotals.margin_amount) }}</td>
                            <td class="px-4 py-3 text-right num">{{ formatRupiah(summaryTotals.after_margin) }}</td>
                            <td class="px-4 py-3 text-right num text-amber-700">{{ formatRupiah(summaryTotals.tsps_tukar) }}</td>
                            <td class="px-5 py-3 text-right num text-teal-900">{{ formatRupiah(summaryTotals.stor_bca) }}</td>
                        </tr>
                    </tfoot>
                </table>

                <table v-else class="w-full text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold text-slate-600 border-b border-slate-200">
                        <tr>
                            <th class="text-left px-5 py-3">Tanggal</th>
                            <th class="text-right px-4 py-3">Margin dari Rek 1</th>
                            <th class="text-right px-4 py-3">Pemasukan Lain / LPG</th>
                            <th class="text-right px-4 py-3">Pengeluaran / Biaya</th>
                            <th class="text-right px-5 py-3">Stor Tunai ke BCA</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="summaryPagination.paginatedItems.value.length === 0">
                            <td colspan="5" class="px-5 py-6 text-center text-xs text-slate-500">Belum ada ringkasan kas Rek 2 pada bulan ini.</td>
                        </tr>
                        <tr v-for="row in summaryPagination.paginatedItems.value" :key="row.date" class="hover:bg-slate-50/70">
                            <td class="px-5 py-3 num text-xs font-medium text-slate-800">{{ formatDate(row.date) }}</td>
                            <td class="px-4 py-3 text-right num text-violet-700">{{ formatRupiah(row.margin_from_rek1) }}</td>
                            <td class="px-4 py-3 text-right num text-emerald-700">{{ formatRupiah(row.other_income) }}</td>
                            <td class="px-4 py-3 text-right num text-red-700">{{ formatRupiah(row.other_expense) }}</td>
                            <td class="px-5 py-3 text-right num font-bold" :class="row.stor_bca < 0 ? 'text-red-700' : 'text-teal-800'">
                                {{ formatRupiah(row.stor_bca) }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot v-if="dailySummary.length > 0" class="bg-slate-50 border-t border-slate-200 font-semibold">
                        <tr>
                            <td class="px-5 py-3 text-slate-900">TOTAL</td>
                            <td class="px-4 py-3 text-right num text-violet-700">{{ formatRupiah(summaryTotals.margin_from_rek1) }}</td>
                            <td class="px-4 py-3 text-right num text-emerald-700">{{ formatRupiah(summaryTotals.other_income) }}</td>
                            <td class="px-4 py-3 text-right num text-red-700">{{ formatRupiah(summaryTotals.other_expense) }}</td>
                            <td class="px-5 py-3 text-right num text-teal-900">{{ formatRupiah(summaryTotals.stor_bca) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <DatePaginationBar
                :current-page="summaryPagination.currentPage.value"
                :total-pages="summaryPagination.totalPages.value"
                :total-dates="summaryPagination.totalDates.value"
                :page-date-count="summaryPagination.pageDates.value.length"
                :date-range-label="summaryPagination.dateRangeLabel.value"
                :dates-per-page="15"
                @change="summaryPagination.goToPage"
            />
        </div>

        <!-- Detailed Ledger Entries -->
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="px-5 py-3.5 border-b border-slate-200">
                <h2 class="text-sm font-semibold text-slate-900">Rincian Mutasi Kas ({{ entries.length }} baris)</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold text-slate-600 border-b border-slate-200">
                        <tr>
                            <th class="text-left px-5 py-3">Tanggal</th>
                            <th class="text-left px-4 py-3 min-w-[210px]">Uraian</th>
                            <th class="text-right px-4 py-3">Debet (Masuk)</th>
                            <th class="text-right px-4 py-3">Kredit (Keluar)</th>
                            <th class="text-right px-5 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="entriesPagination.paginatedItems.value.length === 0">
                            <td colspan="5" class="px-5 py-8 text-center text-xs text-slate-500">Belum ada mutasi kas yang dicatat pada periode ini.</td>
                        </tr>
                        <tr v-for="e in entriesPagination.paginatedItems.value" :key="e.id" class="hover:bg-slate-50/70">
                            <template v-if="editingEntryId === e.id">
                                <td class="px-5 py-2 whitespace-nowrap">
                                    <input
                                        v-model="editEntryForm.entry_date"
                                        type="date"
                                        class="text-xs num rounded-md border border-teal-400 px-2 py-1 bg-white"
                                    >
                                </td>
                                <td class="px-4 py-2">
                                    <input
                                        v-model="editEntryForm.description"
                                        type="text"
                                        class="w-full text-sm rounded-md border border-teal-400 px-2.5 py-1 bg-white"
                                    >
                                </td>
                                <td class="px-4 py-2">
                                    <input
                                        v-model.number="editEntryForm.debit"
                                        type="number"
                                        min="0"
                                        step="1"
                                        class="w-36 ml-auto block text-right num text-sm rounded-md border border-teal-400 px-2 py-1 bg-white"
                                    >
                                </td>
                                <td class="px-4 py-2">
                                    <input
                                        v-model.number="editEntryForm.credit"
                                        type="number"
                                        min="0"
                                        step="1"
                                        class="w-36 ml-auto block text-right num text-sm rounded-md border border-teal-400 px-2 py-1 bg-white"
                                    >
                                </td>
                                <td class="px-5 py-2 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button
                                            type="button"
                                            :disabled="editEntryForm.processing"
                                            class="p-1.5 rounded-md bg-teal-700 text-white hover:bg-teal-800"
                                            aria-label="Simpan perubahan"
                                            @click="saveEditEntry(e.id)"
                                        >
                                            <Check class="w-4 h-4" />
                                        </button>
                                        <button
                                            type="button"
                                            class="p-1.5 rounded-md text-slate-500 hover:bg-slate-200"
                                            aria-label="Batal edit"
                                            @click="cancelEditEntry"
                                        >
                                            <X class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </template>
                            <template v-else>
                                <td class="px-5 py-2.5 num text-xs text-slate-600 whitespace-nowrap">{{ formatDate(e.entry_date) }}</td>
                                <td class="px-4 py-2.5 text-slate-900 min-w-[210px]">{{ e.description }}</td>
                                <td class="px-4 py-2.5 text-right num" :class="e.debit > 0 ? 'text-emerald-700 font-medium' : 'text-slate-300'">
                                    {{ e.debit > 0 ? formatRupiah(e.debit) : '-' }}
                                </td>
                                <td class="px-4 py-2.5 text-right num" :class="e.credit > 0 ? 'text-red-700 font-medium' : 'text-slate-300'">
                                    {{ e.credit > 0 ? formatRupiah(e.credit) : '-' }}
                                </td>
                                <td class="px-5 py-2.5 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button
                                            type="button"
                                            class="p-1.5 rounded-md text-slate-400 hover:text-teal-700 hover:bg-teal-50"
                                            aria-label="Edit mutasi"
                                            @click="startEditEntry(e)"
                                        >
                                            <Pencil class="w-4 h-4" />
                                        </button>
                                        <button
                                            type="button"
                                            class="p-1.5 rounded-md text-slate-400 hover:text-red-600 hover:bg-red-50"
                                            aria-label="Hapus mutasi"
                                            @click="removeEntry(e)"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </template>
                        </tr>
                    </tbody>
                </table>
            </div>
            <DatePaginationBar
                :current-page="entriesPagination.currentPage.value"
                :total-pages="entriesPagination.totalPages.value"
                :total-dates="entriesPagination.totalDates.value"
                :page-date-count="entriesPagination.pageDates.value.length"
                :date-range-label="entriesPagination.dateRangeLabel.value"
                :dates-per-page="15"
                @change="entriesPagination.goToPage"
            />
        </div>
    </AppLayout>
</template>
