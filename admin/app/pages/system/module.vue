<script setup lang="ts">
definePageMeta({
    middleware: "auth"
});
import type { TableColumn } from "@nuxt/ui";


const { data, loading, fetchData, deleteModule } = useModule();
const addModuleModalOpen = ref(false);
const editModuleModalOpen = ref(false);
const editData = ref<any>(null);
const columns: TableColumn<any>[] = [
    { accessorKey: "label", header: "模組名稱" },
    { accessorKey: "name", header: "模組代碼" },
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
                    onClick: () => editModule(row.original)
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

const addModule = () => {
    addModuleModalOpen.value = true;
}

const editModule = (data: any) => {
    console.log("editModule", data);
    editData.value = data;
    editModuleModalOpen.value = true;
}

const handleDelete = async (data: any) => {
    await deleteModule({
        id: data.id,
        onSuccess: () => fetchData()
    });
}

onMounted(() => {
    fetchData();
});
</script>
<template>
    <UDashboardPanel>
        <template #header>
            <UDashboardNavbar title="模組設定" :ui="{ right: 'gap-3', title: 'text-primary' }">
                <template #leading>
                    <UDashboardSidebarCollapse color="primary" />
                </template>
                <template #right>
                    <UButton
                        label="新增模組"
                        color="primary"
                        icon="lucide:plus"
                        @click="addModule" />
                </template>
            </UDashboardNavbar>
        </template>
        <template #body>
            <DataTable
                :data="data"
                :columns="columns"
                :loading="loading" />
        </template>
        <template #footer>
            <PageFooter />
        </template>
    </UDashboardPanel>
    <ModuleFrmModal
        v-model:open="addModuleModalOpen"
        mode="add"
        @added="fetchData" />
    <ModuleFrmModal
        v-model:open="editModuleModalOpen"
        mode="edit"
        :data="editData"
        @updated="fetchData" />
</template>
