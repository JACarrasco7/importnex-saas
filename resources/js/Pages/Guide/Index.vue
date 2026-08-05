<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useFormat } from '@/Composables/useFormat';

const { t } = useFormat();
</script>

<template>
    <Head :title="t('cars.guide_title')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ t('cars.guide_title') }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader :title="t('cars.guide_title')" :subtitle="t('cars.guide_subtitle')" />

                <div class="prose max-w-none text-gray-700">
                    <h2>{{ t('cars.guide_main_title') }}</h2>

                    <p>{{ t('cars.guide_intro') }}</p>

                    <h3>{{ t('cars.guide_idea_title') }}</h3>
                    <p v-html="t('cars.guide_idea_body')"></p>

                    <h2>{{ t('cars.guide_section1_title') }}</h2>

                    <p>{{ t('cars.guide_section1_intro') }}</p>

                    <p v-html="t('cars.guide_flow1')"></p>

                    <p v-html="t('cars.guide_flow2')"></p>

                    <p>{{ t('cars.guide_section1_outro') }}</p>

                    <h2>{{ t('cars.guide_section2_title') }}</h2>

                    <h3>{{ t('cars.guide_step1_title') }}</h3>

                    <p v-html="t('cars.guide_step1_desc')"></p>

                    <p>{{ t('cars.guide_step1_action') }}</p>

                    <h3>{{ t('cars.guide_step2_title') }}</h3>

                    <p>{{ t('cars.guide_step2_desc') }}</p>

                    <ul>
                        <li v-html="t('cars.guide_step2_li1')"></li>
                        <li v-html="t('cars.guide_step2_li2')"></li>
                    </ul>

                    <h3>{{ t('cars.guide_step3_title') }}</h3>

                    <p>{{ t('cars.guide_step3_desc') }}</p>

                    <p>{{ t('cars.guide_step3_cache') }}</p>

                    <h3>{{ t('cars.guide_step4_title') }}</h3>

                    <p>{{ t('cars.guide_step4_intro') }}</p>

                    <ul>
                        <li>{{ t('cars.guide_step4_li1') }}</li>
                        <li>{{ t('cars.guide_step4_li2') }}</li>
                        <li>{{ t('cars.guide_step4_li3') }}</li>
                        <li>{{ t('cars.guide_step4_li4') }}</li>
                        <li>{{ t('cars.guide_step4_li5') }}</li>
                        <li>{{ t('cars.guide_step4_li6') }}</li>
                    </ul>

                    <p v-html="t('cars.guide_step4_ok')"></p>

                    <h3>{{ t('cars.guide_step5_title') }}</h3>

                    <p>{{ t('cars.guide_step5_intro') }}</p>

                    <ul>
                        <li v-html="t('cars.guide_step5_li1')"></li>
                        <li v-html="t('cars.guide_step5_li2')"></li>
                        <li v-html="t('cars.guide_step5_li3')"></li>
                        <li>{{ t('cars.guide_step5_li4') }}</li>
                    </ul>

                    <p>{{ t('cars.guide_step5_outro') }}</p>

                    <h3>{{ t('cars.guide_step6_title') }}</h3>

                    <ol>
                        <li v-html="t('cars.guide_step6_li1')"></li>
                        <li>{{ t('cars.guide_step6_li2') }}</li>
                        <li v-html="t('cars.guide_step6_li3')"></li>
                        <li>{{ t('cars.guide_step6_li4') }}</li>
                    </ol>

                    <p>{{ t('cars.guide_step6_outro') }}</p>

                    <h3>{{ t('cars.guide_step7_title') }}</h3>

                    <p>{{ t('cars.guide_step7_desc') }}</p>

                    <h2>{{ t('cars.guide_section3_title') }}</h2>

                    <h3>{{ t('cars.guide_flow2_step1_title') }}</h3>

                    <p>{{ t('cars.guide_flow2_step1_desc') }}</p>

                    <ul>
                        <li v-html="t('cars.guide_flow2_step1_li1')"></li>
                        <li v-html="t('cars.guide_flow2_step1_li2')"></li>
                        <li v-html="t('cars.guide_flow2_step1_li3')"></li>
                        <li v-html="t('cars.guide_flow2_step1_li4')"></li>
                    </ul>

                    <h3>{{ t('cars.guide_flow2_step2_title') }}</h3>

                    <p>{{ t('cars.guide_flow2_step2_desc') }}</p>

                    <h3>{{ t('cars.guide_flow2_step3_title') }}</h3>

                    <p>{{ t('cars.guide_flow2_step3_desc') }}</p>

                    <h3>{{ t('cars.guide_flow2_step4_title') }}</h3>

                    <p>{{ t('cars.guide_flow2_step4_desc') }}</p>

                    <h3>{{ t('cars.guide_flow2_step5_title') }}</h3>

                    <p>{{ t('cars.guide_flow2_step5_desc') }}</p>

                    <h3>{{ t('cars.guide_flow2_step6_title') }}</h3>

                    <p>{{ t('cars.guide_flow2_step6_desc') }}</p>

                    <h3>{{ t('cars.guide_flow2_note_title') }}</h3>

                    <p v-html="t('cars.guide_flow2_note')"></p>

                    <h2>{{ t('cars.guide_section4_title') }}</h2>

                    <p>{{ t('cars.guide_section4_desc') }}</p>

                    <h2>{{ t('cars.guide_faq_title') }}</h2>

                    <p><strong>{{ t('cars.guide_faq1_q') }}</strong></p>
                    <p>{{ t('cars.guide_faq1_a') }}</p>

                    <p><strong>{{ t('cars.guide_faq2_q') }}</strong></p>
                    <p>{{ t('cars.guide_faq2_a') }}</p>

                    <p><strong>{{ t('cars.guide_faq3_q') }}</strong></p>
                    <p>{{ t('cars.guide_faq3_a') }}</p>

                    <p><strong>{{ t('cars.guide_faq4_q') }}</strong></p>
                    <p>{{ t('cars.guide_faq4_a') }}</p>

                    <p><strong>{{ t('cars.guide_faq5_q') }}</strong></p>
                    <p>{{ t('cars.guide_faq5_a') }}</p>

                    <p><strong>{{ t('cars.guide_faq6_q') }}</strong></p>
                    <p>{{ t('cars.guide_faq6_a') }}</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
