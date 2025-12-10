<script setup lang="ts">
definePageMeta({
    middleware: "auth"
});

import type { TableColumn } from "@nuxt/ui";

const data = ref<any[]>([]);
const loading = ref(false);
const table = ref();

const columns: TableColumn<any>[] = [
    { accessorKey: "username", header: "帳號" },
    { accessorKey: "name", header: "姓名" }
];
</script>

<template>
    <UDashboardPanel>
        <template #header>
            <UDashboardNavbar title="管理系統架構" :ui="{ right: 'gap-3' }">
                <template #leading>
                    <UDashboardSidebarCollapse />
                </template>
            </UDashboardNavbar>
            <UDashboardToolbar>
                <template #right>
                    <StructureAddlevel />
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
</template>
