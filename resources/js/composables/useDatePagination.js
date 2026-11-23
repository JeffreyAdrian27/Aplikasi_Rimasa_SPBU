import { computed, ref, watch } from 'vue';
import { formatDate } from '@/utils/format';

export function useDatePagination(itemsGetter, dateKey, datesPerPage = 15) {
    const currentPage = ref(1);

    const uniqueDates = computed(() => {
        const items = itemsGetter() || [];
        const seen = new Set();
        const dates = [];
        for (const item of items) {
            const d = item?.[dateKey];
            if (d && !seen.has(d)) {
                seen.add(d);
                dates.push(d);
            }
        }
        return dates;
    });

    const totalDates = computed(() => uniqueDates.value.length);
    const totalPages = computed(() => Math.max(1, Math.ceil(totalDates.value / datesPerPage)));

    watch(totalPages, (maxPage) => {
        if (currentPage.value > maxPage) {
            currentPage.value = maxPage;
        }
    });

    const pageDates = computed(() => {
        const start = (currentPage.value - 1) * datesPerPage;
        return uniqueDates.value.slice(start, start + datesPerPage);
    });

    const paginatedItems = computed(() => {
        const items = itemsGetter() || [];
        if (pageDates.value.length === 0) return [];
        const allowed = new Set(pageDates.value);
        return items.filter((item) => allowed.has(item?.[dateKey]));
    });

    const dateRangeLabel = computed(() => {
        if (pageDates.value.length === 0) return 'Belum ada tanggal';
        const first = formatDate(pageDates.value[0]);
        const last = formatDate(pageDates.value[pageDates.value.length - 1]);
        return first === last ? first : `${first} – ${last}`;
    });

    const goToPage = (page) => {
        const clamped = Math.min(Math.max(1, page), totalPages.value);
        currentPage.value = clamped;
    };

    return {
        currentPage,
        totalDates,
        totalPages,
        pageDates,
        paginatedItems,
        dateRangeLabel,
        datesPerPage,
        goToPage,
    };
}
