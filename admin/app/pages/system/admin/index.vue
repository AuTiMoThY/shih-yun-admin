<script setup lang="ts">
definePageMeta({
    middleware: "auth"
});
import type { TableColumn } from "@nuxt/ui";
import { h, resolveComponent } from "vue";
// 參考 Addadmin.vue 的權限設定，轉換顯示文字
import { PERMISSION_LABEL_MAP } from "~/constants/permissions";
import { STATUS_LABEL_MAP } from "~/constants/system/status";
import { STATUS_ICON_MAP } from "~/constants/system/status_icon";

const router = useRouter();
const { data, loading, fetchData, deleteAdmin } = useUsers();

const columns: TableColumn<any>[] = [
    { accessorKey: "username", header: "帳號" },
    { accessorKey: "name", header: "姓名" },
    {
        accessorKey: "roles",
        header: "角色",
        cell: ({ row }) => {
            const roles = row.original.roles || [];
            if (roles.length === 0) {
                return h("span", { class: "text-gray-400" }, "無角色");
            }
            return h(
                "div",
                { class: "flex flex-wrap gap-1" },
                roles.map((role: any) =>
                    h(
                        "span",
                        {
                            class:
                                "px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800"
                        },
                        role.label || role.name
                    )
                )
            );
        }
    },
    {
        accessorKey: "permission_name",
        header: "舊權限（相容）",
        cell: ({ row }) =>
            PERMISSION_LABEL_MAP[row.original.permission_name] ??
            row.original.permission_name ??
            "-"
    },
    {
        accessorKey: "status",
        header: "狀態",
        cell: ({ row }) => {
            const status = String(row.original.status);
            const label = STATUS_LABEL_MAP[status] ?? status;
            const icon = STATUS_ICON_MAP[status] ?? "i-lucide-help-circle";
            const UIcon = resolveComponent("UIcon");

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
        header: "操作",
        cell: ({ row }) => {
            const UButton = resolveComponent("UButton");
            return h("div", { class: "flex items-center gap-2" }, [
                h(UButton, {
                    icon: "i-lucide-edit",
                    label: "編輯",
                    color: "primary",
                    size: "xs",
                    onClick: () => editAdmin(row.original)
                }),
                h(UButton, {
                    icon: "i-lucide-trash",
                    label: "刪除",
                    color: "error",
                    variant: "ghost",
                    size: "xs",
                    onClick: () => deleteAdmin(row.original)
                })
            ]);
        }
    }
];

const editAdmin = (admin: any) => {
    router.push(`/system/admins/edit/${admin.id}`);
};

onMounted(() => {
    fetchData();
});
</script>

<template>
    <UDashboardPanel id="admins">
        <template #header>
            <UDashboardNavbar title="管理員設定" :ui="{ right: 'gap-3' }">
                <template #leading>
                    <UDashboardSidebarCollapse />
                </template>
            </UDashboardNavbar>

            <UDashboardToolbar>
                <template #right>
                    <UButton
                        color="primary"
                        variant="outline"
                        icon="lucide:plus"
                        label="新增管理員"
                        to="/system/admins/add" />
                </template>
            </UDashboardToolbar>
        </template>
        <template #body>
            <UTable
                class="shrink-0"
                :data="data"
                :columns="columns"
                :loading="loading"
                :ui="{
                    base: 'table-fixed border-separate border-spacing-0',
                    thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
                    tbody: '[&>tr]:last:[&>td]:border-b-0',
                    th: 'py-2 first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r',
                    td: 'border-b border-default',
                    separator: 'h-0'
                }" />
        </template>
    </UDashboardPanel>
</template>
