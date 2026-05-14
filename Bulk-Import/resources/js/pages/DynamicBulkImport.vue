<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import DynamicFormFields from '@/Components/DynamicFormFields.vue';
import { router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, onMounted } from 'vue';

const props = defineProps<{
    total_items: number;
    current_index: number;
    item: Record<string, any>;
    fields: string[];           // e.g. ["Name", "Slug", "Status", ...]
}>();

const index = ref(props.current_index);

// Initialize form with dynamic item data
const form = useForm({
    ...props.item,                    // Spread all fields from backend
    image: null as File | null,       // Extra for file upload
});

const onImageChange = (e: Event) => {
    form.image = (e.target as HTMLInputElement).files?.[0] ?? null;
};

const loadItem = async (nextIndex: number) => {
    if (nextIndex >= props.total_items) {
        router.visit(route('dashboard'));
        return;
    }

    try {
        const { data } = await axios.get(route('bulk-import.item', { index: nextIndex }));

        // Reset form with new item data
        form.defaults({
            ...data,
            image: null,
        }).reset();

        index.value = nextIndex;
    } catch (error) {
        console.error('Failed to load item', error);
    }
};

const save = () => {
    form.post(route('bulk-import.save-item'), {
        onSuccess: (page) => {
            const nextIndex = page.props.next_index as number;
            loadItem(nextIndex);
        },
    });
};

const skip = () => loadItem(index.value + 1);

const previous = () => loadItem(Math.max(0, index.value - 1));
</script>

<template>
    <AppLayout title="Bulk Import">
        <div class="mx-auto max-w-7xl px-4 py-10">
            <!-- Progress -->
            <div class="mb-8">
                <div class="mb-2 flex justify-between text-sm text-gray-400">
                    <span>Item {{ index + 1 }} of {{ total_items }}</span>
                    <span>{{ Math.round(((index + 1) / total_items) * 100) }}% Complete</span>
                </div>
                <div class="h-2.5 rounded-full bg-gray-800">
                    <div
                        class="h-2.5 rounded-full bg-blue-600 transition-all duration-500"
                        :style="{ width: ((index + 1) / total_items) * 100 + '%' }"
                    />
                </div>
            </div>

            <div class="rounded-3xl border border-gray-700 bg-black p-8 shadow-2xl">
                <DynamicFormFields
                    :form="form"
                    :fields="fields"
                    @image-change="onImageChange"
                />

                <!-- Action Buttons -->
                <div class="mt-10 grid grid-cols-3 gap-4">
                    <button
                        @click="previous"
                        :disabled="index === 0 || form.processing"
                        class="rounded-2xl border border-gray-700 py-4 font-semibold text-gray-300 transition hover:bg-gray-900 disabled:opacity-50"
                    >
                        ← Previous
                    </button>

                    <button
                        @click="skip"
                        :disabled="form.processing"
                        class="rounded-2xl border border-gray-700 py-4 font-semibold text-gray-300 transition hover:bg-gray-900 disabled:opacity-50"
                    >
                        Skip →
                    </button>

                    <button
                        @click="save"
                        :disabled="form.processing"
                        class="rounded-2xl bg-blue-600 py-4 font-semibold text-white transition hover:bg-blue-700 disabled:opacity-70"
                    >
                        {{ form.processing ? 'Saving...' : 'Save & Next →' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
