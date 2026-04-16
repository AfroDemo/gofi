const decimal = new Intl.NumberFormat('en-GB');

const dateTime = new Intl.DateTimeFormat('en-GB', {
    day: '2-digit',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
});

export function formatMoney(value: number, currency?: string | null) {
    if (!currency) {
        return decimal.format(value);
    }

    return new Intl.NumberFormat('en-TZ', {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(value);
}

export function formatDateTime(value?: string | null) {
    if (!value) {
        return 'Not available';
    }

    return dateTime.format(new Date(value));
}

export function formatMinutes(value?: number | null) {
    if (!value) {
        return 'Flexible';
    }

    if (value < 60) {
        return `${value} min`;
    }

    const hours = value / 60;

    if (Number.isInteger(hours)) {
        return `${hours} hr`;
    }

    return `${hours.toFixed(1)} hr`;
}

export function formatDataLimit(value?: number | null) {
    if (!value) {
        return 'No data cap';
    }

    if (value >= 1024) {
        return `${(value / 1024).toFixed(value % 1024 === 0 ? 0 : 1)} GB`;
    }

    return `${value} MB`;
}

export function formatSpeed(value?: number | null) {
    if (!value) {
        return 'Uncapped';
    }

    if (value >= 1024) {
        return `${(value / 1024).toFixed(value % 1024 === 0 ? 0 : 1)} Mbps`;
    }

    return `${value} Kbps`;
}
