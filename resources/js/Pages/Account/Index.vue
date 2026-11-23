<script setup>
import { computed, reactive } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { KeyRound, ShieldCheck } from 'lucide-vue-next';

const page = usePage();
const user = computed(() => page.props.auth?.user);

const visible = reactive({
    current: false,
    next: false,
    confirm: false,
});

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.put(route('account.password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <Head title="Akun & Ubah Kata Sandi" />
    <AppLayout title="Akun & Ubah Kata Sandi">
        <div class="max-w-xl space-y-6">
            <!-- Account Info -->
            <div class="bg-white border border-slate-200 rounded-xl p-5 flex items-center justify-between gap-4">
                <div>
                    <div class="text-sm font-semibold text-slate-900">{{ user?.name }}</div>
                    <div class="text-xs text-slate-500 mt-0.5">
                        Username: <span class="num font-medium text-slate-700">{{ user?.username }}</span>
                        • {{ user?.role === 'owner' ? 'Owner (Semua Cabang)' : `Admin Cabang ${user?.branch_name}` }}
                    </div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-700 flex items-center justify-center shrink-0">
                    <ShieldCheck class="w-5 h-5" />
                </div>
            </div>

            <!-- Change Password Form -->
            <div class="bg-white border border-slate-200 rounded-xl p-6">
                <div class="flex items-center gap-2.5 mb-5">
                    <KeyRound class="w-4 h-4 text-teal-700" />
                    <h2 class="text-sm font-semibold text-slate-900">Ubah Kata Sandi</h2>
                </div>

                <form class="space-y-4" @submit.prevent="submit">
                    <div>
                        <label for="current_password" class="block text-xs font-semibold text-slate-700 mb-1.5">Kata Sandi Saat Ini</label>
                        <div class="relative">
                            <input
                                id="current_password"
                                v-model="form.current_password"
                                :type="visible.current ? 'text' : 'password'"
                                required
                                autocomplete="current-password"
                                class="w-full pr-11 px-3 py-2.5 text-sm rounded-lg border border-slate-300 bg-white focus:border-teal-700 focus:ring-2 focus:ring-teal-700/20 outline-none"
                                placeholder="Masukkan kata sandi lama"
                            >
                            <button
                                type="button"
                                class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-100"
                                :aria-label="visible.current ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                                @click="visible.current = !visible.current"
                            >
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 transition-transform duration-200" :class="visible.current ? 'scale-105 text-teal-700' : 'scale-100'" aria-hidden="true">
                                    <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0" />
                                    <circle cx="12" cy="12" r="3" class="origin-center transition-transform duration-200" :class="visible.current ? 'scale-100' : 'scale-75'" />
                                    <line x1="3" y1="3" x2="21" y2="21" stroke-dasharray="26" :stroke-dashoffset="visible.current ? 26 : 0" class="transition-[stroke-dashoffset,opacity] duration-200" :class="visible.current ? 'opacity-0' : 'opacity-100'" />
                                </svg>
                            </button>
                        </div>
                        <p v-if="form.errors.current_password" class="mt-1 text-xs text-red-600">{{ form.errors.current_password }}</p>
                    </div>

                    <div>
                        <label for="new_password" class="block text-xs font-semibold text-slate-700 mb-1.5">Kata Sandi Baru</label>
                        <div class="relative">
                            <input
                                id="new_password"
                                v-model="form.password"
                                :type="visible.next ? 'text' : 'password'"
                                required
                                minlength="6"
                                autocomplete="new-password"
                                class="w-full pr-11 px-3 py-2.5 text-sm rounded-lg border border-slate-300 bg-white focus:border-teal-700 focus:ring-2 focus:ring-teal-700/20 outline-none"
                                placeholder="Minimal 6 karakter"
                            >
                            <button
                                type="button"
                                class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-100"
                                :aria-label="visible.next ? 'Sembunyikan kata sandi baru' : 'Tampilkan kata sandi baru'"
                                @click="visible.next = !visible.next"
                            >
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 transition-transform duration-200" :class="visible.next ? 'scale-105 text-teal-700' : 'scale-100'" aria-hidden="true">
                                    <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0" />
                                    <circle cx="12" cy="12" r="3" class="origin-center transition-transform duration-200" :class="visible.next ? 'scale-100' : 'scale-75'" />
                                    <line x1="3" y1="3" x2="21" y2="21" stroke-dasharray="26" :stroke-dashoffset="visible.next ? 26 : 0" class="transition-[stroke-dashoffset,opacity] duration-200" :class="visible.next ? 'opacity-0' : 'opacity-100'" />
                                </svg>
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-xs font-semibold text-slate-700 mb-1.5">Konfirmasi Kata Sandi Baru</label>
                        <div class="relative">
                            <input
                                id="confirm_password"
                                v-model="form.password_confirmation"
                                :type="visible.confirm ? 'text' : 'password'"
                                required
                                minlength="6"
                                autocomplete="new-password"
                                class="w-full pr-11 px-3 py-2.5 text-sm rounded-lg border border-slate-300 bg-white focus:border-teal-700 focus:ring-2 focus:ring-teal-700/20 outline-none"
                                placeholder="Ulangi kata sandi baru"
                            >
                            <button
                                type="button"
                                class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-100"
                                :aria-label="visible.confirm ? 'Sembunyikan konfirmasi sandi' : 'Tampilkan konfirmasi sandi'"
                                @click="visible.confirm = !visible.confirm"
                            >
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 transition-transform duration-200" :class="visible.confirm ? 'scale-105 text-teal-700' : 'scale-100'" aria-hidden="true">
                                    <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0" />
                                    <circle cx="12" cy="12" r="3" class="origin-center transition-transform duration-200" :class="visible.confirm ? 'scale-100' : 'scale-75'" />
                                    <line x1="3" y1="3" x2="21" y2="21" stroke-dasharray="26" :stroke-dashoffset="visible.confirm ? 26 : 0" class="transition-[stroke-dashoffset,opacity] duration-200" :class="visible.confirm ? 'opacity-0' : 'opacity-100'" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-2.5 rounded-lg bg-teal-700 hover:bg-teal-800 disabled:opacity-50 text-white text-xs font-semibold transition-colors"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Kata Sandi Baru' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
