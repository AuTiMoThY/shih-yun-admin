<script setup lang="ts">
import type { TableColumn } from "@nuxt/ui";
import { h, resolveComponent } from "vue";
import { STATUS_LABEL_MAP } from "~/constants/system/status";
import { STATUS_ICON_MAP } from "~/constants/system/status_icon";

// 接收 structureId、caseId 和 url 作為 prop
const props = defineProps<{
    structureId?: number | null;
    caseId?: number | null;
    url?: string | null;
}>();

const UButton = resolveComponent("UButton");
const UIcon = resolveComponent("UIcon");
const { data, loading, fetchData, deleteProgress } = useAppProgress();
const { data: caseData, fetchData: fetchCaseData } = useAppCase();

const route = useRoute();
const { resolvePath } = useStructureResolver();
const currentPath = route.path;
const pathInfo = resolvePath(currentPath);
const basePath = pathInfo.structure?.url || "/progress";

const deleteConfirmModalOpen = ref(false);
const deleteTarget = ref<{ id: string; title: string } | null>(null);

// 建案名稱映射表
const caseNameMap = computed(() => {
    const map = new Map<number, string>();
    if (caseData.value && Array.isArray(caseData.value)) {
        caseData.value.forEach((caseItem: any) => {
            if (caseItem.id && caseItem.title) {
                map.set(Number(caseItem.id), caseItem.title);
            }
        });
    }
    return map;
});

// 獲取建案名稱
const getCaseName = (caseId: number | null | undefined): string => {
    if (!caseId) return "-";
    return caseNameMap.value.get(Number(caseId)) || "-";
};

const columns: TableColumn<any>[] = [
    { accessorKey: "title", header: "標題" },
    {
        accessorKey: "case_id",
        header: "所屬建案名稱",
        cell: ({ row }) => {
            const caseId = row.original.case_id;
            return h("span", getCaseName(caseId));
        }
    },
    {
        accessorKey: "progress_date",
        header: "日期",
        cell: ({ row }) => {
            const date = row.original.progress_date;
            return date || "-";
        }
    },
    {
        accessorKey: "status",
        header: "狀態",
        cell: ({ row }) => {
            const status = String(row.original.status);
            const label = STATUS_LABEL_MAP[status] ?? status;
            const icon = STATUS_ICON_MAP[status] ?? "i-lucide-help-circle";
            return h("div", { class: "flex items-center gap-2" }, [
                h(UIcon, {
                    name: icon,
                    class: status === "1" ? "text-emerald-500" : "text-rose-500"
                }),
                h("span", label)
            ]);
        }
    },
    {
        accessorKey: "action",
        header: "操作",
        cell: ({ row }) => {
            return h("div", { class: "flex items-center gap-2" }, [
                h(UButton, {
                    icon: "i-lucide-edit",
                    label: "編輯",
                    color: "primary",
                    size: "xs",
                    to: props.structureId ? `${basePath}/edit/${row.original.id}` : `/progress/edit/${row.original.id}`,
                }),
                h(UButton, {
                    icon: "i-lucide-trash",
                    label: "刪除",
                    color: "error",
                    variant: "ghost",
                    size: "xs",
                    onClick: () => handleDelete(row.original)
                })
            ]);
        }
    }
];

const handleDelete = async (data: any) => {
    deleteTarget.value = { id: data.id, title: data.title || "此筆工程進度" };
    deleteConfirmModalOpen.value = true;
};

const confirmDelete = async () => {
    const id = deleteTarget.value?.id as number | string;
    if (!id) return;
    await deleteProgress(id, {
        onSuccess: () => fetchData(props.caseId ?? undefined)
    });
    deleteConfirmModalOpen.value = false;
    deleteTarget.value = null;
};

// 監聽 caseId 變化，重新載入資料
watch(
    () => props.caseId,
    (newCaseId) => {
        fetchData(newCaseId ?? undefined);
    },
    { immediate: false }
);

onMounted(async () => {
    // 先獲取建案列表
    await fetchCaseData();
    // 再獲取工程進度列表（使用 caseId 進行篩選）
    await fetchData(props.caseId ?? undefined);
});

// 暴露方法和狀態給父組件使用（用於 header 按鈕）
defineExpose({
    loading
});
</script>

<template>
    <div>
        <DataTable :data="data" :columns="columns" :loading="loading" />
    </div>
    <DeleteConfirmModal
        v-model:open="deleteConfirmModalOpen"
        title="確認刪除"
        :description="
            deleteTarget
                ? `確定要刪除「${deleteTarget.title}」嗎？此操作無法復原，「${deleteTarget.title}」將會被永久刪除。`
                : ''
        "
        :on-confirm="confirmDelete" />
</template>

