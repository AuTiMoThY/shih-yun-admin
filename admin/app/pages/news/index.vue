<script setup lang="ts">
import type { TableColumn } from "@nuxt/ui";
import { h, resolveComponent } from "vue";
import { STATUS_LABEL_MAP } from "~/constants/system/status";
import { STATUS_ICON_MAP } from "~/constants/system/status_icon";

definePageMeta({
    middleware: "auth"
});

const UButton = resolveComponent("UButton");
const UIcon = resolveComponent("UIcon");
const NuxtImg = resolveComponent("NuxtImg");
const { data, loading, fetchData, deleteNews } = useAppNews();

const deleteConfirmModalOpen = ref(false);
const deleteTarget = ref<{ id: string; title: string } | null>(null);
const columns: TableColumn<any>[] = [
    {
        accessorKey: "cover",
        header: "封面圖",
        cell: ({ row }) => {
            const cover = row.original.cover;
            // console.log("cover", cover);
            return h(NuxtImg, {
                src: cover,
                alt: "封面圖",
                class: "w-25 aspect-square object-cover"
            });
        }
    },
    { accessorKey: "title", header: "標題" },
    { accessorKey: "show_date", header: "日期" },
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
                    to: `/news/edit/${row.original.id}`
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
}

const confirmDelete = async () => {
    await deleteNews(deleteTarget.value?.id as number, {
        onSuccess: () => fetchData()
    });
    deleteConfirmModalOpen.value = false;
    deleteTarget.value = null;
}

onMounted(() => {
    fetchData();
});
</script>
<template>
    <UDashboardPanel>
        <template #header>
            <UDashboardNavbar
                title="最新消息"
                :ui="{ right: 'gap-3', title: 'text-primary' }">
                <template #leading>
                    <UDashboardSidebarCollapse />
                </template>
                <template #right>
                    <UButton
                        label="新增最新消息"
                        color="primary"
                        icon="i-lucide-plus"
                        to="/news/add" />
                </template>
            </UDashboardNavbar>
        </template>
        <template #body>
            <DataTable :data="data" :columns="columns" :loading="loading" />
        </template>
        <template #footer>
            <PageFooter />
        </template>
    </UDashboardPanel>
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
