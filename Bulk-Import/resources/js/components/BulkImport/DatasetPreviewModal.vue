<script setup lang="ts">
import axios from 'axios';
import { ref, onMounted, watch } from 'vue';

const props = defineProps<{
    filename: string | null;
    show: boolean;
}>();

const emit = defineEmits(['close']);

const items = ref<Record<string, any>[]>([]);
const rawData = ref<string>('');  
const loading = ref(false);
const error = ref<string | null>(null);
const activeTab = ref<'table' | 'raw'>('table');

const closeModal = () => emit('close');

const loadPreview = async () => {
    if (!props.filename || !props.show) return;

    loading.value = true;
    error.value = null;

    try {
        const { data } = await axios.get(
            route('bulk-import.datasets.preview', { filename: props.filename })
        );

        items.value = data.items ?? [];
        rawData.value = data.raw ?? '';      
    } catch (e: any) {
        error.value = 'Failed to load preview.';
        console.error(e);
    } finally {
        loading.value = false;
    }
};

watch(() => [props.show, props.filename], loadPreview, { immediate: true });
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm" @click.self="closeModal">
            <div class="mx-4 w-full max-w-6xl rounded-3xl border border-gray-700 bg-neutral-900 shadow-2xl overflow-hidden" @click.stop>

                <!-- Header -->
                <div class="flex items-center justify-between border-b border-gray-700 px-6 py-4">
                    <h2 class="text-lg font-semibold text-white">
                        Preview: <span class="font-mono text-blue-400">{{ filename }}</span>
                    </h2>
                    <button @click="closeModal" class="rounded-full p-2 text-gray-400 hover:bg-gray-800 hover:text-white">✕</button>
                </div>

                <!-- Tabs -->
                <div class="flex border-b border-gray-700 bg-neutral-950">
                    <button
                        @click="activeTab = 'table'"
                        :class="['flex-1 py-4 text-sm font-medium transition', activeTab === 'table' ? 'border-b-2 border-blue-500 text-white' : 'text-gray-400']"
                    >
                        Table View
                    </button>
                    <button
                        @click="activeTab = 'raw'"
                        :class="['flex-1 py-4 text-sm font-medium transition', activeTab === 'raw' ? 'border-b-2 border-blue-500 text-white' : 'text-gray-400']"
                    >
                        Raw Original Data
                    </button>
                </div>

                <!-- Content -->
                <div class="max-h-[75vh] overflow-hidden">

                    <!-- Loading -->
                    <div v-if="loading" class="flex h-96 items-center justify-center">
                        <div class="text-center">
                            <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-600 border-t-blue-500"></div>
                            <p class="mt-4 text-gray-400">Loading preview...</p>
                        </div>
                    </div>

                    <!-- Error -->
                    <div v-else-if="error" class="flex h-96 items-center justify-center text-red-400">
                        {{ error }}
                    </div>

                    <!-- Table View -->
                    <div v-else-if="activeTab === 'table' && items.length > 0" class="overflow-auto">
                        <table class="w-full text-sm">
                            <thead class="sticky top-0 bg-neutral-900 border-b border-gray-700">
                                <tr>
                                    <th v-for="key in Object.keys(items[0] || {})" :key="key"
                                        class="px-6 py-4 text-left font-medium text-gray-400 capitalize">
                                        {{ key.replace(/_/g, ' ') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-800">
                                <tr v-for="(item, i) in items" :key="i" class="hover:bg-neutral-800">
                                    <td v-for="key in Object.keys(items[0] || {})" :key="key"
                                        class="px-6 py-3 text-gray-200 whitespace-nowrap">
                                        {{ item[key] ?? '—' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Raw View -->
                    <div v-else-if="activeTab === 'raw'" class="p-6">
                        <pre class="whitespace-pre-wrap font-mono text-sm text-gray-300 bg-black p-6 rounded-2xl overflow-auto max-h-[65vh] border border-gray-700">
{{ rawData || 'No raw data available.' }}
                        </pre>
                    </div>

                    <!-- Empty -->
                    <div v-else class="flex h-96 items-center justify-center text-gray-400">
                        No data available.
                    </div>
                </div>

                <!-- Footer -->
                <div class="border-t border-gray-700 px-6 py-4 text-right">
                    <button @click="closeModal" class="rounded-xl bg-gray-800 px-6 py-2.5 text-sm font-medium text-gray-300 hover:bg-gray-700">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
