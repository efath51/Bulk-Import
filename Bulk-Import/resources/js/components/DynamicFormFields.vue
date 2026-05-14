<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    form: ReturnType<typeof useForm>;
    fields: string[];
}>();

const emit = defineEmits<{
    (e: 'image-change', event: Event): void;
}>();

const showImageUpload = ref(false);

const getLabel = (key: string): string => key.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());

const getInputType = (key: string): 'image' | 'textarea' | 'select' | 'number' | 'text' => {
    if (key === 'image') {
        return 'image';
    }
    if (['description', 'content', 'notes', 'bio'].includes(key)) {
        return 'textarea';
    }
    if (['status'].includes(key)) {
        return 'select';
    }
    if (['price', 'amount', 'quantity', 'stock'].includes(key)) {
        return 'number';
    }
    return 'text';
};
</script>

<template>
    <div class="space-y-5">
        <div v-for="field in fields" :key="field">
            <label class="mb-1.5 block text-sm font-medium">{{ getLabel(field) }}</label>

            <!-- Image -->
            <div v-if="getInputType(field) === 'image'">
                <div class="mb-3 flex gap-2">
                    <button
                        type="button"
                        @click="showImageUpload = false"
                        :class="[
                            'flex-1 rounded-xl border py-3 text-sm font-medium transition',
                            !showImageUpload ? 'border-blue-500 bg-blue-600 text-white' : 'border-gray-700 hover:bg-gray-900',
                        ]"
                    >
                        No Image
                    </button>
                    <button
                        type="button"
                        @click="showImageUpload = true"
                        :class="[
                            'flex-1 rounded-xl border py-3 text-sm font-medium transition',
                            showImageUpload ? 'border-blue-500 bg-blue-600 text-white' : 'border-gray-700 hover:bg-gray-900',
                        ]"
                    >
                        Upload Image
                    </button>
                </div>
                <input
                    v-if="showImageUpload"
                    type="file"
                    accept="image/*"
                    @change="emit('image-change', $event)"
                    class="w-full rounded-xl border border-gray-700 bg-black px-4 py-3 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-600 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white"
                />
                <p v-if="form.errors.image" class="mt-1 text-xs text-red-500">{{ form.errors.image }}</p>
            </div>

            <!-- Textarea -->
            <textarea
                v-else-if="getInputType(field) === 'textarea'"
                v-model="form[field]"
                rows="4"
                class="w-full rounded-xl border border-gray-700 bg-black px-4 py-3 focus:border-blue-500 focus:outline-none"
            />

            <!-- Select -->
            <select
                v-else-if="getInputType(field) === 'select'"
                v-model="form[field]"
                class="w-full rounded-xl border border-gray-700 bg-black px-4 py-3 focus:border-blue-500 focus:outline-none"
            >
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="published">Published</option>
                <option value="draft">Draft</option>
            </select>

            <!-- Text / Number -->
            <input
                v-else
                v-model="form[field]"
                :type="getInputType(field)"
                :class="[
                    'w-full rounded-xl border border-gray-700 bg-black px-4 py-3 focus:border-blue-500 focus:outline-none',
                    field === 'slug' && 'font-mono text-sm',
                ]"
            />

            <!-- Field error -->
            <p v-if="form.errors[field] && field !== 'image'" class="mt-1 text-xs text-red-500">
                {{ form.errors[field] }}
            </p>
        </div>
    </div>
</template>
