<script setup lang="ts">
definePageMeta({
    middleware: "auth"
});

const route = useRoute();

// 從 query 參數取得 structure_id，如果沒有則嘗試從 URL 解析
const structureId = computed(() => {
    // 優先使用 query 參數
    if (route.query.structure_id) {
        return Number(route.query.structure_id);
    }
    
    // 如果沒有 query 參數，嘗試從 URL 解析
    const { resolvePath } = useStructureResolver();
    const pathInfo = resolvePath(route.path);
    return pathInfo.structure_id;
});

// 確保結構資料已載入
onMounted(async () => {
    const { data: structureData, fetchData: fetchStructure } = useStructure();
    if (!structureData.value?.length) {
        await fetchStructure();
    }
});
</script>
<template>
    <UDashboardPanel>
        <template #header>
            <UDashboardNavbar
                title="關於我們"
                :ui="{ right: 'gap-3', title: 'text-primary' }">
                <template #leading>
                    <UDashboardSidebarCollapse />
                </template>
            </UDashboardNavbar>
        </template>
        <template #body>
            <!-- 使用組件 -->
            <AppAbout :structure-id="structureId" />
        </template>
        <template #footer>
            <PageFooter />
        </template>
    </UDashboardPanel>
</template>
