<script setup lang="ts">
definePageMeta({
    middleware: "auth"
});

const newsFormRef = ref<{
    loading: boolean;
    submit: () => void;
} | null>(null);

const loading = computed(() => newsFormRef.value?.loading ?? false);

const handleSubmit = () => {
    newsFormRef.value?.submit();
};
</script>
<template>
    <UDashboardPanel>
        <template #header>
            <UDashboardNavbar
                title="新增最新消息"
                :ui="{ right: 'gap-3', title: 'text-primary' }">
                <template #leading>
                    <UDashboardSidebarCollapse />
                </template>
                <template #right>
                    <UButton
                        label="新增最新消息"
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
                        to="/news" />
                </template>
            </UDashboardToolbar>
        </template>
        <template #body>
            <AppNewsFormPage ref="newsFormRef" mode="add" />
        </template>
        <template #footer>
            <PageFooter />
        </template>
    </UDashboardPanel>
</template>
