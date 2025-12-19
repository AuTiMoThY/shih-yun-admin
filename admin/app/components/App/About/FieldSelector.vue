<script setup lang="ts">
import type { FieldType, FieldTypeOption } from "~/types/CutSectionField";

const emit = defineEmits<{
    (e: "select", type: FieldType): void;
}>();

const fieldTypes: FieldTypeOption[] = [
    {
        type: "title",
        label: "標題",
        icon: "i-lucide-heading",
        description: "主要標題文字"
    },
    {
        type: "subtitle",
        label: "副標題",
        icon: "i-lucide-heading-2",
        description: "次要標題文字"
    },
    {
        type: "content",
        label: "內文",
        icon: "i-lucide-file-text",
        description: "文字內容區塊"
    },
    {
        type: "desktop_image",
        label: "電腦版圖片",
        icon: "i-lucide-monitor",
        description: "電腦版顯示的圖片"
    },
    {
        type: "mobile_image",
        label: "手機版圖片",
        icon: "i-lucide-smartphone",
        description: "手機版顯示的圖片"
    },
    {
        type: "video",
        label: "影片",
        icon: "i-lucide-video",
        description: "影片區塊"
    }
];

const handleSelect = (type: FieldType) => {
    emit("select", type);
};
</script>

<template>
    <UCard>
        <template #header>
            <h4 class="text-md font-semibold">新增欄位</h4>
        </template>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            <UCard
                v-for="fieldType in fieldTypes"
                :key="fieldType.type"
                class="cursor-pointer hover:shadow-md transition-shadow"
                @click="handleSelect(fieldType.type)">
                <div class="flex flex-col items-center text-center p-4 space-y-2">
                    <UIcon :name="fieldType.icon" class="w-8 h-8 text-primary" />
                    <div>
                        <div class="font-medium">{{ fieldType.label }}</div>
                        <div class="text-xs text-gray-500">{{ fieldType.description }}</div>
                    </div>
                </div>
            </UCard>
        </div>
    </UCard>
</template>
