<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import LoadingOverlay from '@/Components/LoadingOverlay.vue';
import {
    LayoutDashboard,
    Fuel,
    ReceiptText,
    Wallet,
    TrendingUp,
    Settings,
    KeyRound,
    LogOut,
    Menu,
    X,
    Building2,
    CheckCircle2,
    AlertCircle,
} from 'lucide-vue-next';

const props = defineProps({
    title: { type: String, default: 'Dashboard' },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const flash = computed(() => page.props.flash || {});
const mobileOpen = ref(false);
const isLoading = ref(false);
let loadingTimer = null;
let removeStartListener = null;
let removeFinishListener = null;

onMounted(() => {
    removeStartListener = router.on('start', () => {
        clearTimeout(loadingTimer);
        loadingTimer = setTimeout(() => {
            isLoading.value = true;
        }, 120);
    });

    removeFinishListener = router.on('finish', () => {
        clearTimeout(loadingTimer);
        isLoading.value = false;
    });
});

onUnmounted(() => {
    clearTimeout(loadingTimer);
    if (removeStartListener) removeStartListener();
    if (removeFinishListener) removeFinishListener();
});

const navItems = computed(() => {
    const items = [
        { name: 'Dashboard', route: 'dashboard', icon: LayoutDashboard, match: '/' },
        { name: 'Tebus Pertamina', route: 'tebus.index', icon: Fuel, match: '/tebus' },
        { name: 'Penjualan & Piutang', route: 'penjualan.index', icon: ReceiptText, match: '/penjualan' },
        { name: 'Kas Harian', route: 'kas.index', icon: Wallet, match: '/kas' },
        { name: 'Proyeksi Kas', route: 'proyeksi.index', icon: TrendingUp, match: '/proyeksi' },
    ];
    if (user.value?.role === 'owner') {
        items.push({ name: 'Pengaturan', route: 'settings.index', icon: Settings, match: '/pengaturan' });
    }
    return items;
});

const isActive = (match) => {
    const url = page.url.split('?')[0];
    return match === '/' ? url === '/' : url.startsWith(match);
};

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="min-h-screen flex bg-slate-50 text-slate-900">
        <LoadingOverlay :show="isLoading" label="Memuat data..." />

        <!-- Mobile backdrop -->
        <div
            v-if="mobileOpen"
            class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden"
            @click="mobileOpen = false"
        />

        <!-- Sidebar -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 text-slate-200 flex flex-col transition-transform duration-200 lg:translate-x-0 lg:sticky lg:top-0 lg:h-screen lg:shrink-0',
                mobileOpen ? 'translate-x-0' : '-translate-x-full',
            ]"
        >
            <div class="h-16 px-5 flex items-center justify-between border-b border-slate-800">
                <Link :href="route('dashboard')" class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-teal-600 flex items-center justify-center text-white">
                        <Fuel class="w-4 h-4" />
                    </div>
                    <div class="leading-tight">
                        <div class="text-sm font-bold text-white tracking-tight">Rimasa SPBU</div>
                        <div class="text-[11px] text-slate-400">Keuangan Multi-Produk</div>
                    </div>
                </Link>
                <button
                    type="button"
                    class="lg:hidden p-1.5 rounded-md text-slate-400 hover:text-white hover:bg-slate-800"
                    aria-label="Tutup menu"
                    @click="mobileOpen = false"
                >
                    <X class="w-5 h-5" />
                </button>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto" aria-label="Navigasi utama">
                <Link
                    v-for="item in navItems"
                    :key="item.route"
                    :href="route(item.route)"
                    :class="[
                        'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150',
                        isActive(item.match)
                            ? 'bg-teal-600/20 text-teal-300 ring-1 ring-inset ring-teal-500/30'
                            : 'text-slate-300 hover:bg-slate-800 hover:text-white',
                    ]"
                    @click="mobileOpen = false"
                >
                    <component :is="item.icon" class="w-4 h-4 shrink-0" />
                    <span>{{ item.name }}</span>
                </Link>
            </nav>

            <!-- User footer -->
            <div class="p-4 border-t border-slate-800">
                <div class="flex items-center gap-2.5 mb-3">
                    <div class="w-8 h-8 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center text-xs font-semibold text-teal-300">
                        {{ user?.name?.slice(0, 2)?.toUpperCase() || 'RS' }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-semibold text-white truncate">{{ user?.name }}</p>
                        <p class="text-[11px] text-slate-400 truncate flex items-center gap-1">
                            <Building2 class="w-3 h-3 shrink-0" />
                            <span>{{ user?.role === 'owner' ? 'Owner (Semua Cabang)' : `Cabang ${user?.branch_name}` }}</span>
                        </p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <Link
                        :href="route('account.index')"
                        class="flex items-center justify-center gap-1.5 px-2.5 py-2 rounded-lg text-xs font-medium text-slate-300 bg-slate-800 hover:bg-slate-700 hover:text-white transition-colors"
                        @click="mobileOpen = false"
                    >
                        <KeyRound class="w-3.5 h-3.5" />
                        <span>Sandi</span>
                    </Link>
                    <button
                        type="button"
                        class="flex items-center justify-center gap-1.5 px-2.5 py-2 rounded-lg text-xs font-medium text-slate-300 bg-slate-800 hover:bg-slate-700 hover:text-white transition-colors"
                        @click="logout"
                    >
                        <LogOut class="w-3.5 h-3.5" />
                        <span>Keluar</span>
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0">
            <header class="sticky top-0 z-30 h-16 bg-white border-b border-slate-200 px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3 min-w-0">
                    <button
                        type="button"
                        class="lg:hidden p-2 -ml-2 rounded-lg text-slate-600 hover:bg-slate-100"
                        aria-label="Buka menu"
                        @click="mobileOpen = true"
                    >
                        <Menu class="w-5 h-5" />
                    </button>
                    <h1 class="text-lg font-semibold text-slate-900 truncate">{{ title }}</h1>
                </div>

                <div class="flex items-center gap-3">
                    <span
                        class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium"
                        :class="user?.role === 'owner' ? 'bg-teal-50 text-teal-800 border border-teal-200' : 'bg-blue-50 text-blue-800 border border-blue-200'"
                    >
                        <Building2 class="w-3.5 h-3.5" />
                        {{ user?.role === 'owner' ? 'Owner • Semua Cabang' : `Admin • ${user?.branch_name}` }}
                    </span>
                    <slot name="header-actions" />
                </div>
            </header>

            <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-[1440px] w-full mx-auto space-y-6">
                <!-- Flash Messages -->
                <div
                    v-if="flash.success"
                    role="status"
                    class="flex items-center gap-2.5 px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-sm"
                >
                    <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
                    <span>{{ flash.success }}</span>
                </div>
                <div
                    v-if="flash.error"
                    role="alert"
                    class="flex items-center gap-2.5 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-900 text-sm"
                >
                    <AlertCircle class="w-4 h-4 text-red-600 shrink-0" />
                    <span>{{ flash.error }}</span>
                </div>

                <slot />
            </main>
        </div>
    </div>
</template>
