<script setup lang="ts">
import { h, ref, resolveComponent, computed, nextTick, watch, shallowRef, onUnmounted } from "vue";
import { useSortable } from "@vueuse/integrations/useSortable";
import { IS_SHOW_FRONTEND_LABEL_MAP } from "~/constants/is_show_frontend";
import { IS_SHOW_BACKEND_LABEL_MAP } from "~/constants/is_show_backend";
import { LEVEL_STATUS_LABEL_MAP } from "~/constants/level_status";
import TreeTableRow from "./TreeTableRow.vue";

const props = defineProps<{
    level: any;
    depth?: number;
    onEdit?: (level: any) => void;
    onAddSub?: (level: any) => void;
    onUpdateSortOrder?: (list: any[]) => Promise<void>;
}>();

const emit = defineEmits<{
    (e: "refresh"): void;
}>();

const UButton = resolveComponent("UButton");
const UBadge = resolveComponent("UBadge");
const isExpanded = ref(true);
const currentDepth = props.depth ?? 0;
const indentWidth = 24; // 每層縮進 24px
const childrenBodyRef = ref<HTMLElement | null>(null);
const childrenData = shallowRef<any[]>([]);
let sortableStop: (() => void) | null = null;

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

const debugLog = (...args: any[]) => {
    console.log(`[structure-sort-depth-${currentDepth}]`, ...args);
};

// 初始化子層級資料
watch(
    () => props.level.children,
    (newChildren) => {
        if (newChildren && Array.isArray(newChildren)) {
            childrenData.value = [...newChildren].filter(Boolean);
        } else {
            childrenData.value = [];
        }
    },
    { immediate: true, deep: true }
);

// 設置子層級的拖曳功能
const setupChildrenSortable = () => {
    // 清理舊的實例
    if (sortableStop) {
        sortableStop();
        sortableStop = null;
    }

    if (!hasChildren.value || !isExpanded.value || !childrenBodyRef.value) {
        return;
    }

    const depth = currentDepth + 1;
    const { stop } = useSortable(childrenBodyRef, childrenData, {
        // group: "nested",
        handle: `tr[data-depth="${depth}"] .drag-handle`,
        animation: 150,
        draggable: `tr[data-depth="${depth}"]`,
        fallbackOnBody: true,
        swapThreshold: 0.65,
        onUpdate: async (evt: any) => {
            debugLog("onUpdate children", {
                oldIndex: evt.oldIndex,
                newIndex: evt.newIndex,
                levelId: props.level.id
            });

            const list = childrenData.value || [];
            const rows = (
                Array.from(
                    childrenBodyRef.value?.querySelectorAll(
                        `tr[data-depth="${depth}"]`
                    ) ?? []
                ) || []
            ) as HTMLElement[];
            const idsAfterDom = rows
                .map((r) => r.dataset.levelId)
                .filter(Boolean);

            debugLog("onUpdate children start", {
                listLength: list.length,
                idsBefore: list.map((x) => x?.id),
                idsAfterDom
            });

            // 根據 DOM 順序重建資料
            const map = new Map(
                list
                    .filter((x) => x && x.id !== undefined)
                    .map((x) => [String(x.id), x])
            );
            const newList = idsAfterDom
                .map((id) => map.get(String(id)))
                .filter(Boolean);

            if (!newList.length || newList.length !== map.size) {
                console.warn(
                    `[structure-sort-depth-${depth}] reorder mismatch, refetch`,
                    {
                        idsAfterDom,
                        listIds: list.map((x) => x?.id)
                    }
                );
                emit("refresh");
                return;
            }

            // 更新子層級資料
            childrenData.value = [...newList];
            // 同步更新到父層級的 level.children
            if (props.level) {
                props.level.children = [...newList];
            }

            // 調用更新排序的 API
            if (props.onUpdateSortOrder) {
                await props.onUpdateSortOrder(newList);
            }

            debugLog("onUpdate children done", {
                movedId: evt.item?.dataset?.levelId,
                idsAfter: childrenData.value.map((x) => x?.id)
            });
        }
    });
    sortableStop = stop;
};

// 當展開狀態改變或子層級資料改變時，重新設置拖曳功能
watch(
    [isExpanded, hasChildren, childrenData],
    async () => {
        if (hasChildren.value && isExpanded.value) {
            await nextTick();
            setupChildrenSortable();
        }
    },
    { immediate: true }
);

// 組件卸載時清理
onUnmounted(() => {
    if (sortableStop) {
        sortableStop();
        sortableStop = null;
    }
});

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
                    :data-depth="currentDepth"
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
                <span class="font-medium">{{ level.label }}</span>
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
        <tbody ref="childrenBodyRef" style="display: contents">
            <TreeTableRow
                v-for="child in childrenData"
                :key="child.id"
                :level="child"
                :depth="currentDepth + 1"
                :on-edit="onEdit"
                :on-add-sub="onAddSub"
                :on-update-sort-order="onUpdateSortOrder"
                @refresh="emit('refresh')" />
        </tbody>
    </template>
</template>

