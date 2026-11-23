<script setup>
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';

defineProps({
    currentPage: { type: Number, required: true },
    totalPages: { type: Number, required: true },
    totalDates: { type: Number, required: true },
    pageDateCount: { type: Number, required: true },
    dateRangeLabel: { type: String, required: true },
    datesPerPage: { type: Number, default: 15 },
});

const emit = defineEmits(['change']);
</script>

<template>
    <div
        v-if="totalDates > 0"
        class="px-5 py-3 border-t border-slate-200 bg-slate-50/70 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-600"
    >
        <div>
            Menampilkan <span class="font-semibold text-slate-800">{{ pageDateCount }}</span> dari
            <span class="font-semibold text-slate-800">{{ totalDates }}</span> tanggal
            (<span class="num text-slate-700">{{ dateRangeLabel }}</span>)
            • maks {{ datesPerPage }} tgl/halaman
        </div>

        <div class="flex items-center gap-1.5">
            <button
                type="button"
                :disabled="currentPage <= 1"
                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md border border-slate-300 bg-white text-slate-700 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                @click="emit('change', currentPage - 1)"
            >
                <ChevronLeft class="w-3.5 h-3.5" />
                <span>Sebelumnya</span>
            </button>

            <button
                v-for="p in totalPages"
                :key="p"
                type="button"
                :class="[
                    'min-w-[30px] px-2 py-1.5 rounded-md font-semibold num transition-colors',
                    p === currentPage
                        ? 'bg-teal-700 text-white'
                        : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-100',
                ]"
                @click="emit('change', p)"
            >
                {{ p }}
            </button>

            <button
                type="button"
                :disabled="currentPage >= totalPages"
                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md border border-slate-300 bg-white text-slate-700 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                @click="emit('change', currentPage + 1)"
            >
                <span>Berikutnya</span>
                <ChevronRight class="w-3.5 h-3.5" />
            </button>
        </div>
    </div>
</template>
