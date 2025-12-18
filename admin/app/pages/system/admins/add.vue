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
            <UDashboardNavbar
                title="新增管理員"
                :ui="{ right: 'gap-3', title: 'text-primary' }">
                <template #leading>
                    <UDashboardSidebarCollapse />
                </template>
                <template #right>
                    <UButton
                        label="新增管理員"
                        type="button"
                        color="primary"
                        icon="lucide:plus"
                        :loading="loading"
                        :disabled="loading"
                        @click="handleSubmit()" />
                </template>
            </UDashboardNavbar>
            <UDashboardToolbar>
                <template #left>
                    <UButton
                        label="返回列表"
                        color="neutral"
                        variant="ghost"
                        icon="i-lucide-arrow-left"
                        to="/system/admins" />
                </template>
            </UDashboardToolbar>
        </template>
        <template #body>
            <AdminsAdminForm ref="adminFormRef" mode="add" />
        </template>
        <template #footer>
            <PageFooter />
        </template>
    </UDashboardPanel>
</template>
