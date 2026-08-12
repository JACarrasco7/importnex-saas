import dayjs from 'dayjs';
import 'dayjs/locale/en';
import 'dayjs/locale/es';
import localizedFormat from 'dayjs/plugin/localizedFormat';
import relativeTime from 'dayjs/plugin/relativeTime';
import advancedFormat from 'dayjs/plugin/advancedFormat';
import { usePage } from '@inertiajs/vue3';

dayjs.extend(localizedFormat);
dayjs.extend(relativeTime);
dayjs.extend(advancedFormat);

// Mapa de locale -> codigo BCP-47 para Intl
const LOCALE_TO_BCP47 = {
    en: 'en-US',
    es: 'es-ES',
};

// Mapa de currency -> locale por defecto (para formato de fecha si se requiere)
const CURRENCY_DEFAULTS = {
    EUR: { locale: 'es-ES' },
    USD: { locale: 'en-US' },
    GBP: { locale: 'en-GB' },
};

function getFormatOpts() {
    let currency = 'EUR';
    let locale = 'es';
    try {
        const page = usePage();
        const f = page?.props?.formatting;
        if (f) {
            currency = f.currency || currency;
            locale = f.locale || locale;
        }
    } catch (e) {
        // usePage fuera de contexto: usar defaults
    }
    const bcp = LOCALE_TO_BCP47[locale] || CURRENCY_DEFAULTS[currency]?.locale || 'en-US';
    return { currency, bcp };
}

function makeCurrencyFormatter(currency, bcp) {
    try {
        return new Intl.NumberFormat(bcp, {
            style: 'currency',
            currency,
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        });
    } catch (e) {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'EUR' });
    }
}

function makeNumberFormatter(bcp) {
    return new Intl.NumberFormat(bcp, {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    });
}

function makePercentFormatter(bcp) {
    return new Intl.NumberFormat(bcp, {
        style: 'percent',
        minimumFractionDigits: 0,
        maximumFractionDigits: 1,
    });
}

export function useFormat() {
    const { currency, bcp } = getFormatOpts();
    const currencyFormatter = makeCurrencyFormatter(currency, bcp);
    const numberFormatter = makeNumberFormatter(bcp);
    const percentFormatter = makePercentFormatter(bcp);
    const dayjsLocale = (bcp || 'es-ES').startsWith('es') ? 'es' : 'en';

    return {
        currency: (value) => {
            const num = Number(value) || 0;
            return currencyFormatter.format(num);
        },
        // Devuelve la currency configurada (ej 'EUR') para etiquetas tipo '29 EUR/mes'.
        currencyCode: () => currency,
        // Traduce un enum de estado (Located, Valuing, ...) a label legible.
        // Recibe `t` del composable de traducciones porque useFormat
        // no puede llamar a useTranslations() fuera de un setup().
        // Devuelve el enum tal cual si no hay clave i18n, para no romper nada.
        statusLabel: (t, status) => {
            if (!status) return '—';
            if (!t) return status;
            // Normalizar a formato canónico (Located, In_transit, Pending review)
            const canonical = status.charAt(0).toUpperCase() + status.slice(1).toLowerCase();
            const key = `cars.status.${canonical}`;
            const label = t(key);
            if (label === key) {
                // Fallback: probar con clave original
                const keyOrig = `cars.status.${status}`;
                const labelOrig = t(keyOrig);
                if (labelOrig !== keyOrig) return labelOrig;
                return status;
            }
            return label;
        },
        number: (value, decimals = 0) => {
            const num = Number(value) || 0;
            return new Intl.NumberFormat(bcp, {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals,
            }).format(num);
        },
        percent: (value) => percentFormatter.format(value || 0),
        date: (value, format = 'L') => {
            if (!value) return '—';
            return dayjs(value).locale(dayjsLocale).format(format);
        },
        datetime: (value) => {
            if (!value) return '—';
            return dayjs(value).locale(dayjsLocale).format('L LT');
        },
        fromNow: (value) => {
            if (!value) return '—';
            return dayjs(value).locale(dayjsLocale).fromNow();
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
        verdictVariant: (verdict) => {
            const map = {
                positive: 'green', good: 'green', excellent: 'green',
                neutral: 'amber', fair: 'amber', normal: 'amber',
                negative: 'red', poor: 'red', bad: 'red', fail: 'red',
            };
            return map[verdict?.toLowerCase()] || 'gray';
        },
    };
}
