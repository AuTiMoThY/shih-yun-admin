<script setup lang="ts">
definePageMeta({
    middleware: "auth"
});

const adminFormRef = ref<{
    loading: boolean;
    submit: () => void;
} | null>(null);

const loading = computed(() => adminFormRef.value?.loading ?? false);

const handleSubmit = () => {
    adminFormRef.value?.submit();
};
</script>

<template>
    <UDashboardPanel id="admins-add">
        <template #header>
            <UDashboardNavbar title="新增管理員" :ui="{ right: 'gap-3' }">
                <template #leading>
                    <UDashboardSidebarCollapse />
                </template>
                <template #right>
                    <UButton
                        type="button"
                        color="primary"
                        variant="outline"
                        icon="lucide:plus"
                        :loading="loading"
                        :disabled="loading"
                        @click="handleSubmit()"
                        label="新增管理員" />
                </template>
            </UDashboardNavbar>
            <UDashboardToolbar>
                <template #left>
                    <UButton
                        color="neutral"
                        variant="outline"
                        icon="i-lucide-arrow-left"
                        label="返回列表"
                        to="/system/admins" />
                </template>
            </UDashboardToolbar>
        </template>
        <template #body>
            <AdminsAdminForm ref="adminFormRef" mode="add" />
        </template>
    </UDashboardPanel>
</template>
