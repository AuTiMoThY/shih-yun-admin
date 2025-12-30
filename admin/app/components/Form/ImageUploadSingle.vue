<script setup lang="ts">
interface Props {
    modelValue: string;
    label?: string;
    name?: string;
    description?: string;
    error?: string | boolean;
    required?: boolean;
    disabled?: boolean;
    maxSize?: number;
    acceptTypes?: string[];
    previewMaxWidth?: string;
    previewMaxHeight?: string;
}

const props = withDefaults(defineProps<Props>(), {
    label: "圖片",
    name: "image",
    description: "",
    error: false,
    required: false,
    disabled: false,
    maxSize: 5 * 1024 * 1024, // 5MB
    acceptTypes: () => ["image/*"],
    previewMaxWidth: "100%",
    previewMaxHeight: "300px"
});

const emit = defineEmits<{
    "update:modelValue": [value: string];
}>();

// 使用單圖上傳 composable
const upload = useImageUploadSingle({
    maxSize: props.maxSize,
    acceptTypes: props.acceptTypes
});

// 監聽 upload.formValue 變化，同步到 modelValue
watch(
    () => upload.formValue.value,
    (newValue) => {
        if (newValue && !newValue.startsWith("temp_")) {
            emit("update:modelValue", newValue);
        } else if (newValue && newValue.startsWith("temp_")) {
            // 臨時 ID 也更新，用於驗證
            emit("update:modelValue", newValue);
        }
    }
);

// 監聽 upload.preview 變化
watch(
    () => upload.preview.value,
    (preview) => {
        if (preview) {
            // 有預覽圖時，優先使用 formValue（已上傳的 URL），否則使用臨時 ID
            if (
                upload.formValue.value &&
                !upload.formValue.value.startsWith("temp_")
            ) {
                emit("update:modelValue", upload.formValue.value);
            } else if (upload.tempId.value) {
                emit("update:modelValue", upload.tempId.value);
            } else if (upload.formValue.value) {
                emit("update:modelValue", upload.formValue.value);
            }
        } else if (!preview && !props.modelValue) {
            // 沒有預覽圖且沒有 modelValue 時，清空
            emit("update:modelValue", "");
        }
    },
    { immediate: true }
);

// 監聽外部 modelValue 變化，載入初始值
watch(
    () => props.modelValue,
    (newValue) => {
        if (newValue && newValue !== upload.formValue.value) {
            // 只有在沒有預覽圖時才同步，避免覆蓋新上傳的圖片
            if (!upload.preview.value) {
                upload.loadInitialValue(newValue);
            }
        } else if (!newValue && !upload.preview.value) {
            upload.reset();
        }
    },
    { immediate: true }
);

// 處理移除
const handleRemove = () => {
    upload.remove();
    emit("update:modelValue", "");
};

// 暴露上傳方法給父組件
defineExpose({
    upload: upload.upload,
    remove: handleRemove,
    reset: upload.reset,
    loadInitialValue: upload.loadInitialValue,
    // 暴露預覽相關數據（用於預覽功能）
    preview: upload.preview,
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
                :disabled="disabled"
                @change="upload.handleFileSelect" />
            <div
                v-if="upload.preview.value || modelValue"
                class="relative w-full"
                :style="{
                    maxWidth: previewMaxWidth
                }">
                <img
                    :src="upload.preview.value || modelValue"
                    :alt="label"
                    class="w-full object-cover rounded-lg border"
                    :style="{
                        maxHeight: previewMaxHeight
                    }" />
                <UButton
                    icon="i-lucide-x"
                    size="xs"
                    color="error"
                    variant="solid"
                    class="absolute top-2 right-2"
                    :disabled="disabled || upload.isUploading.value"
                    @click="handleRemove" />
            </div>
            <UButton
                :label="upload.preview.value || modelValue ? '更換圖片' : '上傳圖片'"
                icon="i-lucide-upload"
                color="primary"
                variant="outline"
                block
                :loading="upload.isUploading.value"
                :disabled="disabled || upload.isUploading.value"
                @click="upload.triggerFileSelect" />
        </div>
    </UFormField>
</template>

