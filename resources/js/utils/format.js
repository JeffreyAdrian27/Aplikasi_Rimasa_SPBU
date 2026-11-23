export function formatRupiah(value, showSign = false) {
    const num = Number(value || 0);
    const abs = Math.abs(Math.round(num));
    const formatted = new Intl.NumberFormat('id-ID').format(abs);
    if (num < 0) return `-Rp\u00A0${formatted}`;
    if (showSign && num > 0) return `+Rp\u00A0${formatted}`;
    return `Rp\u00A0${formatted}`;
}

export function formatNumber(value) {
    const num = Number(value || 0);
    return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(num);
}

export function formatDate(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr + (dateStr.length === 10 ? 'T00:00:00' : ''));
    if (Number.isNaN(d.getTime())) return dateStr;
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(d);
}

export const MONTHS = [
    { value: 1, label: 'Januari' },
    { value: 2, label: 'Februari' },
    { value: 3, label: 'Maret' },
    { value: 4, label: 'April' },
    { value: 5, label: 'Mei' },
    { value: 6, label: 'Juni' },
    { value: 7, label: 'Juli' },
    { value: 8, label: 'Agustus' },
    { value: 9, label: 'September' },
    { value: 10, label: 'Oktober' },
    { value: 11, label: 'November' },
    { value: 12, label: 'Desember' },
];
