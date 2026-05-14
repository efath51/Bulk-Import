<script setup lang="ts">
import axios from 'axios';
import { ref, onMounted } from 'vue';

const props = defineProps<{ filename: string }>();

const items   = ref<Record<string, string>[]>([]);
const loading = ref(true);
const error   = ref<string | null>(null);

onMounted(async () => {
    try {
        const { data } = await axios.get(
            route('bulk-import.datasets.preview', { filename: props.filename })
        );
        items.value = data.items ?? [];
    } catch (e) {
        error.value = 'Could not load preview.';
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div>
    
        <div v-if="loading" class="py-6 text-center text-sm text-gray-400">
            Loading preview...
        </div>

     
        <div v-else-if="error" class="py-6 text-center text-sm text-red-400">
            {{ error }}
        </div>


        <div v-else-if="items.length === 0" class="py-6 text-center text-sm text-gray-400">
            No items found in this file.
        </div>

        <!-- Table -->
        <div v-else class="overflow-x-auto rounded-xl border dark:bg-neutral-900">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-neutral-900 text-left text-gray-500">
                    <tr>
                        <th v-for="key in Object.keys(items[0])" :key="key"
                            class="px-4 py-3 font-medium capitalize">
                            {{ key.replace(/_/g, ' ') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="(item, i) in items" :key="i" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td v-for="key in Object.keys(items[0])" :key="key"
                            class="px-4 py-3 text-gray-700 dark:text-gray-200">
                            {{ item[key] ?? '—' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
