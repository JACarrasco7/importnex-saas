<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { PencilIcon, BuildingOfficeIcon, CreditCardIcon, CalendarIcon, ExclamationTriangleIcon, KeyIcon, TrashIcon, UserPlusIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import FormSection from '@/Components/FormSection.vue';
import { useFormat } from '@/Composables/useFormat';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    organization: Object,
    members: { type: Array, default: () => [] },
    canManage: { type: Boolean, default: false },
    usersLimit: { type: Number, default: null },
    apiTokenActive: { type: Boolean, default: false },
    newApiToken: { type: String, default: null },
    companyMissing: { type: Array, default: () => [] },
});

const memberForm = useForm({ name: '', email: '', role: 'operator' });
const addMember = () => memberForm.post(route('organization.members.store', props.organization.id), {
    preserveScroll: true,
    onSuccess: () => memberForm.reset(),
});
const removeMember = (member) => {
    if (!window.confirm(`¿Quitar el acceso a ${member.name}?`)) return;
    router.delete(route('organization.members.destroy', [props.organization.id, member.id]), { preserveScroll: true });
};
const issueToken = () => {
    if (props.apiTokenActive && !window.confirm('El token actual dejará de funcionar. ¿Generar uno nuevo?')) return;
    router.post(route('organization.api-token.issue', props.organization.id), {}, { preserveScroll: true });
};
const revokeToken = () => {
    if (!window.confirm('¿Revocar el token de API?')) return;
    router.delete(route('organization.api-token.revoke', props.organization.id), { preserveScroll: true });
};
const inputClass = 'block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-estoril-500 focus:ring-estoril-500';
const roleLabel = (role) => (role === 'owner' ? 'Propietario' : 'Operador');

const { date } = useFormat();
</script>

<template>
    <Head :title="t('organization.title')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ t('organization.title') }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader :title="organization.name" :subtitle="t('organization.details')">
                    <template #actions>
                        <Link :href="route('organization.edit', organization.id)" class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-estoril-500">
                            <PencilIcon class="h-4 w-4" />
                            {{ t('app.edit') }}
                        </Link>
                    </template>
                </PageHeader>

                <div v-if="companyMissing.length" class="flex items-start gap-3 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-200">
                    <ExclamationTriangleIcon class="mt-0.5 h-5 w-5 shrink-0" />
                    <p>
                        Faltan datos de tu empresa para contratos y aviso legal: <strong>{{ companyMissing.join(', ') }}</strong>.
                        <Link :href="route('organization.edit', organization.id)" class="font-semibold underline">Completarlos</Link>
                    </p>
                </div>

                <FormSection title="Datos">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-estoril-50">
                                <BuildingOfficeIcon class="h-5 w-5 text-estoril-600" />
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('organization.name') }}</dt>
                                <dd class="font-medium text-gray-900">{{ organization.name }}</dd>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-estoril-50">
                                <CreditCardIcon class="h-5 w-5 text-estoril-600" />
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('organization.plan') }}</dt>
                                <dd class="font-medium text-gray-900">{{ organization.plan || 'starter' }}</dd>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50">
                                <CalendarIcon class="h-5 w-5 text-amber-600" />
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('organization.trial_ends') }}</dt>
                                <dd class="font-medium text-gray-900">{{ organization.trial_ends_at ? date(organization.trial_ends_at) : t('organization.no_trial') }}</dd>
                            </div>
                        </div>
                    </div>
                </FormSection>

                <FormSection title="Equipo" :subtitle="usersLimit ? `${members.length} de ${usersLimit} usuarios de tu plan` : null">
                    <ul class="divide-y divide-gray-100">
                        <li v-for="member in members" :key="member.id" class="flex items-center justify-between gap-3 py-3">
                            <div class="min-w-0">
                                <p class="truncate font-medium text-gray-900">{{ member.name }}</p>
                                <p class="truncate text-sm text-gray-500">{{ member.email }} · {{ roleLabel(member.role) }}</p>
                            </div>
                            <button v-if="canManage && member.id !== $page.props.auth.user.id" type="button" class="rounded-lg p-2 text-gray-400 hover:bg-rose-50 hover:text-rose-600" :aria-label="`Quitar a ${member.name}`" @click="removeMember(member)">
                                <TrashIcon class="h-4 w-4" />
                            </button>
                        </li>
                    </ul>
                    <form v-if="canManage" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-4" @submit.prevent="addMember">
                        <div class="sm:col-span-1">
                            <input v-model="memberForm.name" type="text" required placeholder="Nombre" :class="inputClass" />
                            <p v-if="memberForm.errors.name" class="mt-1 text-xs text-red-600">{{ memberForm.errors.name }}</p>
                        </div>
                        <div class="sm:col-span-1">
                            <input v-model="memberForm.email" type="email" required placeholder="Email" :class="inputClass" />
                            <p v-if="memberForm.errors.email" class="mt-1 text-xs text-red-600">{{ memberForm.errors.email }}</p>
                        </div>
                        <select v-model="memberForm.role" :class="inputClass">
                            <option value="operator">Operador</option>
                            <option value="owner">Propietario</option>
                        </select>
                        <button type="submit" :disabled="memberForm.processing" class="inline-flex items-center justify-center gap-2 rounded-lg bg-estoril-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-estoril-500 disabled:opacity-50">
                            <UserPlusIcon class="h-4 w-4" />
                            Invitar
                        </button>
                    </form>
                    <p v-if="canManage" class="mt-2 text-xs text-gray-500">Le llegará un email para crear su contraseña.</p>
                </FormSection>

                <FormSection v-if="canManage" title="API" subtitle="Token para conectar herramientas externas (importación de valoraciones, mercado, cierres). Solo da acceso a los datos de tu empresa.">
                    <div v-if="newApiToken" class="mb-3 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-900 ring-1 ring-emerald-200">
                        <p class="font-semibold">Copia tu token ahora (no se volverá a mostrar):</p>
                        <code class="mt-1 block break-all font-mono text-xs">{{ newApiToken }}</code>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="inline-flex items-center gap-2 text-sm" :class="apiTokenActive ? 'text-emerald-700' : 'text-gray-500'">
                            <KeyIcon class="h-4 w-4" />
                            {{ apiTokenActive ? 'Token activo' : 'Sin token' }}
                        </span>
                        <button type="button" class="rounded-lg bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50" @click="issueToken">
                            {{ apiTokenActive ? 'Generar uno nuevo' : 'Generar token' }}
                        </button>
                        <button v-if="apiTokenActive" type="button" class="text-sm font-semibold text-rose-600 hover:text-rose-700" @click="revokeToken">Revocar</button>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">Envíalo en la cabecera <code>X-Import-Token</code>.</p>
                </FormSection>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
