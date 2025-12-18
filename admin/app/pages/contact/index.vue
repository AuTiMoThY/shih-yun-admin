<script setup lang="ts">
import type { TableColumn } from "@nuxt/ui";
definePageMeta({
    middleware: "auth"
});
const UCheckbox = resolveComponent("UCheckbox");
const UButton = resolveComponent("UButton");

const { data, loading, fetchData, updateStatus } = useContact();
const columns: TableColumn<any>[] = [
    { accessorKey: "name", header: "姓名" },
    { accessorKey: "email", header: "信箱" },
    { accessorKey: "phone", header: "電話" },
    { accessorKey: "project", header: "建案" },
    {
        accessorKey: "status",
        header: "處理狀態",
        cell: ({ row }) => {
            const status = computed(() => row.original.status);
            const statusLabel = computed(() => status.value === 1 ? "已處理" : "未處理");
            return h(UCheckbox, {
                label: statusLabel.value,
                modelValue: status.value === 1,
                onChange: async (value: boolean) => {
                    const newStatus = value ? 1 : 0;
                    // 調用 API 更新後端狀態，成功後會自動更新本地數據
                    await updateStatus(row.original.id, newStatus);
                }
            });
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
                    // to: `/contact/edit/${row.original.id}`
                }),
                h(UButton, {
                    icon: "i-lucide-trash",
                    label: "刪除",
                    color: "error",
                    variant: "ghost",
                    size: "xs",
                    // onClick: () => handleDelete(row.original)
                })
            ]);
        }
    }
];

const handleDelete = async (data: any) => {
    // deleteTarget.value = { id: data.id, name: data.name };
    // deleteConfirmModalOpen.value = true;
};

const handleEdit = async (data: any) => {
    console.log("handleEdit", data);
};

onMounted(async () => {
    await fetchData();
});
</script>
<template>
    <UDashboardPanel>
        <template #header>
            <UDashboardNavbar
                title="聯絡表單"
                :ui="{ right: 'gap-3', title: 'text-primary' }">
                <template #leading>
                    <UDashboardSidebarCollapse />
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
