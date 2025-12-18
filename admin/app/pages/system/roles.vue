<script setup lang="ts">
definePageMeta({
    middleware: "auth"
});
import type { TableColumn } from "@nuxt/ui";
import { STATUS_LABEL_MAP } from "~/constants/system/status";
import { STATUS_ICON_MAP } from "~/constants/system/status_icon";

const UButton = resolveComponent("UButton");
const UIcon = resolveComponent("UIcon");
const deleteConfirmModalOpen = ref(false);
const deleteTarget = ref<{ id: number | string; label: string } | null>(null);
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
    deleteTarget.value = { id: Number(data.id), label: data.label };
    deleteConfirmModalOpen.value = true;
};
const confirmDelete = async () => {
    await deleteRole(deleteTarget.value?.id as number, {
        onSuccess: () => fetchData()
    });
    deleteConfirmModalOpen.value = false;
    deleteTarget.value = null;
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
            <UDashboardNavbar
                title="角色設定"
                :ui="{ right: 'gap-3', title: 'text-primary' }">
                <template #leading>
                    <UDashboardSidebarCollapse />
                </template>
            </UDashboardNavbar>
            <UDashboardToolbar>
                <template #right>
                    <UButton
                        label="新增角色"
                        color="primary"
                        icon="lucide:plus"
                        @click="addRole" />
                </template>
            </UDashboardToolbar>
        </template>
        <template #body>
            <DataTable :data="data" :columns="columns" :loading="loading" />
        </template>
        <template #footer>
            <PageFooter />
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
