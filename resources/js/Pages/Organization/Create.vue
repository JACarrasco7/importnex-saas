<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const form = useForm({
    name: '',
});

const submit = () => {
    form.post(route('organization.store'), {
        onFinish: () => form.reset('name'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head :title="t('organization.create')" />

        <div class="max-w-2xl mx-auto">
            <div class="bg-white shadow sm:rounded-lg sm:p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">
                    {{ t('organization.create_your') }}
                </h2>

                <p class="text-sm text-gray-600 mb-6">
                    {{ t('organization.create_desc') }}
                </p>

                <form @submit.prevent="submit">
                    <div class="mb-4">
                        <InputLabel for="name" value="Organization name" />

                        <TextInput
                            id="name"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.name"
                            required
                            autofocus
                            autocomplete="organization"
                        />

                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div class="flex items-center justify-end mt-6">
                        <PrimaryButton :disabled="form.processing">
                            {{ t('organization.create_button') }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </GuestLayout>
</template>
