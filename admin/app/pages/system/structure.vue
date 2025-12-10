<script setup lang="ts">
definePageMeta({
    middleware: "auth"
});
import { computed, nextTick, onMounted, ref, shallowRef } from "vue";
import { useSortable } from "@vueuse/integrations/useSortable";
import StructureAddSubLevel from "~/components/Structure/AddSubLevel.vue";
import StructureTreeTableRow from "~/components/Structure/TreeTableRow.vue";

const data = shallowRef<any[]>([]);
const loading = ref(false);
const toast = useToast();
const addSubLevelModalOpen = ref(false);
const { public: runtimePublic } = useRuntimeConfig();
const apiBase = runtimePublic.apiBase;
const rootBodyRef = ref<HTMLElement | null>(null);
const currentParentLevel = ref<any>(null);

const editLevel = (level: any) => {
    console.log(typeof level.id);
};

const addSubLevel = (level: any) => {
    console.log(level);

    currentParentLevel.value = level;
    addSubLevelModalOpen.value = true;
};

const debugLog = (...args: any[]) => {
    // 集中控管排序相關除錯訊息
    console.log("[structure-sort]", ...args);
};

const rootLevels = computed(() => (data.value || []).filter(Boolean));

const fetchData = async () => {
    loading.value = true;
    const res = await $fetch<{
        success: boolean;
        data: any[];
        message?: string;
    }>(`${apiBase}/api/structure/get?tree=1`, {
        method: "GET"
    });
    if (res?.success) {
        data.value = (res.data || []).filter(Boolean);
        debugLog("fetchData success", {
            count: data.value.length,
            ids: data.value.map((x) => x?.id)
        });
    } else {
        console.error(res.message);
        toast.add({ title: res.message, color: "error" });
    }
    loading.value = false;
};

const updateSortOrder = async (list: any[]) => {
    const payload = (list || []).map((item, index) => ({
        id: item?.id,
        sort_order: index
    }));
    debugLog("updateSortOrder payload", payload);
    const res = await $fetch<{
        success: boolean;
        message?: string;
    }>(`${apiBase}/api/structure/update-sort-order`, {
        method: "POST",
        body: payload
    });
    if (res?.success) {
        toast.add({ title: "排序已更新", color: "success" });
    } else {
        console.error(res.message);
        toast.add({ title: res.message, color: "error" });
    }
};

const setupRootSortable = () => {
    useSortable(rootBodyRef, data, {
        group: "nested",
        handle: "tr[data-depth='0']",
        animation: 150,
        draggable: "tr[data-depth='0']",
        fallbackOnBody: true,
		swapThreshold: 0.65,
        onEnd: async (evt: any) => {
            const list = data.value || [];
            const rows = (
                Array.from(
                    rootBodyRef.value?.querySelectorAll(
                        "tr[data-depth='0']"
                    ) ?? []
                ) || []
            ) as HTMLElement[];
            const idsAfterDom = rows
                .map((r) => r.dataset.levelId)
                .filter(Boolean);
            debugLog("onEnd start", {
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
            // updateSortOrder(data.value);
            debugLog("onEnd done", {
                movedId: evt.item?.dataset?.levelId,
                idsAfter: data.value.map((x) => x?.id)
            });
        }
    });
};


onMounted(async () => {
    await fetchData();
    await nextTick();
    setupRootSortable();
});
</script>

<template>
    <UDashboardPanel>
        <template #header>
            <UDashboardNavbar title="管理系統架構" :ui="{ right: 'gap-3' }">
                <template #leading>
                    <UDashboardSidebarCollapse />
                </template>
            </UDashboardNavbar>
            <UDashboardToolbar>
                <template #right>
                    <StructureAddlevel @added="fetchData" />
                </template>
            </UDashboardToolbar>
        </template>
        <template #body>
            <div v-if="loading" class="flex items-center justify-center py-12">
                <UIcon name="i-lucide-loader-2" class="w-6 h-6 animate-spin" />
            </div>
            <div v-else class="overflow-x-auto">
                <table
                    class="w-full table-fixed border-separate border-spacing-0">
                    <thead>
                        <tr class="bg-elevated/50">
                            <th
                                class="py-2 px-4 text-left first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r font-semibold">
                                名稱
                            </th>
                            <th
                                class="py-2 px-4 text-left border-y border-default first:border-l last:border-r font-semibold">
                                前台顯示
                            </th>
                            <th
                                class="py-2 px-4 text-left border-y border-default first:border-l last:border-r font-semibold">
                                後台顯示
                            </th>
                            <th
                                class="py-2 px-4 text-left border-y border-default first:border-l last:border-r font-semibold">
                                是否上線
                            </th>
                            <th
                                class="py-2 px-4 text-left border-y border-default first:border-l last:border-r font-semibold">
                                操作
                            </th>
                        </tr>
                    </thead>
                    <tbody ref="rootBodyRef" class="nested-0">
                        <template
                            v-for="(level, idx) in rootLevels"
                            :key="level?.id ?? `root-${idx}`">
                            <StructureTreeTableRow
                                v-if="level"
                                :level="level"
                                :depth="0"
                                :on-edit="editLevel"
                                :on-add-sub="addSubLevel"
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
        </template>
    </UDashboardPanel>

    <StructureAddSubLevel
        v-model:open="addSubLevelModalOpen"
        :parent-id="
            currentParentLevel?.id ? parseInt(currentParentLevel.id) : 0
        "
        :parent-name="currentParentLevel?.name"
        @added="fetchData" />
</template>
