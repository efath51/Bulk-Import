<script setup lang="ts">
import DynamicFormFields from '@/components/DynamicFormFields.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref } from 'vue';

const props = defineProps<{
    total_items: number;
    current_index: number;
    item: Record<string, string>;
    imported_count?: number;
}>();

const index = ref(props.current_index);
const imported_count = ref(props.imported_count || 0);

const form = useForm({
    ...props.item,
    image: null as File | null,
});
 
const fields = computed(() => Object.keys(props.item));

const onImageChange = (e: Event) => {
    form.image = (e.target as HTMLInputElement).files?.[0] ?? null;
};

const loadItem = async (nextIndex: number) => {
    if (nextIndex >= props.total_items) {
        router.visit(route('dashboard'));
        return;
    }

    const { data } = await axios.get(route('bulk-import.item', { index: nextIndex }));

    form.defaults({ ...data.item, image: null }).reset();
    index.value = data.index;
    imported_count.value=data.imported_count;
};

const save = () => {
    form.post(route('bulk-import.save-item'), {
        onSuccess: async (page) => {
            await loadItem(page.props.next_index as number);
        },
    });
};

const skip = async () => {
    await loadItem(index.value + 1);
};
const previous = async () => {
    const key = index.value === 0 ? 0 : index.value - 1;
    await loadItem(key);
};

const cancel = () => {
    if (confirm('Are you sure you want to cancel? All progress will be lost.')) {
        router.post(route('bulk-import.cancel'));
    }
};

const breadcrumbs = [{ title: 'Bulk Import', href: '/bulk-import' }];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-4/6 px-4 py-10">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-lg font-semibold">Bulk Import</h1>
                <button @click="cancel" class="text-sm text-gray-400 transition hover:text-red-500">Cancel Import</button>
            </div>

            <!-- Progress -->
            <div class="mb-6">
                <div class="mb-2 flex justify-between text-sm text-gray-500">
                    <span>Item {{ index + 1 }} of {{ total_items }}</span>
                    <span>{{ Math.round(((index + 1) / total_items) * 100) }}%</span>
                </div>
                <div class="h-1.5 rounded-full bg-gray-100">
                    <div
                        class="h-1.5 rounded-full bg-blue-600 transition-all duration-500"
                        :style="{ width: ((index + 1) / total_items) * 100 + '%' }"
                    />
                </div>
            </div>

            <!-- Import Status Tracker -->
            <div class="mb-6 flex items-center justify-between rounded-2xl bg-gray-900 px-5 py-3">
                <div class="flex items-center gap-3">
                    <div class="rounded-full bg-emerald-500/10 px-3 py-1 text-sm font-medium text-emerald-400">✓ Successfully Imported</div>
                    <span class="text-lg font-semibold text-white">
                        {{ imported_count }}
                        <span class="text-base font-normal text-gray-500">of {{ total_items }}</span>
                    </span>
                </div>

                <div class="text-sm text-gray-400">{{ Math.round((imported_count / total_items) * 100) }}% Done</div>
            </div>

            <!-- Form -->
            <div class="rounded-2xl border p-8">
                <DynamicFormFields :form="form" :fields="fields" @image-change="onImageChange" />

                <!-- Actions -->
                <div class="mt-8 grid grid-cols-3 gap-3">
                    <button
                        @click="previous"
                        :disabled="index === 0 || form.processing"
                        class="rounded-2xl border py-4 font-semibold text-gray-700 transition hover:bg-gray-50 disabled:opacity-50"
                    >
                        ← Previous
                    </button>

                    <button
                        @click="skip"
                        :disabled="form.processing"
                        class="rounded-2xl border py-4 font-semibold text-gray-700 transition hover:bg-gray-50 disabled:opacity-50"
                    >
                        Skip →
                    </button>

                    <button
                        @click="save"
                        :disabled="form.processing"
                        class="rounded-2xl bg-blue-600 py-4 font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60"
                    >
                        {{ form.processing ? 'Saving...' : 'Save & Next →' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
