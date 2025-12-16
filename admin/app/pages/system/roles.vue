<script setup lang="ts">
definePageMeta({
    middleware: "auth"
});
import type { TableColumn } from "@nuxt/ui";
import { STATUS_LABEL_MAP } from "~/constants/system/status";
import { STATUS_ICON_MAP } from "~/constants/system/status_icon";

const { data, loading, fetchData, deleteRole } = useRole();
const { data: permissionData, fetchData: fetchPermissions } =
    usePermissionData();
const addRoleModalOpen = ref(false);
const editRoleModalOpen = ref(false);
const editData = ref<any>(null);

const columns: TableColumn<any>[] = [
    { accessorKey: "label", header: "角色名稱" },
    { accessorKey: "name", header: "角色代碼" },
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
                    onClick: () => editRole(row.original)
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

const addRole = () => {
    addRoleModalOpen.value = true;
};

const editRole = async (data: any) => {
    const roleData = await useRole().fetchById(data.id);
    console.log("roleData", roleData);
    if (roleData) {
        editData.value = roleData;
        editRoleModalOpen.value = true;
    }
};

const handleDelete = async (data: any) => {
    await deleteRole({
        id: data.id,
        onSuccess: () => fetchData()
    });
};

onMounted(async () => {
    await fetchData();
    await fetchPermissions();

    console.log("data", data.value);
});
</script>
<template>
    <UDashboardPanel>
        <template #header>
            <UDashboardNavbar title="角色設定" :ui="{ right: 'gap-3' }">
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
                        label="新增角色"
                        @click="addRole" />
                </template>
            </UDashboardToolbar>
        </template>
        <template #body>
            <UTable
                ref="table"
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
    <RoleFrmModal
        v-model:open="addRoleModalOpen"
        mode="add"
        @added="fetchData" />
    <RoleFrmModal
        v-model:open="editRoleModalOpen"
        mode="edit"
        :data="editData"
        @updated="fetchData" />
</template>
