<script setup lang="ts">
import DatasetPreviewModal from "@/components/BulkImport/DatasetPreviewModal.vue";
import FileUpload from "@/Components/BulkImport/FileUpload.vue";
import PerseConfigForm from "@/components/BulkImport/PerseConfigForm.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { router } from "@inertiajs/vue3";
import { ref } from "vue";
const props = defineProps<{
  datasets: {
    name: string;
    size: string;
    uploaded: string;
    path: string;
  }[];
}>();
const previewing = ref<string | null>(null);

const openPreview = (filename: string) => {
  previewing.value = filename;
};

const closePreview = () => {
  previewing.value = null;
};

const load = (name: string) => {
  router.post(route("bulk-import.load"), { filename: name });
};

const remove = (name: string) => {
  if (confirm(`Delete ${name}?`)) {
    router.delete(route("bulk-import.datasets.delete", { filename: name }));
  }
};

const breadcrumbs = [
  { title: "Bulk Import", href: "/bulk-import" },
  { title: "Datasets", href: "/bulk-import/datasets" },
];
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs" title="Datasets">
    <div class="mx-auto w-full px-4 py-6">
      <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">

        <div class="lg:col-span-8">
          <div class="mb-4 flex items-center justify-between">
            <h2 class="text-xl font-semibold">Uploaded Datasets</h2>
            <span class="text-sm text-gray-400"
              >{{ datasets.length }} files</span
            >
          </div>

          <!-- Empty -->
          <div
            v-if="datasets.length === 0"
            class="rounded-2xl border border-dashed py-20 text-center text-gray-400"
          >
            No datasets uploaded yet.
          </div>

          <!-- Table -->
          <div v-else class="overflow-hidden rounded-xl border">
            <table class="w-full text-sm">
              <thead class="border-b bg-gray-50 text-left dark:bg-neutral-900">
                <tr>
                  <th class="px-5 py-3 font-medium">File</th>
                  <th class="px-5 py-3 font-medium">Size</th>
                  <th class="px-5 py-3 font-medium">Uploaded</th>
                  <th class="px-5 py-3 text-right font-medium">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y">
                <template v-for="file in datasets" :key="file.name">
                  <tr
                    class="transition hover:bg-gray-50 dark:hover:bg-gray-900"
                  >
                    <!-- File name + icon -->
                    <td class="px-5 py-4">
                      <div class="flex items-center gap-3">
                        <span
                          class="rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-semibold uppercase text-blue-600"
                        >
                          {{ file.name.split(".").pop() }}
                        </span>
                        <span class="font-medium text-gray-300">{{
                          file.name
                        }}</span>
                      </div>
                    </td>

                    <td class="px-5 py-4 text-gray-500">{{ file.size }}</td>
                    <td class="px-5 py-4 text-gray-500">{{ file.uploaded }}</td>

                    <!-- Actions -->
                    <td class="px-5 py-4">
                      <div class="flex justify-end gap-2">
                        <button
                          @click="
                            previewing === file.name
                              ? closePreview()
                              : openPreview(file.name)
                          "
                          class="rounded-lg border border-gray-700 px-3 py-1.5 text-xs font-medium text-gray-300 transition hover:bg-gray-800"
                        >
                          {{
                            previewing === file.name
                              ? "Hide Preview"
                              : "Preview"
                          }}
                        </button>
                        <button
                          @click="load(file.name)"
                          class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-blue-700"
                        >
                          Load
                        </button>
                        <button
                          @click="remove(file.name)"
                          class="rounded-lg bg-red-50 px-3 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-100"
                        >
                          Delete
                        </button>
                      </div>
                    </td>
                  </tr>

                  <tr v-if="previewing === file.name">
                    <td colspan="4" class="bg-gray-50 px-5 py-4 dark:bg-black">
                      <PdfPreview :filename="file.name" />
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </div>

        <div class="space-y-8 lg:col-span-4 border-s ps-4">
          <div>
            <h2 class="mb-4 text-lg font-semibold">Upload New Dataset</h2>
            <FileUpload />
          </div>
          <hr />

          <PerseConfigForm />
        </div>
      </div>
    </div>

    <!-- Modal -->
    <DatasetPreviewModal
      :filename="previewing"
      :show="!!previewing"
      @close="previewing = null"
    />
  </AppLayout>
</template>
