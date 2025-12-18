<script setup lang="ts">
definePageMeta({
    middleware: "auth"
});
</script>

<template>
    <UDashboardPanel id="home">
        <template #header>
            <UDashboardNavbar
                title="Home"
                :ui="{ right: 'gap-3', title: 'text-primary' }">
                <template #leading>
                    <UDashboardSidebarCollapse />
                </template>

                <template #right> </template>
            </UDashboardNavbar>
        </template>

        <template #body>
            <div class="p-6">
                <h2 class="text-2xl font-bold mb-4">歡迎回來！</h2>
                <p class="text-gray-600 dark:text-gray-400">
                    這是您的後台管理介面首頁。
                </p>
            </div>
        </template>
        <template #footer>
            <PageFooter />
        </template>
    </UDashboardPanel>
</template>
