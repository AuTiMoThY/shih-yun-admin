<script setup lang="ts">
definePageMeta({
    middleware: "auth"
});

const route = useRoute();
const router = useRouter();
const { resolvePath } = useStructureResolver();
const { getFormComponentByModule, hasFormComponentForModule } = useModuleComponent();

// 等待結構資料載入
const { data: structureData, fetchData: fetchStructure } = useStructure();
const { data: modulesData, fetchData: fetchModules } = useModule();

// 解析路徑並取得結構資訊（移除 /add 後綴）
const currentPath = computed(() => {
    return route.path.replace(/\/add$/, '');
});

const pathInfo = computed(() => {
    return resolvePath(currentPath.value);
});

// 取得結構資訊
const structureInfo = computed(() => {
    const info = pathInfo.value;
    return {
        structureId: info.structure_id,
        moduleName: info.module_name,
        structure: info.structure
    };
});

// 根據模組名稱取得表單組件
const formComponent = computed(() => {
    const { moduleName } = structureInfo.value;
    if (!moduleName || !hasFormComponentForModule(moduleName)) {
        return null;
    }
    return getFormComponentByModule(moduleName);
});

// 頁面標題
const pageTitle = computed(() => {
    const { structure } = structureInfo.value;
    return structure ? `新增${structure.label}` : '新增';
});

// 返回列表的路徑
const backToListPath = computed(() => {
    const { structure } = structureInfo.value;
    if (structure?.url) {
        return structure.url.startsWith('/') ? structure.url : `/${structure.url}`;
    }
    // 如果沒有自訂 URL，使用模組的 name
    const { moduleName } = structureInfo.value;
    return moduleName ? `/${moduleName}` : '/';
});

// 載入狀態
const isLoading = computed(() => {
    return !structureData.value?.length || !modulesData.value?.length;
});

// 表單組件的 ref
const formRef = ref<any>(null);

// 取得表單的 loading 狀態
const formLoading = computed(() => {
    return formRef.value?.loading ?? false;
});

// 處理提交
const handleSubmit = () => {
    formRef.value?.submit();
};

// 初始化：載入結構和模組資料
onMounted(async () => {
    if (!structureData.value?.length) {
        await fetchStructure();
    }
    if (!modulesData.value?.length) {
        await fetchModules();
    }
});

// 監聽路徑變化
watch(
    () => route.path,
    () => {
        // 路徑變化時，組件會自動重新渲染
    },
    { immediate: true }
);
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
                    <UButton
                        label="新增"
                        type="button"
                        color="primary"
                        icon="lucide:plus"
                        :loading="formLoading"
                        :disabled="formLoading"
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
                        :to="backToListPath" />
                </template>
            </UDashboardToolbar>
        </template>
        <template #body>
            <!-- 載入中狀態 -->
            <div
                v-if="isLoading"
                class="flex items-center justify-center py-12">
                <UIcon name="i-lucide-loader-2" class="w-6 h-6 animate-spin" />
            </div>

            <!-- 找不到對應的模組或表單組件 -->
            <div
                v-else-if="!formComponent"
                class="flex flex-col items-center justify-center py-12">
                <UIcon
                    name="i-lucide-alert-circle"
                    class="w-12 h-12 text-gray-400 mb-4" />
                <h2
                    class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    頁面不存在
                </h2>
                <p class="text-gray-500 dark:text-gray-400">
                    找不到對應的模組或表單組件
                </p>
            </div>

            <!-- 動態載入表單組件 -->
            <component
                v-else
                ref="formRef"
                :is="formComponent"
                mode="add"
                :structure-id="structureInfo.structureId"
                @submit="() => {
                    // 表單組件內部已經處理導向，這裡可以處理其他邏輯
                    // 如果需要覆蓋導向，可以在這裡處理
                }" />
        </template>
        <template #footer>
            <PageFooter />
        </template>
    </UDashboardPanel>
</template>
