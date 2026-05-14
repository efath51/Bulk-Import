<script setup lang="ts">
import axios from 'axios';
import { ref, onMounted } from 'vue';


const defaultPatterns = [
    { name: 'Item Number',  pattern: '/Item\\s+\\d+/i' },
    { name: 'Name to Name', pattern: '/Name:.*?(?=Name:|$)/s' },
    { name: 'Blank Line',   pattern: '/\\n{2,}/' },
]

const configs        = ref([])
const selectedName   = ref('')
const customPattern  = ref('')
const saving         = ref(false)
const saved          = ref(false)

onMounted(async () => {
    const { data } = await axios.get(route('bulk-import.parser-config.index'))
    configs.value = data.configs

    // Pre-fill active pattern if exists
    if (data.active) {
        selectedName.value  = data.active.name
        customPattern.value = data.active.pattern
    }
})

const onSelect = (e: Event) => {
    const name    = (e.target as HTMLSelectElement).value
    const pattern = defaultPatterns.find(p => p.name === name)

    selectedName.value  = name
    customPattern.value = pattern?.pattern ?? ''
}

const savePattern = async () => {
    if (!customPattern.value || !selectedName.value) return

    saving.value = true

    await axios.post(route('bulk-import.parser-config.save'), {
        name:    selectedName.value,
        pattern: customPattern.value,
    })

    saving.value = false
    saved.value  = true
    setTimeout(() => saved.value = false, 2000)
}
</script>

<template>
    <div class="rounded-2xl border border-gray-700 bg-neutral-900 p-6">
        <h3 class="mb-4 text-lg font-medium">Parsing Configuration</h3>

        <div class="space-y-5 text-sm">

        
            <div>
                <label class="mb-1.5 block text-gray-400">Preset Patterns</label>
                <select @change="onSelect"
                        class="w-full rounded-lg border border-gray-700 bg-black px-4 py-3 text-sm">
                    <option value="">— Select a preset —</option>
                    <option v-for="p in defaultPatterns" :key="p.name" :value="p.name">
                        {{ p.name }}
                    </option>
                </select>
            </div>

           
            <div>
                <label class="mb-1.5 block text-gray-400">Pattern</label>
                <input v-model="customPattern"
                       placeholder="Write a custom pattern or pick a preset above"
                       class="w-full rounded-lg border border-gray-700 bg-black px-4 py-3 font-mono text-sm" />
            </div>

            
            <div>
                <label class="mb-1.5 block text-gray-400">Pattern Name</label>
                <input v-model="selectedName"
                       placeholder="e.g. My Custom Pattern"
                       class="w-full rounded-lg border border-gray-700 bg-black px-4 py-3 text-sm" />
            </div>

        </div>

        <button @click="savePattern" :disabled="saving"
                class="mt-6 w-full rounded-xl bg-gray-800 py-3 text-sm font-medium
                       transition hover:bg-gray-700 disabled:opacity-50">
            {{ saving ? 'Saving...' : saved ? ' Saved' : 'Save Pattern' }}
        </button>
    </div>
</template>
