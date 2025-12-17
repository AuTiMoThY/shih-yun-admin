<script setup lang="ts">
import type { TableColumn } from "@nuxt/ui";
import { h, resolveComponent } from "vue";
import { STATUS_LABEL_MAP } from "~/constants/system/status";
import { STATUS_ICON_MAP } from "~/constants/system/status_icon";

definePageMeta({
    middleware: "auth"
});

const { data, loading, fetchData } = useAppNews();

const columns: TableColumn<any>[] = [
    {
        accessorKey: "cover",
        header: "封面圖",
        cell: ({ row }) => {
            const cover = row.original.cover;
            return h("NuxtImg", {
                src: cover,
                alt: "封面圖",
                class: "w-10 h-10 object-cover"
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
                h(resolveComponent("UIcon"), { name: icon }),
                h("span", label)
            ]);
        }
    },
    {
        accessorKey: "action",
        header: "操作",
        cell: ({ row }) => {
            const UButton = resolveComponent("UButton");
            return h("div", { class: "flex items-center gap-2" }, [
                h(UButton, {
                    icon: "i-lucide-edit",
                    label: "編輯",
                    color: "primary",
                    size: "xs"
                    // onClick: () => editNews(row.original)
                }),
                h(UButton, {
                    icon: "i-lucide-trash",
                    label: "刪除",
                    color: "error",
                    variant: "ghost",
                    size: "xs"
                    // onClick: () => deleteNews(row.original)
                })
            ]);
        }
    }
];
</script>
<template>
    <UDashboardPanel>
        <template #header>
            <UDashboardNavbar
                title="最新消息"
                :ui="{ right: 'gap-3', title: 'text-primary' }">
                <template #leading>
                    <UDashboardSidebarCollapse color="primary" />
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
</template>
