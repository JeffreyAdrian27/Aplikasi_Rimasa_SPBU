<script setup>
import { reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Save } from 'lucide-vue-next';

const props = defineProps({
    branches: Array,
    users: Array,
});

const branchEdits = reactive(
    Object.fromEntries(
        props.branches.map((b) => [
            b.id,
            { name: b.name, margin_percent: Number((b.margin_rate * 100).toFixed(2)) },
        ])
    )
);

const saveBranch = (branchId) => {
    router.patch(route('settings.branch.update', branchId), branchEdits[branchId], {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Pengaturan" />
    <AppLayout title="Pengaturan Cabang & Pengguna">
        <!-- Branch Margin Config -->
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200">
                <h2 class="text-sm font-semibold text-slate-900">Pengaturan Margin BBM per Cabang</h2>
                <p class="text-xs text-slate-500">Persentase margin otomatis memotong omzet Rek 1 dan dipindahkan ke Rek 2 setiap hari.</p>
            </div>
            <div class="divide-y divide-slate-100">
                <div
                    v-for="b in branches"
                    :key="b.id"
                    class="p-5 flex flex-wrap items-end gap-4"
                >
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-xs font-medium text-slate-700 mb-1">Nama Cabang ({{ b.code }})</label>
                        <input v-model="branchEdits[b.id].name" type="text" class="w-full text-sm rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div class="w-40">
                        <label class="block text-xs font-medium text-slate-700 mb-1">Margin (%)</label>
                        <input v-model.number="branchEdits[b.id].margin_percent" type="number" step="0.1" min="0" max="50" class="w-full num text-sm rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-teal-700 hover:bg-teal-800 text-white text-xs font-semibold"
                        @click="saveBranch(b.id)"
                    >
                        <Save class="w-3.5 h-3.5" />
                        <span>Simpan</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- User Roles Directory -->
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200">
                <h2 class="text-sm font-semibold text-slate-900">Daftar Akun Pengguna (Multi-Role)</h2>
                <p class="text-xs text-slate-500">Owner memiliki akses lintas cabang; Admin Cabang hanya melihat dan mencatat data cabangnya sendiri.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold text-slate-600 border-b border-slate-200">
                        <tr>
                            <th class="text-left px-5 py-3">Nama</th>
                            <th class="text-left px-4 py-3">Username</th>
                            <th class="text-left px-4 py-3">Role</th>
                            <th class="text-left px-5 py-3">Cabang</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="u in users" :key="u.id">
                            <td class="px-5 py-3 font-medium text-slate-900">{{ u.name }}</td>
                            <td class="px-4 py-3 num text-xs text-slate-600">{{ u.username }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="px-2 py-0.5 rounded-full text-xs font-medium"
                                    :class="u.role === 'owner' ? 'bg-teal-50 text-teal-800' : 'bg-blue-50 text-blue-800'"
                                >
                                    {{ u.role === 'owner' ? 'Owner' : 'Admin Cabang' }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-700">{{ u.branch?.name || 'Semua Cabang' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
