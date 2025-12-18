<script setup lang="ts">
definePageMeta({
    middleware: "auth"
});
import type { TableColumn } from "@nuxt/ui";
import { STATUS_LABEL_MAP } from "~/constants/system/status";
import { STATUS_ICON_MAP } from "~/constants/system/status_icon";

const UButton = resolveComponent("UButton");
const UIcon = resolveComponent("UIcon");
const { data, loading, fetchData, deletePermission } = usePermissionData();
const { data: moduleData, fetchData: fetchModules } = useModule();
const addPermissionModalOpen = ref(false);
const editPermissionModalOpen = ref(false);
const editData = ref<any>(null);
const selectedModuleId = ref<number | null>(null);
const deleteConfirmModalOpen = ref(false);
const deleteTarget = ref<{ id: number | string; label: string } | null>(null);
const columns: TableColumn<any>[] = [
    { accessorKey: "label", header: "權限名稱" },
    { accessorKey: "name", header: "權限代碼" },
    {
        accessorKey: "module_id",
        header: "模組",
        cell: ({ row }) => {
            const moduleId = row.original.module_id;
            if (!moduleId) return "-";
            const module = moduleData.value.find((m: any) => m.id === moduleId);
            return module ? module.label : "-";
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
        header: "操作",
        cell: ({ row }) => {
            return h("div", { class: "flex items-center gap-2" }, [
                h(UButton, {
                    icon: "i-lucide-edit",
                    label: "編輯",
                    color: "primary",
                    size: "xs",
                    onClick: () => editPermission(row.original)
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
        onSuccess: () => fetchData()
    });
    deleteConfirmModalOpen.value = false;
    deleteTarget.value = null;
};

const handleModuleFilter = () => {
    const moduleId = selectedModuleId.value;
    fetchData(moduleId ?? undefined);
};

onMounted(async () => {
    await fetchModules();
    await fetchData();
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
                        v-model="selectedModuleId"
                        :options="[
                            { label: '全部模組', value: null },
                            ...moduleData.map((m: any) => ({ label: m.label, value: m.id }))
                        ]"
                        placeholder="篩選模組"
                        @change="handleModuleFilter"
                        class="w-48" />
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
    <PermissionFrmModal
        v-model:open="addPermissionModalOpen"
        mode="add"
        @added="
            () => {
                const moduleId = selectedModuleId;
                fetchData(moduleId ?? undefined);
            }
        " />
    <PermissionFrmModal
        v-model:open="editPermissionModalOpen"
        mode="edit"
        :data="editData"
        @updated="
            () => {
                const moduleId = selectedModuleId;
                fetchData(moduleId ?? undefined);
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
