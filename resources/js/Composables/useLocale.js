import { ref, computed } from 'vue';
import dayjs from 'dayjs';
import 'dayjs/locale/en';
import 'dayjs/locale/es';
import localizedFormat from 'dayjs/plugin/localizedFormat';
import relativeTime from 'dayjs/plugin/relativeTime';
import advancedFormat from 'dayjs/plugin/advancedFormat';

dayjs.extend(localizedFormat);
dayjs.extend(relativeTime);
dayjs.extend(advancedFormat);

const supportedLocales = ['en', 'es'];

export function useLocale() {
    const currentLocale = ref('en');

    const isEnglish = computed(() => currentLocale.value === 'en');
    const isSpanish = computed(() => currentLocale.value === 'es');

    const dayjsLocale = computed(() => {
        const localeMap = {
            en: 'en',
            es: 'es',
        };
        return localeMap[currentLocale.value] || 'en';
    });

    function setLocale(locale) {
        if (!supportedLocales.includes(locale)) {
            console.warn(`Locale ${locale} not supported`);
            return;
        }

        currentLocale.value = locale;
        dayjs.locale(locale);

        // Persist in localStorage for guest users
        localStorage.setItem('locale', locale);
    }

    function getFormattedDate(value, format = 'L') {
        if (!value) return '—';
        return dayjs(value).locale(dayjsLocale.value).format(format);
    }

    function getFormattedDateTime(value) {
        if (!value) return '—';
        return dayjs(value).locale(dayjsLocale.value).format('L LT');
    }

    function getFromNow(value) {
        if (!value) return '—';
        return dayjs(value).locale(dayjsLocale.value).fromNow();
    }

    return {
        locale: currentLocale,
        isEnglish,
        isSpanish,
        supportedLocales,
        setLocale,
        getFormattedDate,
        getFormattedDateTime,
        getFromNow,
    };
}
