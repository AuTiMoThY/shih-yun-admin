<script setup lang="ts">
definePageMeta({
    middleware: "auth"
});
import type { TableColumn } from "@nuxt/ui";

const toast = useToast();

const { getUsers } = useUsers();
const users = ref<any[]>([]);
const loading = ref(false);
const table = ref();

const columns: TableColumn<any>[] = [
    { accessorKey: "username", header: "帳號" },
    { accessorKey: "name", header: "姓名" },
    { accessorKey: "permission_name", header: "權限名稱" },
    { accessorKey: "status", header: "狀態" }
];

const fetchUsers = async () => {
    loading.value = true;
    const res = await getUsers();
    if (res?.success) {
        users.value = res.data;
    }
    else {
        console.error(res.message);
        toast.add({
            title: res.message,
            color: "error"
        });
    }
    loading.value = false;
};

onMounted(fetchUsers);
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
                    <AdminsAddadmin @added="fetchUsers" />
                </template>
            </UDashboardToolbar>
        </template>
        <template #body>
            <UTable
                ref="table"
                class="shrink-0"
                :data="users"
                :columns="columns"
                :loading="loading"
                :ui="{
                    base: 'table-fixed border-separate border-spacing-0',
                    thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
                    tbody: '[&>tr]:last:[&>td]:border-b-0',
                    th: 'py-2 first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r',
                    td: 'border-b border-default',
                    separator: 'h-0',
                }"
            />
        </template>
    </UDashboardPanel>
</template>
