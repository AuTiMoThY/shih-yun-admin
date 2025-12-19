<script setup lang="ts">
import type { TableColumn } from "@nuxt/ui";
definePageMeta({
    middleware: "auth"
});
const UCheckbox = resolveComponent("UCheckbox");
const UButton = resolveComponent("UButton");
const deleteConfirmModalOpen = ref(false);
const deleteTarget = ref<{ id: number | string; name: string } | null>(null);
const { data, loading, fetchData, updateStatus, deleteContact } = useContact();
const columns: TableColumn<any>[] = [
    { accessorKey: "name", header: "姓名" },
    { accessorKey: "email", header: "信箱" },
    { accessorKey: "phone", header: "電話" },
    {
        accessorKey: "status",
        header: "處理狀態",
        cell: ({ row }) => {
            const status = computed(() => Number(row.original.status) === 1);
            const statusLabel = computed(() =>
                status.value ? "已處理" : "未處理"
            );

            return h(UCheckbox, {
                label: statusLabel.value,
                modelValue: status.value,
                "onUpdate:modelValue": async (value: boolean) => {
                    const oldStatus = row.original.status;
                    const newStatus = value ? 1 : 0;

                    // 樂觀更新：先更新本地狀態
                    row.original.status = newStatus;

                    // 調用 API 更新後端狀態
                    const result = await updateStatus(
                        row.original.id,
                        newStatus
                    );

                    // 如果更新失敗，回滾狀態
                    if (!result.success) {
                        row.original.status = oldStatus;
                    }
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
                    to: `/contact/edit/${row.original.id}`
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
    deleteTarget.value = { id: data.id, name: data.name };
    deleteConfirmModalOpen.value = true;
};

const confirmDelete = async () => {
    await deleteContact(deleteTarget.value?.id as number, {
        onSuccess: () => fetchData()
    });
    deleteConfirmModalOpen.value = false;
    deleteTarget.value = null;
};

onMounted(async () => {
    await fetchData();
    console.log("data", data.value);
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
    <DeleteConfirmModal
        v-model:open="deleteConfirmModalOpen"
        title="確認刪除"
        :description="
            deleteTarget
                ? `確定要刪除「${deleteTarget.name}」嗎？此操作無法復原，「${deleteTarget.name}」將會被永久刪除。`
                : ''
        "
        :on-confirm="confirmDelete" />
</template>
