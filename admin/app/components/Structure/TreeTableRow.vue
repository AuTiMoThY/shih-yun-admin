<script setup lang="ts">
import { h, ref, resolveComponent, computed } from "vue";
import { IS_SHOW_FRONTEND_LABEL_MAP } from "~/constants/is_show_frontend";
import { IS_SHOW_BACKEND_LABEL_MAP } from "~/constants/is_show_backend";
import { LEVEL_STATUS_LABEL_MAP } from "~/constants/level_status";
import TreeTableRow from "./TreeTableRow.vue";

const props = defineProps<{
    level: any;
    depth?: number;
    onEdit?: (level: any) => void;
    onAddSub?: (level: any) => void;
}>();

const emit = defineEmits<{
    (e: "refresh"): void;
}>();

const UButton = resolveComponent("UButton");
const UBadge = resolveComponent("UBadge");
const isExpanded = ref(true);
const currentDepth = props.depth ?? 0;
const indentWidth = 24; // 每層縮進 24px

const isShowFrontendLabelMap = IS_SHOW_FRONTEND_LABEL_MAP;
const isShowBackendLabelMap = IS_SHOW_BACKEND_LABEL_MAP;
const levelStatusLabelMap = LEVEL_STATUS_LABEL_MAP;

const hasChildren = computed(() => {
    return props.level.children && props.level.children.length > 0;
});

const toggleExpand = () => {
    if (hasChildren.value) {
        isExpanded.value = !isExpanded.value;
    }
};

</script>

<template>
    <tr
        class="hover:bg-gray-50 dark:hover:bg-gray-800/50"
        :data-depth="currentDepth"
        :data-level-id="level.id">
        <td class="py-2 px-4 border-b border-default">
            <div class="flex items-center gap-2" :style="{ paddingLeft: `${currentDepth * indentWidth}px` }">
                <UIcon
                    name="i-lucide-grip-vertical"
                    class="w-4 h-4 text-gray-500 drag-handle cursor-grab" />
                <button
                    v-if="hasChildren"
                    @click="toggleExpand"
                    class="flex items-center justify-center w-5 h-5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                    <UIcon
                        :name="isExpanded ? 'i-lucide-chevron-down' : 'i-lucide-chevron-right'"
                        class="w-4 h-4" />
                </button>
                <span v-else class="w-5"></span>
                <span class="font-medium">{{ level.name }}</span>
            </div>
        </td>
        <td class="py-2 px-4 border-b border-default">
            <div class="flex items-center gap-2">
                <UBadge
                    :label="isShowFrontendLabelMap[level.is_show_frontend] ?? level.is_show_frontend"
                    :color="level.is_show_frontend === '1' ? 'success' : 'error'" />
            </div>
        </td>
        <td class="py-2 px-4 border-b border-default">
            <div class="flex items-center gap-2">
                <UBadge
                    :label="isShowBackendLabelMap[level.is_show_backend] ?? level.is_show_backend"
                    :color="level.is_show_backend === '1' ? 'success' : 'error'" />
            </div>
        </td>
        <td class="py-2 px-4 border-b border-default">
            <div class="flex items-center gap-2">
                <UBadge
                    :label="levelStatusLabelMap[level.status] ?? level.status"
                    :color="level.status === '1' ? 'success' : 'error'" />
            </div>
        </td>
        <td class="py-2 px-4 border-b border-default">
            <div class="flex items-center gap-2">
                <UButton
                    icon="i-lucide-edit"
                    label="編輯"
                    color="primary"
                    size="xs"
                    @click="onEdit?.(level)" />
                <UButton
                    icon="i-lucide-plus"
                    label="加入子層級"
                    color="primary"
                    size="xs"
                    @click="onAddSub?.(level)" />
            </div>
        </td>
    </tr>
    <template v-if="hasChildren && isExpanded">
        <tbody style="display: contents">
            <TreeTableRow
                v-for="child in level.children"
                :key="child.id"
                :level="child"
                :depth="currentDepth + 1"
                :on-edit="onEdit"
                :on-add-sub="onAddSub"
                @refresh="emit('refresh')" />
        </tbody>
    </template>
</template>

