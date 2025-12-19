<script setup lang="ts">
import type { FieldConfig, FieldType } from "~/types/CutSectionField";

const { hasPermission, isSuperAdmin } = usePermission();

// 權限檢查
const canSortField = computed(() => isSuperAdmin() || hasPermission('about.field.sort'));
// const canDeleteField = computed(() => isSuperAdmin() || hasPermission('about.field.delete'));

const props = defineProps<{
    field: FieldConfig;
    index: number;
}>();

const emit = defineEmits<{
    (e: "update", field: FieldConfig): void;
    (e: "delete", id: string): void;
    (e: "move-up", index: number): void;
    (e: "move-down", index: number): void;
}>();

const localField = ref<FieldConfig>({ ...props.field });
// console.log("localField:", localField.value);

// 控制標題區的即時編輯
const isEditingLabel = ref(false);

// 監聽外部變更
watch(
    () => props.field,
    (newField) => {
        localField.value = { ...newField };
        // 如果是圖片欄位且值有變更，更新圖片預覽
        if (
            (newField.type === "desktop_image" ||
                newField.type === "mobile_image") &&
            newField.value !== imagePreview.value
        ) {
            loadInitialValue(newField.value || null);
            if (newField.value) {
                loadAspectFromSrc(newField.value);
            } else {
                imageAspectRatio.value = null;
            }
        }
    },
    { deep: true }
);

// 更新欄位名稱
const updateLabel = (label: string) => {
    localField.value.label = label;
    emit("update", { ...localField.value });
};

// 更新欄位內容
const updateValue = (value: string) => {
    console.log("更新欄位內容:", value);
    localField.value.value = value;
    emit("update", { ...localField.value });
};

// 欄位類型顯示名稱
const fieldTypeNames: Record<FieldType, string> = {
    title: "標題",
    subtitle: "副標題",
    content: "內文",
    desktop_image: "電腦版圖片",
    mobile_image: "手機版圖片",
    video: "影片"
};

// 圖片上傳
const {
    inputRef: imageInputRef,
    preview: imagePreview,
    isUploading,
    handleFileSelect,
    triggerFileSelect,
    remove: removeImage,
    upload,
    loadInitialValue,
    formValue
} = useImageUploadSingle({
    onPreviewChange: (previewUrl) => {
        // 當預覽變更時，如果有上傳成功的 URL（不是 blob URL），更新欄位值
        if (previewUrl && !previewUrl.startsWith('blob:')) {
            updateValue(previewUrl);
        }
    }
});

// 處理圖片上傳（選擇後立即上傳）
const handleImageUpload = async (event: Event) => {
    await handleFileSelect(event);
    // 立即上傳
    const success = await upload();
    if (success && formValue.value) {
        // 上傳成功後，確保欄位值已更新
        updateValue(formValue.value);
    }
};

const triggerImageUpload = () => {
    triggerFileSelect();
};

const imageAspectRatio = ref<string | null>(null);

const loadAspectFromSrc = (src: string) => {
    const img = new Image();
    img.onload = () => {
        imageAspectRatio.value = `${img.naturalWidth} / ${img.naturalHeight}`;
    };
    img.src = src;
};

const handleRemoveImage = () => {
    removeImage();
    updateValue("");
    imageAspectRatio.value = null;
};

const fieldIcon = computed(() => {
    return {
        title: "i-lucide-heading",
        subtitle: "i-lucide-heading-2",
        desktop_image: "i-lucide-image",
        mobile_image: "i-lucide-image",
        content: "i-lucide-file-text",
        video: "i-lucide-video"
    }[props.field.type];
});

// 初始化圖片預覽
onMounted(() => {
    if (
        (props.field.type === "desktop_image" ||
            props.field.type === "mobile_image") &&
        props.field.value
    ) {
        loadInitialValue(props.field.value);
        loadAspectFromSrc(props.field.value);
    }
});

watch(isEditingLabel, async (editing) => {
    if (editing) {
        await nextTick();
        // labelInputRef.value?.input?.focus?.();
    }
});
</script>

<template>
    <UCard class="relative group">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <UIcon :name="fieldIcon" class="w-5 h-5" />
                    <div class="flex items-center gap-2">
                        <template v-if="isEditingLabel">
                            <UInput
                                v-model="localField.label"
                                size="xs"
                                class="w-44"
                                :placeholder="`請輸入欄位名稱，例如：精神一主標`"
                                @blur="
                                    isEditingLabel = false;
                                    updateLabel(localField.label);
                                "
                                @keyup.enter="
                                    isEditingLabel = false;
                                    updateLabel(localField.label);
                                "
                                :autofocus="isEditingLabel">
                                <template #trailing>
                                    <UKbd value="Enter" />
                                </template>
                            </UInput>
                        </template>
                        <template v-else>
                            <span
                                class="text-sm font-semibold text-primary cursor-pointer hover:underline"
                                @click="isEditingLabel = true">
                                {{
                                    localField.label ||
                                    fieldTypeNames[field.type]
                                }}
                            </span>
                        </template>
                        <span
                            class="text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded">
                            類型：{{ fieldTypeNames[field.type] }}
                        </span>
                    </div>
                </div>
                <div class="flex items-center gap-1">
                    <PermissionGuard permission="about.field.sort">
                        <UButton
                            v-if="index > 0 && canSortField"
                            icon="i-lucide-arrow-up"
                            size="xs"
                            color="neutral"
                            variant="ghost"
                            @click="emit('move-up', index)" />
                    </PermissionGuard>

                    <PermissionGuard permission="about.field.sort">
                        <UButton
                            icon="i-lucide-arrow-down"
                            size="xs"
                            color="neutral"
                            variant="ghost"
                            @click="emit('move-down', index)" />
                    </PermissionGuard>

                    <PermissionGuard permission="about.field.delete">
                        <UButton
                            icon="i-lucide-trash-2"
                            size="xs"
                            color="error"
                            variant="ghost"
                            @click="emit('delete', field.id)" />
                    </PermissionGuard>
                </div>
            </div>
        </template>

        <div class="">
            <!-- 根據欄位類型顯示不同的輸入元件 -->
            <template
                v-if="field.type === 'title' || field.type === 'subtitle'">
                <UFormField
                    name="field-value"
                    :ui="{ root: 'flex items-center gap-2' }">
                    <UTextarea
                        v-model="localField.value"
                        :placeholder="`請輸入${localField.label}`"
                        :rows="1"
                        @update:model-value="updateValue"
                        autoresize
                        :ui="{ root: 'w-full' }" />
                </UFormField>
            </template>
            
            <template v-else-if="field.type === 'content'">
                <UFormField name="field-value">
                    <UTextarea
                        v-model="localField.value"
                        placeholder="請輸入內文"
                        :rows="3"
                        @update:model-value="updateValue"
                        autoresize
                        :ui="{ root: 'w-full' }" />
                </UFormField>
            </template>

            <template
                v-else-if="
                    field.type === 'desktop_image' ||
                    field.type === 'mobile_image'
                ">
                <UFormField
                    :label="fieldTypeNames[field.type]"
                    name="field-value">
                    <div class="space-y-2">
                        <input
                            ref="imageInputRef"
                            type="file"
                            accept="image/*"
                            class="hidden"
                            @change="handleImageUpload" />
                        <div
                            v-if="imagePreview || localField.value"
                            class="relative w-full max-w-lg">
                            <img
                                :src="imagePreview || localField.value"
                                alt="預覽"
                                class="w-full max-w-lg object-cover rounded-lg border"
                                :style="
                                    imageAspectRatio
                                        ? { aspectRatio: imageAspectRatio }
                                        : undefined
                                " />
                            <UButton
                                icon="i-lucide-x"
                                size="xs"
                                color="error"
                                variant="solid"
                                class="absolute top-2 right-2"
                                @click="handleRemoveImage" />
                        </div>
                        <UButton
                            :label="
                                imagePreview || localField.value
                                    ? '更換圖片'
                                    : '上傳圖片'
                            "
                            icon="i-lucide-upload"
                            color="primary"
                            variant="outline"
                            block
                            :loading="isUploading"
                            :disabled="isUploading"
                            @click="triggerImageUpload" />
                    </div>
                </UFormField>
            </template>

            <template v-else-if="field.type === 'video'">
                <UFormField name="field-value">
                    <UInput
                        v-model="localField.value"
                        placeholder="請輸入影片連結"
                        @update:model-value="updateValue"
                        :ui="{ root: 'w-full' }" />
                </UFormField>
            </template>

        </div>
    </UCard>
</template>
