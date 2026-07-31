import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

// Navigation labels - using translation keys
const navLabels = {
    overview: t('nav.overview'),
    dashboard: t('nav.dashboard'),
    inventory: t('nav.inventory'),
    cars: t('nav.cars'),
    kanban: t('nav.kanban'),
    map: t('nav.map'),
    finance: t('nav.finance'),
    trips: t('nav.trips'),
    crm: t('nav.crm'),
    clients: t('nav.clients'),
    contacts: t('nav.contacts'),
    templates: t('nav.templates'),
    alerts: t('nav.alerts'),
    account: t('nav.account'),
    plan: t('nav.plan'),
    billing: t('nav.billing'),
    organization: t('nav.organization'),
    profile: t('nav.profile'),
};

export { navLabels };
