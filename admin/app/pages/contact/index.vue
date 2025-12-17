<script setup lang="ts">
definePageMeta({
    middleware: "auth"
});


</script>
<template>
    <UDashboardPanel>
        <template #header>
            <UDashboardNavbar title="聯絡我們" :ui="{ right: 'gap-3', title: 'text-primary' }">
                <template #leading>
                    <UDashboardSidebarCollapse color="primary" />
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
        <template #footer>
            <PageFooter />
        </template>
    </UDashboardPanel>
</template>