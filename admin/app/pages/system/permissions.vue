<script setup lang="ts">
definePageMeta({
    middleware: ["auth", "permission"]
});
import { useSortable } from "@vueuse/integrations/useSortable";
import { STATUS_LABEL_MAP } from "~/constants/system/status";
import { STATUS_ICON_MAP } from "~/constants/system/status_icon";

const UButton = resolveComponent("UButton");
const UIcon = resolveComponent("UIcon");
const {
    data: allPermissions,
    loading,
    fetchData,
    deletePermission,
    updateSortOrder
} = usePermissionData();
const { data: structureData, fetchData: fetchStructures } = useStructure();
const { data: moduleData, fetchData: fetchModules } = useModule();
const addPermissionModalOpen = ref(false);
const editPermissionModalOpen = ref(false);
const editData = ref<any>(null);
const selectedStructureId = ref<number | null>(null);
const deleteConfirmModalOpen = ref(false);
const deleteTarget = ref<{ id: number | string; label: string } | null>(null);
const tableBodyRef = ref<HTMLElement | null>(null);
const sortableData = ref<any[]>([]);
let sortableStop: (() => void) | null = null;

// 扁平化單元樹狀結構，用於下拉選單
const flattenStructures = (items: any[]): any[] => {
    const result: any[] = [];
    const traverse = (nodes: any[]) => {
        for (const node of nodes) {
            if (node && node.id) {
                // 只顯示有 URL 的單元（表示是實際的單元，而非分類）
                if (node.url) {
                    result.push({
                        id: node.id,
                        label: node.label,
                        url: node.url
                    });
                }
                // 遞迴處理子節點
                if (node.children && Array.isArray(node.children)) {
                    traverse(node.children);
                }
            }
        }
    };
    traverse(items);
    return result;
};

const structureList = computed(() =>
    flattenStructures(structureData.value || [])
);

// 根據選中的單元篩選權限
const data = computed(() => {
    if (!selectedStructureId.value) {
        return allPermissions.value || [];
    }

    const selectedStructure = structureList.value.find(
        (s: any) => s.id === selectedStructureId.value
    );

    if (!selectedStructure || !selectedStructure.url) {
        return allPermissions.value || [];
    }

    // 根據單元的 URL 篩選權限（權限名稱以 {url}. 開頭）
    const urlPrefix = `${selectedStructure.url}.`;
    return (allPermissions.value || []).filter(
        (p: any) => p.name && p.name.startsWith(urlPrefix)
    );
});

const structureLabel = (item: any) => {
    if (!item.name) {
        return "-";
    }
    // 從權限名稱中提取 URL 前綴（例如：about.view -> about）
    const urlPrefix = item.name.split(".")[0];
    const structure = structureList.value.find((s: any) => s.url === urlPrefix);
    return structure?.label ?? "-";
};

const addPermission = () => {
    addPermissionModalOpen.value = true;
};

const editPermission = async (data: any) => {
    const permissionData = await usePermissionData().fetchById(data.id);
    if (permissionData) {
        editData.value = permissionData;
        editPermissionModalOpen.value = true;
    }
};

const handleDelete = async (data: any) => {
    deleteTarget.value = { id: Number(data.id), label: data.label };
    deleteConfirmModalOpen.value = true;
};

const confirmDelete = async () => {
    await deletePermission(deleteTarget.value?.id as number, {
        onSuccess: async () => {
            await fetchData();
            await nextTick();
            setupSortable();
        }
    });
    deleteConfirmModalOpen.value = false;
    deleteTarget.value = null;
};

const handleStructureFilter = async () => {
    // 重新載入所有權限資料（篩選邏輯在 computed 中處理）
    await fetchData();
    await nextTick();
    setupSortable();
};

const setupSortable = () => {
    // 清理舊的實例
    if (sortableStop) {
        sortableStop();
        sortableStop = null;
    }

    if (!tableBodyRef.value) {
        return;
    }

    // 同步資料到 sortableData
    sortableData.value = [...data.value];

    const { stop } = useSortable(tableBodyRef, sortableData, {
        handle: ".drag-handle",
        animation: 150,
        draggable: "tr",
        fallbackOnBody: true,
        swapThreshold: 0.65,
        onUpdate: async (evt: any) => {
            console.log("onUpdate", evt);
            const list = sortableData.value || [];
            const rows = (Array.from(
                tableBodyRef.value?.querySelectorAll(
                    "tr[data-permission-id]"
                ) ?? []
            ) || []) as HTMLElement[];
            const idsAfterDom = rows
                .map((r) => r.dataset.permissionId)
                .filter(Boolean);
            console.log("onUpdate start", {
                oldIndex: evt.oldIndex,
                newIndex: evt.newIndex,
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
                console.warn("[permission-sort] reorder mismatch, refetch", {
                    idsAfterDom,
                    listIds: list.map((x) => x?.id)
                });
                await fetchData();
                await nextTick();
                setupSortable();
                return;
            }

            sortableData.value = [...newList];
            await updateSortOrder(sortableData.value);
            console.log("onUpdate done", {
                movedId: evt.item?.dataset?.permissionId,
                idsAfter: sortableData.value.map((x) => x?.id)
            });
        }
    });
    sortableStop = stop;
};

// 組件卸載時清理
onUnmounted(() => {
    if (sortableStop) {
        sortableStop();
        sortableStop = null;
    }
});

// 監聽 data 變化，同步到 sortableData（用於排序）
watch(
    data,
    (newData) => {
        sortableData.value = [...newData];
    },
    { immediate: true }
);

onMounted(async () => {
    await fetchModules();
    await fetchStructures();
    await fetchData();
    await nextTick();
    setupSortable();
});
</script>
<template>
    <UDashboardPanel>
        <template #header>
            <UDashboardNavbar
                title="權限設定"
                :ui="{ right: 'gap-3', title: 'text-primary' }">
                <template #leading>
                    <UDashboardSidebarCollapse />
                </template>
                <template #right>
                    <UButton
                        label="新增權限"
                        color="primary"
                        icon="lucide:plus"
                        @click="addPermission" />
                </template>
            </UDashboardNavbar>
            <UDashboardToolbar>
                <template #left>
                    <USelect
                        v-model="selectedStructureId"
                        :items="[
                            { label: '全部單元', value: null },
                            ...structureList.map((s: any) => ({ 
                                label: s.label, 
                                value: s.id 
                            }))
                        ]"
                        placeholder="篩選單元"
                        @change="handleStructureFilter"
                        class="w-48" />
                </template>
            </UDashboardToolbar>
        </template>
        <template #body>
            <div v-if="loading" class="flex items-center justify-center py-12">
                <UIcon name="i-lucide-loader-2" class="w-6 h-6 animate-spin" />
            </div>
            <div v-else class="overflow-x-auto">
                <table
                    class="w-full table-fixed border-separate border-spacing-0 text-sm">
                    <thead>
                        <tr class="bg-elevated/50">
                            <th
                                class="w-[40px] py-2 px-4 text-left first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r"></th>
                            <th
                                class="py-2 px-4 text-left first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r">
                                權限名稱
                            </th>
                            <th
                                class="py-2 px-4 text-left first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r">
                                權限代碼
                            </th>
                            <th
                                class="py-2 px-4 text-left first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r">
                                單元
                            </th>
                            <th
                                class="py-2 px-4 text-left first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r">
                                狀態
                            </th>
                            <th
                                class="py-2 px-4 text-left first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r">
                                操作
                            </th>
                        </tr>
                    </thead>
                    <tbody ref="tableBodyRef">
                        <template
                            v-for="(item, idx) in sortableData"
                            :key="item?.id ?? `permission-${idx}`">
                            <tr
                                v-if="item"
                                :data-permission-id="item.id"
                                class="hover:bg-elevated/30">
                                <td class="py-2 px-4 border-b border-default">
                                    <UIcon
                                        name="i-lucide-grip-vertical"
                                        class="w-4 h-4 cursor-grab text-gray-400 hover:text-gray-600 drag-handle" />
                                </td>
                                <td class="py-2 px-4 border-b border-default">
                                    {{ item.label }}
                                </td>
                                <td class="py-2 px-4 border-b border-default">
                                    {{ item.name }}
                                </td>
                                <td class="py-2 px-4 border-b border-default">
                                    <template v-if="item.name">
                                        {{ structureLabel(item) }}
                                    </template>
                                    <template v-else>-</template>
                                </td>
                                <td class="py-2 px-4 border-b border-default">
                                    <DataTableStatus :status="item.status" />
                                </td>
                                <td class="py-2 px-4 border-b border-default">
                                    <div class="flex items-center gap-2">
                                        <UButton
                                            icon="i-lucide-edit"
                                            label="編輯"
                                            color="primary"
                                            size="xs"
                                            @click="editPermission(item)" />
                                        <UButton
                                            icon="i-lucide-trash"
                                            label="刪除"
                                            color="error"
                                            variant="ghost"
                                            size="xs"
                                            @click="handleDelete(item)" />
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <div
                    v-if="!loading && sortableData.length === 0"
                    class="flex items-center justify-center py-12 text-gray-500">
                    暫無資料
                </div>
            </div>
        </template>
        <template #footer>
            <PageFooter />
        </template>
    </UDashboardPanel>
    <PermissionFrmModal
        v-model:open="addPermissionModalOpen"
        mode="add"
        @added="
            async () => {
                await fetchData();
                await nextTick();
                setupSortable();
            }
        " />
    <PermissionFrmModal
        v-model:open="editPermissionModalOpen"
        mode="edit"
        :data="editData"
        @updated="
            async () => {
                await fetchData();
                await nextTick();
                setupSortable();
            }
        " />
    <DeleteConfirmModal
        v-model:open="deleteConfirmModalOpen"
        title="確認刪除"
        :description="
            deleteTarget
                ? `確定要刪除「${deleteTarget.label}」嗎？此操作無法復原，「${deleteTarget.label}」將會被永久刪除。`
                : ''
        "
        :on-confirm="confirmDelete" />
</template>
