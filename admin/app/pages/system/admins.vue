<script setup lang="ts">
definePageMeta({
    middleware: "auth"
});
import type { TableColumn } from "@nuxt/ui";
import { h, resolveComponent } from "vue";
import { PERMISSION_LABEL_MAP } from "~/constants/permissions";
import { ADMIN_STATUS_LABEL_MAP } from "~/constants/admin_status";

const toast = useToast();

const { getUsers } = useUsers();
const users = ref<any[]>([]);
const loading = ref(false);
const table = ref();

const { public: runtimePublic } = useRuntimeConfig();
const apiBase = runtimePublic.apiBase;

// 編輯 modal 相關
const editModalOpen = ref(false);
const editingAdmin = ref<any | null>(null);

// 參考 Addadmin.vue 的權限設定，轉換顯示文字
const permissionLabelMap = PERMISSION_LABEL_MAP;
const adminStatusLabelMap = ADMIN_STATUS_LABEL_MAP;
const statusIconMap: Record<string, string> = {
    "1": "i-lucide-badge-check",
    "0": "i-lucide-ban"
};
const columns: TableColumn<any>[] = [
    { accessorKey: "username", header: "帳號" },
    { accessorKey: "name", header: "姓名" },
    {
        accessorKey: "permission_name",
        header: "權限名稱",
        cell: ({ row }) =>
            permissionLabelMap[row.original.permission_name] ??
            row.original.permission_name
    },
    {
        accessorKey: "status",
        header: "狀態",
        cell: ({ row }) => {
            const status = String(row.original.status);
            const label = adminStatusLabelMap[status] ?? status;
            const icon =
                statusIconMap[status] ?? "i-lucide-help-circle";
            const UIcon = resolveComponent("UIcon");

            return h(
                "div",
                { class: "flex items-center gap-2" },
                [
                    h(UIcon, {
                        name: icon,
                        class:
                            status === "1"
                                ? "text-emerald-500"
                                : "text-rose-500"
                    }),
                    h("span", label)
                ]
            );
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
    editingAdmin.value = admin;
    editModalOpen.value = true;
};

const deleteAdmin = async (admin: any) => {
    console.log(admin);
    try {
        const response = await $fetch<{ success: boolean; message: string }>(
            "/api/admins/delete",
            {
                baseURL: apiBase,
                method: "POST",
                body: { id: admin.id },
                headers: { "Content-Type": "application/json" },
                credentials: "include"
            }
        );
        console.log(response);
        if (response.success) {
            toast.add({ title: response.message, color: "success" });
            fetchUsers();
        } else {
            toast.add({ title: response.message, color: "error" });
        }
    } catch (error: any) {
        console.error("deleteAdmin error", error);
        toast.add({ title: error.message || "刪除管理員失敗，請稍後再試", color: "error" });
    }
};

const fetchUsers = async () => {
    loading.value = true;
    const res = await getUsers();
    if (res?.success) {
        users.value = res.data;
    } else {
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
                    separator: 'h-0'
                }" />
        </template>
    </UDashboardPanel>

    <!-- 編輯管理員 Modal -->
    <AdminsEditadmin
        v-model:open="editModalOpen"
        :admin="editingAdmin"
        @updated="fetchUsers" />
</template>
