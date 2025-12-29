<script setup lang="ts">
import type { PreviewData } from "~/composables/useFormPreview";

interface Props {
    data: PreviewData;
    coverUrl?: string;
    slideUrls?: string[];
    title?: string;
    moduleType?: "news" | "case" | "about" | "custom";
}

const props = withDefaults(defineProps<Props>(), {
    coverUrl: "",
    slideUrls: () => [],
    title: "預覽",
    moduleType: "custom"
});

/**
 * 格式化日期顯示
 */
const formatDate = (dateStr?: string): string => {
    if (!dateStr) return "";
    try {
        const date = new Date(dateStr);
        return date.toLocaleDateString("zh-TW", {
            year: "numeric",
            month: "long",
            day: "numeric"
        });
    } catch {
        return dateStr;
    }
};

/**
 * 渲染 HTML 內容（安全處理）
 */
const renderContent = (html?: string): string => {
    if (!html) return "";
    // 這裡可以加入 XSS 防護邏輯
    // 目前直接返回，因為內容來自管理後台
    return html;
};

/**
 * 解析 content（Case 模組需要解析 JSON 字符串）
 */
const parsedContent = computed(() => {
    if (props.moduleType === "case" && props.data.content) {
        try {
            const parsed = JSON.parse(props.data.content);
            return Array.isArray(parsed) ? parsed : null;
        } catch {
            return null;
        }
    }
    return null;
});

console.log(props.data);
</script>

<template>
    <div class="form-preview space-y-6 p-6">
        <!-- 標題 -->
        <div v-if="title || data.title" class="border-b pb-4">
            <h2 class="text-2xl font-bold text-gray-900">
                {{ data.title || title }}
            </h2>
            <!-- 日期（News 模組） -->
            <div
                v-if="moduleType === 'news' && data.show_date"
                class="mt-2 text-sm text-gray-500">
                <UIcon name="i-lucide-calendar" class="w-4 h-4 inline mr-1" />
                {{ formatDate(data.show_date) }}
            </div>
        </div>

        <!-- 封面圖 -->
        <div v-if="coverUrl || data.cover" class="w-full">
            <img
                :src="coverUrl || data.cover"
                alt="封面圖"
                class="w-full object-cover rounded-lg shadow-md"
                style="max-height: 400px" />
        </div>

        <!-- 輪播圖（News、Case 模組） -->
        <div
            v-if="
                (moduleType === 'news' || moduleType === 'case') &&
                (slideUrls?.length > 0 || (data.slide?.length ?? 0) > 0)
            "
            class="w-full">
            <h3 class="text-lg font-semibold mb-3 text-gray-700">輪播圖</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                <div
                    v-for="(url, index) in slideUrls || data.slide"
                    :key="index"
                    class="relative aspect-square">
                    <img
                        :src="url"
                        :alt="`輪播圖 ${index + 1}`"
                        class="w-full h-full object-cover rounded-lg border shadow-sm" />
                </div>
            </div>
        </div>

        <!-- 其他欄位（用於擴展） -->
        <div
            v-if="moduleType === 'case'"
            class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div v-if="data.year" class="flex items-center gap-2">
                <UIcon name="i-lucide-calendar" class="w-4 h-4 text-gray-500" />
                <span class="text-gray-600">年份：</span>
                <span class="font-semibold">{{ data.year }}</span>
            </div>
            <div v-if="data.s_text" class="flex items-center gap-2">
                <UIcon name="i-lucide-tag" class="w-4 h-4 text-gray-500" />
                <span class="text-gray-600">小字：</span>
                <span class="font-semibold">{{ data.s_text }}</span>
            </div>
            <div v-if="data.ca_type" class="flex items-center gap-2">
                <UIcon name="i-lucide-building" class="w-4 h-4 text-gray-500" />
                <span class="text-gray-600">類型：</span>
                <span class="font-semibold">{{ data.ca_type }}</span>
            </div>
            <div v-if="data.ca_area" class="flex items-center gap-2">
                <UIcon name="i-lucide-map-pin" class="w-4 h-4 text-gray-500" />
                <span class="text-gray-600">區域：</span>
                <span class="font-semibold">{{ data.ca_area }}</span>
            </div>
        </div>
        <!-- 內容區塊（Case 模組的特殊處理） -->
        <div v-if="moduleType === 'case' && parsedContent" class="space-y-6">
            <div
                v-for="(section, index) in parsedContent"
                :key="section?.id || index"
                class="border rounded-lg p-4 bg-gray-50">
                <h4 class="text-lg font-semibold mb-3 text-gray-800">
                    區塊 {{ section?.index || index + 1 }}
                </h4>
                <div
                    v-if="section?.fields && Array.isArray(section.fields)"
                    class="space-y-3">
                    <div
                        v-for="(field, fieldIndex) in section.fields"
                        :key="fieldIndex"
                        class="border-l-4 border-primary pl-4">
                        section.fields.{{ Number(fieldIndex) + 1 }}
                        <pre>{{ field }}</pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- HTML 內容（News 模組） -->
        <div
            v-if="moduleType === 'news' && data.content"
            class="prose-content max-w-none">
            <div v-html="renderContent(data.content)" />
        </div>

        <!-- 自訂內容（其他模組或自訂渲染） -->
        <div v-if="moduleType === 'custom'" class="prose-content max-w-none">
            <div v-if="data.content" v-html="renderContent(data.content)" />
        </div>
    </div>
</template>

<style scoped>
@reference '~/assets/css/main.css';

.form-preview {
    @apply bg-white;
}
</style>
