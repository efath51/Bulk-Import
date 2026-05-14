<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const form = useForm({ dataset: null as File | null });
const selectedFile = ref<File | null>(null);

const allowedTypes = ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

const onFileChange = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (!file || !allowedTypes.includes(file.type)) {
        alert('Only PDF or DOCX files are accepted.');
        return;
    }
    selectedFile.value = file;
    form.dataset = file;
};

const fileSizeLabel = computed(() => (selectedFile.value ? (selectedFile.value.size / 1024 / 1024).toFixed(2) + ' MB' : ''));

const submit = () => form.post(route('bulk-import.upload'));

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Bulk Import',
        href: '/bulk-import',
    },
];
</script>

<template>
    <Head title="Bulk Import" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto mt-20 max-w-lg px-4">
            <h1 class="mb-8 text-center text-3xl font-bold">Import Items from File</h1>

            <div
                class="rounded-3xl border-2 border-dashed border-gray-300 p-12 text-center transition-colors hover:border-blue-400"
                @dragover.prevent
                @drop.prevent="
                    (e) => {
                        form.dataset = e.dataTransfer.files[0];
                        selectedFile = e.dataTransfer.files[0];
                    }
                "
            >
                <input id="file-input" type="file" accept=".pdf,.docx" class="hidden" @change="onFileChange" />
                <label for="file-input" class="block cursor-pointer">
                    <div class="mb-3 text-4xl">📄</div>
                    <p class="font-medium text-gray-700">
                        {{ selectedFile ? selectedFile.name : 'Click or drag a file here' }}
                    </p>
                    <p v-if="fileSizeLabel" class="mt-1 text-sm text-gray-400">{{ fileSizeLabel }}</p>
                    <p class="mt-2 text-sm text-gray-400">PDF or DOCX · Max 10 MB</p>
                </label>
            </div>

            <p v-if="form.errors.dataset" class="mt-3 text-center text-sm text-red-500">
                {{ form.errors.dataset }}
            </p>

            <button
                @click="submit"
                :disabled="form.processing || !form.dataset"
                class="mt-6 w-full rounded-2xl bg-black py-5 text-lg font-medium text-white transition-opacity disabled:opacity-40"
            >
                {{ form.processing ? 'Processing…' : 'Upload & Start Wizard' }}
            </button>
        </div>
    </AppLayout>
</template>
