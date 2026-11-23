<script setup>
import { Fuel } from 'lucide-vue-next';

defineProps({
    show: { type: Boolean, default: false },
    label: { type: String, default: 'Memuat data...' },
});
</script>

<template>
    <Transition
        enter-active-class="transition-opacity duration-150 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="show"
            role="status"
            aria-live="polite"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/20 backdrop-blur-[2px]"
        >
            <div class="bg-white border border-slate-200 rounded-2xl px-7 py-6 flex flex-col items-center gap-3.5 shadow-lg">
                <div class="relative w-12 h-12 flex items-center justify-center">
                    <div class="absolute inset-0 rounded-full border-[3px] border-slate-200" />
                    <div class="absolute inset-0 rounded-full border-[3px] border-teal-700 border-t-transparent animate-spin" />
                    <Fuel class="w-5 h-5 text-teal-700" />
                </div>
                <div class="text-center">
                    <div class="text-sm font-semibold text-slate-900">{{ label }}</div>
                    <div class="text-xs text-slate-500 mt-0.5">Rimasa SPBU</div>
                </div>
            </div>
        </div>
    </Transition>
</template>
