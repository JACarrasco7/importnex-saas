import dayjs from 'dayjs';
import 'dayjs/locale/en';
import 'dayjs/locale/de';
import 'dayjs/locale/es';
import localizedFormat from 'dayjs/plugin/localizedFormat';
import relativeTime from 'dayjs/plugin/relativeTime';
import advancedFormat from 'dayjs/plugin/advancedFormat';

dayjs.extend(localizedFormat);
dayjs.extend(relativeTime);
dayjs.extend(advancedFormat);

const LOCALE = 'de';
const CURRENCY_LOCALE = 'de-DE';
const CURRENCY = 'EUR';

const currencyFormatter = new Intl.NumberFormat(CURRENCY_LOCALE, {
    style: 'currency',
    currency: CURRENCY,
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
});

const numberFormatter = new Intl.NumberFormat(CURRENCY_LOCALE, {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
});

const percentFormatter = new Intl.NumberFormat(CURRENCY_LOCALE, {
    style: 'percent',
    minimumFractionDigits: 0,
    maximumFractionDigits: 1,
});

export function useFormat() {
    return {
        currency: (value) => {
            const num = Number(value) || 0;
            return currencyFormatter.format(num);
        },
        number: (value, decimals = 0) => {
            const num = Number(value) || 0;
            return new Intl.NumberFormat(CURRENCY_LOCALE, {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals,
            }).format(num);
        },
        percent: (value) => percentFormatter.format(value || 0),
        date: (value, format = 'L') => {
            if (!value) return '—';
            return dayjs(value).locale(LOCALE).format(format);
        },
        datetime: (value) => {
            if (!value) return '—';
            return dayjs(value).locale(LOCALE).format('L LT');
        },
        fromNow: (value) => {
            if (!value) return '—';
            return dayjs(value).locale(LOCALE).fromNow();
        },
        age: (value) => {
            if (!value) return '—';
            return dayjs().diff(dayjs(value), 'year');
        },
        truncate: (value, length = 50) => {
            if (!value) return '';
            return value.length > length ? `${value.substring(0, length)}…` : value;
        },
        initials: (name) => {
            if (!name) return '?';
            return name
                .split(' ')
                .filter(Boolean)
                .slice(0, 2)
                .map((s) => s[0].toUpperCase())
                .join('');
        },
        statusVariant: (status) => {
            const map = {
                active: 'green', purchased: 'indigo', delivered: 'emerald',
                pending: 'amber', cancelled: 'rose', draft: 'gray',
                located: 'gray', valuing: 'amber', offered: 'sky',
                reserved: 'purple', in_transit: 'blue', processing: 'indigo',
                new: 'blue', briefing: 'sky', quote_sent: 'amber',
                negotiating: 'purple', order_signed: 'indigo', in_process: 'blue',
                resolved: 'emerald', closed: 'gray',
            };
            return map[status?.toLowerCase()] || 'gray';
        },
        trafficLightVariant: (color) => {
            const map = { green: 'green', amber: 'amber', red: 'red', neutral: 'gray' };
            return map[color] || 'gray';
        },
    };
}
