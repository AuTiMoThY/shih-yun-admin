<script setup lang="ts">
definePageMeta({
    middleware: "auth"
});

const route = useRoute();
const { resolvePath } = useStructureResolver();
const { getComponentByModule, hasComponentForModule } = useModuleComponent();

// 等待結構資料載入
const { data: structureData, fetchData: fetchStructure } = useStructure();
const { data: modulesData, fetchData: fetchModules } = useModule();

// 解析路徑並取得結構資訊
const pathInfo = computed(() => {
    return resolvePath(route.path);
});

// 取得結構資訊
const structureInfo = computed(() => {
    const info = pathInfo.value;
    return {
        label: info.structure?.label || null,
        url: info.structure?.url || null,
        structureId: info.structure_id,
        moduleName: info.module_name,
        structure: info.structure
    };
});
// 根據模組名稱取得組件（使用實際的組件引用）
const component = computed(() => {
    const { moduleName } = structureInfo.value;
    if (!moduleName || !hasComponentForModule(moduleName)) {
        console.log("[...slug] component computed: 無法取得組件", {
            moduleName,
            hasComponent: hasComponentForModule(moduleName)
        });
        return null;
    }
    return getComponentByModule(moduleName);
});


// 組件實例的 ref（用於取得組件暴露的方法）
const componentRef = ref<any>(null);

// 頁面標題
const pageTitle = computed(() => {
    const { structure } = structureInfo.value;
    return structure?.label || "載入中...";
});

// 載入狀態
const isLoading = computed(() => {
    return !structureData.value?.length || !modulesData.value?.length;
});

// 監聽路徑變化，重新解析
watch(
    () => route.path,
    (newPath) => {
        console.log("[...slug] 路徑變化:", newPath);
        console.log("[...slug] 新的 pathInfo:", pathInfo.value);
        console.log("[...slug] 新的 component:", component.value);
        // 路徑變化時，組件會自動重新渲染
    },
    { immediate: true }
);

// 監聽 component 變化
watch(
    () => component.value,
    (newComp) => {
        console.log("[...slug] component 變化:", newComp);
    }
);

// 監聽 isLoading 變化
watch(
    () => isLoading.value,
    (loading) => {
        console.log("[...slug] isLoading 變化:", loading);
    }
);

// 取得組件的 loading 狀態（用於按鈕）
const componentLoading = computed(() => {
    return componentRef.value?.loading?.value || false;
});

// 監聽 componentRef 變化，確保組件已掛載
watch(
    () => componentRef.value,
    (newRef) => {
        if (newRef) {
            console.log("[...slug] componentRef 已設定:", newRef);
        }
    }
);

// 初始化：載入結構和模組資料
onMounted(async () => {
    console.log("[...slug] onMounted: 開始載入資料");
    if (!structureData.value?.length) {
        console.log("[...slug] 載入結構資料");
        await fetchStructure();
    }
    if (!modulesData.value?.length) {
        console.log("[...slug] 載入模組資料");
        await fetchModules();
    }

    console.log("[...slug] pathInfo:", pathInfo.value);
    console.log("[...slug] component:", component.value);
    console.log("[...slug] structureInfo:", structureInfo.value);
});
</script>

<template>
    <UDashboardPanel>
        <template #header>
            <UDashboardNavbar
                :title="pageTitle"
                :ui="{ right: 'gap-3', title: 'text-primary' }">
                <template #leading>
                    <UDashboardSidebarCollapse />
                </template>
                <template #right>
                    <!-- About 模組的按鈕 -->
                    <template v-if="structureInfo.moduleName === 'about'">
                        <PermissionGuard permission="about.section.create">
                            <UButton
                                label="新增區塊(卡)"
                                color="primary"
                                icon="i-lucide-plus"
                                @click="() => componentRef?.addCutSection?.()"
                                :ui="{ base: 'justify-center' }" />
                        </PermissionGuard>
                        <UButton
                            label="儲存"
                            color="success"
                            icon="i-lucide-save"
                            :loading="componentLoading"
                            :disabled="componentLoading"
                            @click="() => componentRef?.saveAllSections?.()" />
                    </template>

                    <!-- News 模組的按鈕 -->
                    <template v-else-if="structureInfo.moduleName === 'news'">
                        <UButton
                            :label="`新增${structureInfo.label || ''}`"
                            color="primary"
                            icon="i-lucide-plus"
                            :to="`/${structureInfo.url}/add`" />
                    </template>

                    <!-- Case 模組的按鈕 -->
                    <template v-else-if="structureInfo.moduleName === 'case'">
                        <UButton
                            :label="`新增${structureInfo.label || ''}`"
                            color="primary"
                            icon="i-lucide-plus"
                            :to="`/${structureInfo.url}/add`" />
                    </template>
                </template>
            </UDashboardNavbar>
        </template>
        <template #body>
            <!-- 載入中狀態 -->
            <div
                v-if="isLoading"
                class="flex items-center justify-center py-12">
                <UIcon name="i-lucide-loader-2" class="w-6 h-6 animate-spin" />
            </div>

            <!-- 找不到對應的模組 -->
            <div
                v-else-if="!component"
                class="flex flex-col items-center justify-center py-12">
                <UIcon
                    name="i-lucide-alert-circle"
                    class="w-12 h-12 text-gray-400 mb-4" />
                <h2
                    class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    頁面不存在
                </h2>
                <p class="text-gray-500 dark:text-gray-400">
                    找不到對應的模組
                </p>
            </div>
            <!-- 動態載入組件 -->
            <component
                v-else
                ref="componentRef"
                :is="component"
                :key="`${structureInfo.moduleName}-${structureInfo.structureId}`"
                :structure-id="structureInfo.structureId" />
        </template>
        <template #footer>
            <PageFooter />
        </template>
    </UDashboardPanel>
</template>
