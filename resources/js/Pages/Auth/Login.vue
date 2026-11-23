<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Fuel, Lock, User, ArrowRight } from 'lucide-vue-next';

const showPassword = ref(false);

const form = useForm({
    username: 'owner',
    password: 'password',
    remember: true,
});

const fillDemo = (username) => {
    form.username = username;
    form.password = 'password';
};

const submit = () => {
    form.post(route('login.store'));
};
</script>

<template>
    <Head title="Masuk" />
    <div class="min-h-screen bg-slate-50 flex flex-col justify-center items-center px-4 py-10">
        <div class="w-full max-w-md bg-white border border-slate-200 rounded-2xl p-7 sm:p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-teal-700 text-white flex items-center justify-center">
                    <Fuel class="w-5 h-5" />
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-900 tracking-tight">Rimasa SPBU</h1>
                    <p class="text-xs text-slate-500">Sistem Manajemen Keuangan BBM Multi-Produk</p>
                </div>
            </div>

            <form class="space-y-4" @submit.prevent="submit">
                <div>
                    <label for="username" class="block text-xs font-semibold text-slate-700 mb-1.5">Username</label>
                    <div class="relative">
                        <User class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                        <input
                            id="username"
                            v-model="form.username"
                            type="text"
                            required
                            autocomplete="username"
                            class="w-full pl-9 pr-3 py-2.5 text-sm rounded-lg border border-slate-300 bg-white focus:border-teal-700 focus:ring-2 focus:ring-teal-700/20 outline-none"
                            placeholder="owner / admin_ts / admin_kakap"
                        >
                    </div>
                    <p v-if="form.errors.username" class="mt-1 text-xs text-red-600">{{ form.errors.username }}</p>
                </div>

                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-700 mb-1.5">Kata Sandi</label>
                    <div class="relative">
                        <Lock class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                        <input
                            id="password"
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            required
                            autocomplete="current-password"
                            class="w-full pl-9 pr-11 py-2.5 text-sm rounded-lg border border-slate-300 bg-white focus:border-teal-700 focus:ring-2 focus:ring-teal-700/20 outline-none"
                            placeholder="••••••••"
                        >
                        <button
                            type="button"
                            class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
                            :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                            :aria-pressed="showPassword"
                            @click="showPassword = !showPassword"
                        >
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.75"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="w-4 h-4 transition-transform duration-200 ease-out"
                                :class="showPassword ? 'scale-105 text-teal-700' : 'scale-100'"
                                aria-hidden="true"
                            >
                                <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0" />
                                <circle
                                    cx="12"
                                    cy="12"
                                    r="3"
                                    class="origin-center transition-transform duration-200 ease-out"
                                    :class="showPassword ? 'scale-100' : 'scale-75'"
                                />
                                <line
                                    x1="3"
                                    y1="3"
                                    x2="21"
                                    y2="21"
                                    stroke-dasharray="26"
                                    :stroke-dashoffset="showPassword ? 26 : 0"
                                    class="transition-[stroke-dashoffset,opacity] duration-200 ease-out"
                                    :class="showPassword ? 'opacity-0' : 'opacity-100'"
                                />
                            </svg>
                        </button>
                    </div>
                </div>

                <label class="flex items-center gap-2 text-xs text-slate-600 select-none">
                    <input v-model="form.remember" type="checkbox" class="rounded border-slate-300 text-teal-700 focus:ring-teal-700">
                    <span>Ingat sesi saya</span>
                </label>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg bg-teal-700 hover:bg-teal-800 disabled:opacity-60 text-white text-sm font-semibold transition-colors"
                >
                    <span>{{ form.processing ? 'Memproses...' : 'Masuk ke Dashboard' }}</span>
                    <ArrowRight class="w-4 h-4" />
                </button>
            </form>

            <!-- Quick Role Switcher for Demo / Internal Testing -->
            <div class="mt-6 pt-5 border-t border-slate-200">
                <p class="text-xs font-medium text-slate-500 mb-2.5">Pilih cepat akun demo (password: <span class="num text-slate-700">password</span>):</p>
                <div class="grid grid-cols-3 gap-2">
                    <button
                        type="button"
                        class="px-2.5 py-2 rounded-lg border border-slate-200 hover:border-teal-600 hover:bg-teal-50/50 text-left transition-colors"
                        @click="fillDemo('owner')"
                    >
                        <div class="text-xs font-semibold text-slate-900">owner</div>
                        <div class="text-[11px] text-slate-500">Semua cabang</div>
                    </button>
                    <button
                        type="button"
                        class="px-2.5 py-2 rounded-lg border border-slate-200 hover:border-teal-600 hover:bg-teal-50/50 text-left transition-colors"
                        @click="fillDemo('admin_ts')"
                    >
                        <div class="text-xs font-semibold text-slate-900">admin_ts</div>
                        <div class="text-[11px] text-slate-500">Tanjung Sari</div>
                    </button>
                    <button
                        type="button"
                        class="px-2.5 py-2 rounded-lg border border-slate-200 hover:border-teal-600 hover:bg-teal-50/50 text-left transition-colors"
                        @click="fillDemo('admin_kakap')"
                    >
                        <div class="text-xs font-semibold text-slate-900">admin_kakap</div>
                        <div class="text-[11px] text-slate-500">Kakap</div>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
