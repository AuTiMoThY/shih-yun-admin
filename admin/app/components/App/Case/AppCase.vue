<script setup lang="ts">
import type { TableColumn } from "@nuxt/ui";
import { h, resolveComponent } from "vue";
import { STATUS_LABEL_MAP } from "~/constants/system/status";
import { STATUS_ICON_MAP } from "~/constants/system/status_icon";

// 接收 structureId 和 url 作為 prop
const props = defineProps<{
    structureId?: number | null;
    url?: string | null;
}>();

const UButton = resolveComponent("UButton");
const UIcon = resolveComponent("UIcon");
const NuxtImg = resolveComponent("NuxtImg");
const { data, loading, fetchData, deleteCase } = useAppCase();

const route = useRoute();
const { resolvePath } = useStructureResolver();
const currentPath = route.path;
const pathInfo = resolvePath(currentPath);
const basePath = pathInfo.structure?.url || "/";

const deleteConfirmModalOpen = ref(false);
const deleteTarget = ref<{ id: string; title: string } | null>(null);
const columns: TableColumn<any>[] = [
    {
        accessorKey: "cover",
        header: "封面圖",
        cell: ({ row }) => {
            const cover = row.original.cover;
            return h(NuxtImg, {
                src: cover,
                alt: "封面圖",
                class: "w-25 aspect-square object-cover"
            });
        }
    },
    { accessorKey: "year", header: "年份" },
    { accessorKey: "title", header: "標題" },
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
                    to: props.structureId
                        ? `${basePath}/edit/${row.original.id}`
                        : `/news/edit/${row.original.id}`
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
    deleteTarget.value = { id: data.id, title: data.title };
    deleteConfirmModalOpen.value = true;
};

const confirmDelete = async () => {
    const id = deleteTarget.value?.id as number | string;
    if (!id) return;
    await deleteCase(id, {
        onSuccess: () => fetchData(props.structureId ?? null)
    });
    deleteConfirmModalOpen.value = false;
    deleteTarget.value = null;
};

onMounted(() => {
    fetchData(props.structureId ?? null);
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
