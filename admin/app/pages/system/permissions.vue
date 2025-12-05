<template>
    <UDashboardPanel>
        <template #header>
            <UDashboardNavbar title="權限設定" :ui="{ right: 'gap-3' }">
                <template #leading>
                    <UDashboardSidebarCollapse />
                </template>

            </UDashboardNavbar>

        </template>
        <template #body>
            <UCard>
                <div class="p-8 text-center text-muted-foreground">
                    此功能尚未開發
                </div>
            </UCard>
        </template>
    </UDashboardPanel>
</template>

<script setup lang="ts">
definePageMeta({
    middleware: 'auth',
});
</script>

