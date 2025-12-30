<script setup lang="ts">
interface Props {
    modelValue: string[];
    label?: string;
    name?: string;
    description?: string;
    error?: string | boolean;
    required?: boolean;
    disabled?: boolean;
    maxSize?: number;
    acceptTypes?: string[];
    enableSortable?: boolean;
    gridCols?: string;
}

const props = withDefaults(defineProps<Props>(), {
    label: "輪播圖",
    name: "images",
    description: "",
    error: false,
    required: false,
    disabled: false,
    maxSize: 5 * 1024 * 1024, // 5MB
    acceptTypes: () => ["image/*"],
    enableSortable: true,
    gridCols: "grid-cols-2 md:grid-cols-4 lg:grid-cols-5"
});

const emit = defineEmits<{
    "update:modelValue": [value: string[]];
}>();

// 使用多圖上傳 composable
const upload = useImageUploadMultiple({
    maxSize: props.maxSize,
    acceptTypes: props.acceptTypes,
    enableSortable: props.enableSortable
});

// 監聽 upload.previews 長度變化
watch(
    () => upload.previews.value.length,
    (previewLength) => {
        if (previewLength > 0) {
            const formValueLength = upload.formValue.value.length;
            if (formValueLength === previewLength) {
                emit("update:modelValue", [...upload.formValue.value]);
            } else {
                const newSlide = [...upload.formValue.value];
                emit("update:modelValue", newSlide.slice(0, previewLength));
            }
        } else if (upload.formValue.value.length === 0) {
            emit("update:modelValue", []);
        }
    },
    { immediate: true }
);

// 監聽 upload.formValue 變化
watch(
    () => upload.formValue.value,
    (newValue) => {
        if (newValue.length > 0) {
            // 立即同步到 modelValue，確保上傳完成後數據正確
            emit("update:modelValue", [...newValue]);
        } else if (upload.previews.value.length === 0) {
            emit("update:modelValue", []);
        }
    },
    { immediate: false, deep: true }
);

// 監聽外部 modelValue 變化，載入初始值
watch(
    () => props.modelValue,
    (newValue) => {
        if (
            Array.isArray(newValue) &&
            JSON.stringify(newValue) !==
                JSON.stringify(upload.formValue.value) &&
            upload.previews.value.length === 0
        ) {
            upload.loadInitialValue(newValue);
        }
    },
    { immediate: true, deep: true }
);

// 啟用排序功能
watch(
    () => ({
        length: upload.sortableData.value.length,
        listRef: upload.sortableListRef.value
    }),
    ({ length, listRef }) => {
        if (length > 0 && listRef) {
            nextTick(() => upload.setupSortable());
        }
    },
    { immediate: true }
);

// 暴露上傳方法給父組件
defineExpose({
    upload: upload.upload,
    remove: upload.remove,
    reset: upload.reset,
    loadInitialValue: upload.loadInitialValue,
    // 暴露預覽相關數據（用於預覽功能）
    previews: upload.previews,
    formValue: upload.formValue
});
</script>

<template>
    <UFormField
        :label="label"
        :name="name"
        :description="description"
        :error="error"
        :required="required">
        <div class="space-y-2">
            <input
                :ref="upload.inputRef"
                type="file"
                :accept="acceptTypes.join(',')"
                class="hidden"
                multiple
                :disabled="disabled"
                @change="upload.handleFileSelect" />
            <div
                v-if="upload.sortableData.value.length > 0"
                :ref="upload.sortableListRef"
                :class="['grid gap-2', gridCols]">
                <div
                    v-for="(imageId, index) in upload.sortableData.value"
                    :key="imageId"
                    :data-image-id="imageId"
                    class="relative group">
                    <img
                        :src="
                            (upload.previews.value &&
                                upload.previews.value[index]) ||
                            ''
                        "
                        :alt="`${label} ${index + 1}`"
                        class="w-full object-cover rounded-lg border aspect-square" />
                    <div
                        v-if="enableSortable"
                        class="drag-handle absolute top-2 left-2 cursor-grab active:cursor-grabbing bg-black/50 hover:bg-black/70 rounded p-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <UIcon
                            name="i-lucide-grip-vertical"
                            class="w-4 h-4 text-white" />
                    </div>
                    <UButton
                        icon="i-lucide-x"
                        size="xs"
                        color="error"
                        variant="solid"
                        class="absolute top-2 right-2"
                        :disabled="disabled || upload.isUploading.value"
                        @click="upload.remove(index)" />
                </div>
            </div>
            <UButton
                label="新增輪播圖（可多選）"
                icon="i-lucide-plus"
                color="primary"
                variant="outline"
                block
                :loading="upload.isUploading.value"
                :disabled="disabled || upload.isUploading.value"
                @click="upload.triggerFileSelect" />
        </div>
    </UFormField>
</template>

