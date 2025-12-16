<script setup lang="ts">
definePageMeta({
    middleware: "auth"
});
import type { TableColumn } from "@nuxt/ui";
import { STATUS_LABEL_MAP } from "~/constants/system/status";
import { STATUS_ICON_MAP } from "~/constants/system/status_icon";

const { data, loading, fetchData, deletePermission } = usePermissionData();
const { data: moduleData, fetchData: fetchModules } = useModule();
const addPermissionModalOpen = ref(false);
const editPermissionModalOpen = ref(false);
const editData = ref<any>(null);
const selectedModuleId = ref<number | null>(null);

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
}

const editPermission = async (data: any) => {
    const permissionData = await usePermissionData().fetchById(data.id);
    if (permissionData) {
        editData.value = permissionData;
        editPermissionModalOpen.value = true;
    }
}

const handleDelete = async (data: any) => {
    await deletePermission({
        id: data.id,
        onSuccess: () => {
            const moduleId = selectedModuleId.value;
            fetchData(moduleId ?? undefined);
        }
    });
}

const handleModuleFilter = () => {
    const moduleId = selectedModuleId.value;
    fetchData(moduleId ?? undefined);
}

onMounted(async () => {
    await fetchModules();
    await fetchData();
});
</script>
<template>
    <UDashboardPanel>
        <template #header>
            <UDashboardNavbar title="權限設定" :ui="{ right: 'gap-3' }">
                <template #leading>
                    <UDashboardSidebarCollapse />
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
                <template #right>
                    <UButton
                        color="primary"
                        variant="outline"
                        icon="lucide:plus"
                        label="新增權限"
                        @click="addPermission" />
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
    <PermissionFrmModal
        v-model:open="addPermissionModalOpen"
        mode="add"
        @added="() => {
            const moduleId = selectedModuleId;
            fetchData(moduleId ?? undefined);
        }" />
    <PermissionFrmModal
        v-model:open="editPermissionModalOpen"
        mode="edit"
        :data="editData"
        @updated="() => {
            const moduleId = selectedModuleId;
            fetchData(moduleId ?? undefined);
        }" />
</template>

