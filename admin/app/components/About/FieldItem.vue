<script setup lang="ts">
import type { FieldConfig, FieldType } from "~/types/CutSectionField";

const toast = useToast();
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
import { useDateFormat, useNow } from "@vueuse/core";

// 監聽外部變更
watch(
    () => props.field,
    (newField) => {
        localField.value = { ...newField };
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
    desktop_image: "電腦版圖片",
    mobile_image: "手機版圖片",
    content: "內文"
};

// 圖片上傳
const { uploadImage, getImagePreview, getImageDimensions } = useImageUpload();
const imagePreview = ref<string | null>(null);
const imageInputRef = ref<HTMLInputElement | null>(null);
const isUploading = ref(false);
const imageAspectRatio = ref<string | null>(null);

const handleImageUpload = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) return;

    // 驗證檔案類型
    if (!file.type.startsWith("image/")) {
        toast.add({
            title: "檔案格式錯誤",
            description: "請選擇圖片檔案",
            color: "error"
        });
        return;
    }

    // 驗證檔案大小（例如：5MB）
    const maxSize = 5 * 1024 * 1024; // 5MB
    if (file.size > maxSize) {
        toast.add({
            title: "檔案過大",
            description: "圖片大小不能超過 5MB",
            color: "error"
        });
        return;
    }

    isUploading.value = true;

    try {
        // 先顯示本地預覽與長寬比
        const [preview, dims] = await Promise.all([
            getImagePreview(file),
            getImageDimensions(file)
        ]);
        imagePreview.value = preview;
        imageAspectRatio.value = `${dims.width} / ${dims.height}`;

        // 上傳到伺服器
        const uploadedUrl = await uploadImage(file);
        if (uploadedUrl) {
            updateValue(uploadedUrl);
            imagePreview.value = uploadedUrl;
        } else {
            // 上傳失敗，保留本地預覽但標記為未上傳
            imagePreview.value = preview;
        }
    } catch (error) {
        console.error("圖片處理錯誤:", error);
    } finally {
        isUploading.value = false;
        // 清空 input 值，允許重新選擇相同檔案
        if (imageInputRef.value) {
            imageInputRef.value.value = "";
        }
    }
};

const triggerImageUpload = () => {
    imageInputRef.value?.click();
};

const removeImage = () => {
    imagePreview.value = null;
    imageAspectRatio.value = null;
    updateValue("");
    if (imageInputRef.value) {
        imageInputRef.value.value = "";
    }
};

const loadAspectFromSrc = (src: string) => {
    const img = new Image();
    img.onload = () => {
        imageAspectRatio.value = `${img.naturalWidth} / ${img.naturalHeight}`;
    };
    img.src = src;
};

const fieldIcon = computed(() => {
    return {
        title: "i-lucide-heading",
        subtitle: "i-lucide-heading-2",
        desktop_image: "i-lucide-image",
        mobile_image: "i-lucide-image",
        content: "i-lucide-file-text"
    }[props.field.type];
});

// 初始化圖片預覽
onMounted(() => {
    if (
        (props.field.type === "desktop_image" ||
            props.field.type === "mobile_image") &&
        props.field.value
    ) {
        imagePreview.value = props.field.value;
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
                                class="text-sm font-semibold text-gray-900 cursor-pointer hover:underline"
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

        <div class="space-y-4">
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
                                @click="removeImage" />
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
        </div>
    </UCard>
</template>
