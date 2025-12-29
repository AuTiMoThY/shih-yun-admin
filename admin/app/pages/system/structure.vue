<script setup lang="ts">
definePageMeta({
    middleware: "auth"
});

import { useSortable } from "@vueuse/integrations/useSortable";
import StructureLevelModal from "~/components/Structure/LevelModal.vue";
import StructureTreeTableRow from "~/components/Structure/TreeTableRow.vue";

const { data, loading, fetchData, updateSortOrder, deleteLevel } =
    useStructure();
const rootBodyRef = ref<HTMLElement | null>(null);
let sortableStop: (() => void) | null = null;

// Modal 狀態
const addRootModalOpen = ref(false);
const addSubLevelModalOpen = ref(false);
const editModalOpen = ref(false);
const deleteConfirmModalOpen = ref(false);
const deleteTarget = ref<{ id: string; label: string } | null>(null);
// 當前操作的層級資料
const currentParentLevel = ref<any>(null);
const currentEditLevel = ref<any>(null);
const isExpanded = ref(true);
const rootLevels = computed(() => (data.value || []).filter(Boolean));

const handleModalSuccess = async () => {
    await fetchData();
    // 注意：側邊欄選單會由 composable 自動更新（在 addLevel/updateLevel 成功後）
    await nextTick();
    setupRootSortable();
};


const handleDelete = (level: any) => {
    console.log("handleDelete", level);

    deleteTarget.value = { id: level.id, label: level.label };
    deleteConfirmModalOpen.value = true;
};

const confirmDelete = async () => {
    console.log("confirmDelete", deleteTarget.value);
    await deleteLevel(deleteTarget.value, {
        onSuccess: async () => {
            await fetchData();
            // 注意：側邊欄選單會由 composable 自動更新（在 deleteLevel 成功後）
            await nextTick();
            setupRootSortable();
        }
    });
    deleteConfirmModalOpen.value = false;
    deleteTarget.value = null;
};

const handleEdit = (level: any) => {
    console.log("handleEdit", level);
    currentEditLevel.value = level;
    editModalOpen.value = true;
};

const handleAddSub = (level: any) => {
    console.log("handleAddSub", level);
    if (level?.module_id) {
        return;
    }
    currentParentLevel.value = level;
    addSubLevelModalOpen.value = true;
};

const setupRootSortable = () => {
    // 清理舊的實例
    if (sortableStop) {
        sortableStop();
        sortableStop = null;
    }

    if (!rootBodyRef.value) {
        return;
    }

    const { stop } = useSortable(rootBodyRef, data, {
        // group: "nested",
        handle: "tr[data-depth='0'] .drag-handle",
        animation: 150,
        draggable: "tr[data-depth='0']",
        fallbackOnBody: true,
        swapThreshold: 0.65,
        onStart: function (evt: any) {
            console.log("onStart", evt);
            // 拖曳開始時收合當前層級
            isExpanded.value = false;
        },
        onUpdate: async (evt: any) => {

            console.log("onUpdate", evt);
            const list = data.value || [];
            const rows = (Array.from(
                rootBodyRef.value?.querySelectorAll("tr[data-depth='0']") ?? []
            ) || []) as HTMLElement[];
            const idsAfterDom = rows
                .map((r) => r.dataset.levelId)
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
                console.warn("[structure-sort] reorder mismatch, refetch", {
                    idsAfterDom,
                    listIds: list.map((x) => x?.id)
                });
                await fetchData();
                await nextTick();
                setupRootSortable();
                return;
            }

            data.value = [...newList];
            await updateSortOrder(data.value);
            // 注意：側邊欄選單會由 composable 自動更新（在 updateSortOrder 成功後）
            console.log("onUpdate done", {
                movedId: evt.item?.dataset?.levelId,
                idsAfter: data.value.map((x) => x?.id)
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

onMounted(async () => {
    await fetchData();
    await nextTick();
    setupRootSortable();
});
</script>

<template>
    <UDashboardPanel>
        <template #header>
            <UDashboardNavbar
                title="管理系統架構"
                :ui="{ right: 'gap-3', title: 'text-primary' }">
                <template #leading>
                    <UDashboardSidebarCollapse />
                </template>
                <template #right>
                    <UButton
                        label="新增層級1"
                        color="primary"
                        icon="lucide:plus"
                        @click="addRootModalOpen = true" />
                </template>
            </UDashboardNavbar>
        </template>
        <template #body>
            <div v-if="loading" class="flex items-center justify-center py-12">
                <UIcon name="i-lucide-loader-2" class="w-6 h-6 animate-spin" />
            </div>
            <div v-else>
                <!-- 手機版：卡片式佈局 -->
                <div class="block md:hidden space-y-3">
                    <template
                        v-for="(level, idx) in rootLevels"
                        :key="level?.id ?? `root-${idx}`">
                        <StructureTreeTableRow
                            v-if="level"
                            :level="level"
                            :depth="0"
                            :is-expanded="isExpanded"
                            :on-edit="handleEdit"
                            :on-add-sub="handleAddSub"
                            :on-update-sort-order="updateSortOrder"
                            :on-delete="handleDelete"
                            @refresh="fetchData" />
                    </template>
                    <div
                        v-if="!loading && data.length === 0"
                        class="flex items-center justify-center py-12 text-gray-500">
                        暫無資料
                    </div>
                </div>
                <!-- 桌面版：表格佈局 -->
                <div class="hidden md:block overflow-x-auto">
                    <table
                        class="w-full table-fixed border-separate border-spacing-0 text-sm">
                        <thead>
                            <tr class="bg-elevated/50">
                                <th
                                    class="py-2 px-4 text-left first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r">
                                    名稱
                                </th>
                                <th
                                    class="w-[120px] py-2 px-4 text-left first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r">
                                    URL
                                </th>
                                <th
                                    class="w-[180px] py-2 px-4 text-left first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r">
                                    模組名稱
                                </th>
                                <th
                                    class="w-[120px] py-2 px-4 text-left border-y border-default first:border-l last:border-r">
                                    是否上線
                                </th>
                                <th
                                    class="py-2 px-4 text-left border-y border-default first:border-l last:border-r">
                                    操作
                                </th>
                            </tr>
                        </thead>
                        <tbody ref="rootBodyRef">
                            <template
                                v-for="(level, idx) in rootLevels"
                                :key="level?.id ?? `root-${idx}`">
                                <StructureTreeTableRow
                                    v-if="level"
                                    :level="level"
                                    :depth="0"
                                    :is-expanded="isExpanded"
                                    :on-edit="handleEdit"
                                    :on-add-sub="handleAddSub"
                                    :on-update-sort-order="updateSortOrder"
                                    :on-delete="handleDelete"
                                    @refresh="fetchData" />
                            </template>
                        </tbody>
                    </table>
                    <div
                        v-if="!loading && data.length === 0"
                        class="flex items-center justify-center py-12 text-gray-500">
                        暫無資料
                    </div>
                </div>
            </div>
        </template>
        <template #footer>
            <PageFooter />
        </template>
    </UDashboardPanel>

    <!-- 新增層級1 Modal -->
    <StructureLevelModal
        v-model:open="addRootModalOpen"
        mode="add-root"
        @added="handleModalSuccess" />

    <!-- 新增子層級 Modal -->
    <StructureLevelModal
        v-model:open="addSubLevelModalOpen"
        mode="add-sub"
        :parent-id="
            currentParentLevel?.id ? parseInt(currentParentLevel.id) : 0
        "
        :parent-name="currentParentLevel?.label"
        @added="handleModalSuccess" />

    <!-- 編輯層級 Modal -->
    <StructureLevelModal
        v-model:open="editModalOpen"
        mode="edit"
        :level="currentEditLevel"
        @updated="handleModalSuccess" />

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
