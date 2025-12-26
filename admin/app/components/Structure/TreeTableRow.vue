<script setup lang="ts">
import {
    h,
    ref,
    resolveComponent,
    computed,
    nextTick,
    watch,
    shallowRef,
    onUnmounted
} from "vue";
import { useSortable } from "@vueuse/integrations/useSortable";
import { LEVEL_STATUS_LABEL_MAP } from "~/constants/level_status";
import TreeTableRow from "./TreeTableRow.vue";

const props = withDefaults(
    defineProps<{
        level: any;
        depth?: number;
        isExpanded?: boolean;
        mobileOnly?: boolean; // 只渲染手機版
        desktopOnly?: boolean; // 只渲染桌面版
        onEdit?: (level: any) => void;
        onAddSub?: (level: any) => void;
        onDelete?: (level: any) => void;
        onUpdateSortOrder?: (list: any[]) => Promise<void>;
    }>(),
    {
        mobileOnly: false,
        desktopOnly: false
    }
);

const emit = defineEmits<{
    (e: "refresh"): void;
}>();

const UButton = resolveComponent("UButton");
const UBadge = resolveComponent("UBadge");
const isExpanded = ref(props.isExpanded ?? true);
const currentDepth = props.depth ?? 0;
const indentWidth = 24; // 每層縮進 24px
const childrenBodyRef = ref<HTMLElement | null>(null);
const childrenData = shallowRef<any[]>([]);
let sortableStop: (() => void) | null = null;


const levelStatusLabelMap = LEVEL_STATUS_LABEL_MAP;

const { data: modulesData } = useModule();
// console.log("modulesData", modulesData.value);
const moduleName = computed(() => {
    const target = modulesData.value?.find(
        (module) => module.id === props.level.module_id
    );
    return target ? `${target.label} (${target.name})` : "";
});

const canAddSub = computed(
    () => !props.level?.module_id && !props.level?.url
);


const hasChildren = computed(() => {
    return props.level.children && props.level.children.length > 0;
});

const toggleExpand = () => {
    if (hasChildren.value) {
        isExpanded.value = !isExpanded.value;
    }
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
        onStart: () => {
            // 拖曳開始時收合當前層級
            isExpanded.value = false;
        },
        onUpdate: async (evt: any) => {
            console.log("onUpdate children", {
                oldIndex: evt.oldIndex,
                newIndex: evt.newIndex,
                levelId: props.level.id
            });

            const list = childrenData.value || [];
            const rows = (Array.from(
                childrenBodyRef.value?.querySelectorAll(
                    `tr[data-depth="${depth}"]`
                ) ?? []
            ) || []) as HTMLElement[];
            const idsAfterDom = rows
                .map((r) => r.dataset.levelId)
                .filter(Boolean);

            console.log("onUpdate children start", {
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

            console.log("onUpdate children done", {
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
    <!-- 手機版：卡片式佈局 -->
    <template v-if="!desktopOnly">
        <div
            class="block md:hidden bg-white dark:bg-gray-900 rounded-lg border border-default shadow-sm mb-3"
            :data-depth="currentDepth"
            :data-level-id="level.id"
            :style="{ marginLeft: `${currentDepth * 16}px` }">
            <div class="p-4 space-y-3">
                <!-- 標題列 -->
                <div class="flex items-center gap-2">
                    <UIcon
                        name="i-lucide-grip-vertical"
                        :data-depth="currentDepth"
                        class="w-4 h-4 text-gray-500 drag-handle cursor-grab shrink-0" />
                    <button
                        v-if="hasChildren"
                        @click="toggleExpand"
                        class="flex items-center justify-center w-5 h-5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors shrink-0">
                        <UIcon
                            :name="
                                isExpanded
                                    ? 'i-lucide-chevron-down'
                                    : 'i-lucide-chevron-right'
                            "
                            class="w-4 h-4" />
                    </button>
                    <span v-else class="w-5 shrink-0"></span>
                    <span class="font-medium text-base flex-1">
                        {{ level.label }} ( {{ level.url }} )
                    </span>
                </div>

                <!-- 資訊欄位 -->
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div class="flex flex-col gap-1">
                        <span class="text-gray-500 dark:text-gray-400 text-xs">
                            是否上線
                        </span>
                        <UBadge
                            :label="
                                levelStatusLabelMap[level.status] ?? level.status
                            "
                            :color="level.status === '1' ? 'success' : 'error'"
                            size="sm" />
                    </div>
                </div>

                <!-- 操作按鈕 -->
                <div class="flex flex-wrap gap-2 pt-2 border-t border-default">
                    <UButton
                        icon="i-lucide-edit"
                        label="編輯"
                        color="primary"
                        size="xs"
                        variant="outline"
                        class="flex-1 min-w-[80px]"
                        @click="onEdit?.(level)" />
                    <UButton
                        icon="i-lucide-plus"
                        label="子層級"
                        color="primary"
                        size="xs"
                        variant="outline"
                        class="flex-1 min-w-[80px]"
                        :disabled="!canAddSub"
                        :title="!canAddSub ? '已有模組或 URL，無法新增子層級' : ''"
                        @click="onAddSub?.(level)" />
                    <UButton
                        icon="i-lucide-trash"
                        label="刪除"
                        color="error"
                        variant="ghost"
                        size="xs"
                        class="flex-1 min-w-[80px]"
                        @click="onDelete?.(level)" />
                </div>
            </div>
        </div>
        <!-- 子層級（手機版） -->
        <template v-if="hasChildren && isExpanded && !desktopOnly">
            <div class="block md:hidden">
                <TreeTableRow
                    v-for="child in childrenData"
                    :key="child.id"
                    :level="child"
                    :depth="currentDepth + 1"
                    mobile-only
                    :on-edit="onEdit"
                    :on-add-sub="onAddSub"
                    :on-update-sort-order="onUpdateSortOrder"
                    :on-delete="onDelete"
                    @refresh="emit('refresh')" />
            </div>
        </template>
    </template>

    <!-- 桌面版：表格行佈局 -->
    <template v-if="!mobileOnly">
        <tr
            class="hidden md:table-row hover:bg-gray-50 dark:hover:bg-gray-800/50"
            :data-depth="currentDepth"
            :data-level-id="level.id">
            <td class="py-2 px-4 border-b border-default">
                <div
                    class="flex items-center gap-2"
                    :style="{ paddingLeft: `${currentDepth * indentWidth}px` }">
                    <UIcon
                        name="i-lucide-grip-vertical"
                        :data-depth="currentDepth"
                        class="w-4 h-4 text-gray-500 drag-handle cursor-grab" />
                    <button
                        v-if="hasChildren"
                        @click="toggleExpand"
                        class="flex items-center justify-center w-5 h-5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                        <UIcon
                            :name="
                                isExpanded
                                    ? 'i-lucide-chevron-down'
                                    : 'i-lucide-chevron-right'
                            "
                            class="w-4 h-4" />
                    </button>
                    <span v-else class="w-5"></span>
                    <span class="font-medium">{{ level.label }}</span>
                </div>
            </td>
            <td class="py-2 px-4 border-b border-default">
                <span class="font-medium">{{ level.url }}</span>
            </td>
            <td class="py-2 px-4 border-b border-default">
                <span class="font-medium">{{ moduleName }}</span>
            </td>
            <td class="py-2 px-4 border-b border-default">
                <div class="flex items-center gap-2">
                    <UBadge
                        variant="outline"
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
                        :disabled="!canAddSub"
                        :title="!canAddSub ? '已有模組或 URL，無法新增子層級' : ''"
                        @click="onAddSub?.(level)" />
                    <UButton
                        icon="i-lucide-trash"
                        label="刪除"
                        color="error"
                        variant="ghost"
                        size="xs"
                        @click="onDelete?.(level)" />
                </div>
            </td>
        </tr>
        <!-- 子層級（桌面版） -->
        <template v-if="hasChildren && isExpanded && !mobileOnly">
            <tbody ref="childrenBodyRef" style="display: contents">
                <TreeTableRow
                    v-for="child in childrenData"
                    :key="child.id"
                    :level="child"
                    :depth="currentDepth + 1"
                    desktop-only
                    :on-edit="onEdit"
                    :on-add-sub="onAddSub"
                    :on-update-sort-order="onUpdateSortOrder"
                    :on-delete="onDelete"
                    @refresh="emit('refresh')" />
            </tbody>
        </template>
    </template>
</template>
