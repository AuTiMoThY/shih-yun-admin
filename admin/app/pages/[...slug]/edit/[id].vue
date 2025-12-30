<script setup lang="ts">
definePageMeta({
    middleware: ["auth", "permission"]
});

const route = useRoute();
const router = useRouter();
const { resolvePath } = useStructureResolver();
const { getFormComponentByModule, hasFormComponentForModule } =
    useModuleComponent();

// 等待結構資料載入
const { data: structureData, fetchData: fetchStructure } = useStructure();
const { data: modulesData, fetchData: fetchModules } = useModule();

// 取得 ID
const itemId = computed(() => {
    const id = route.params.id as string | undefined;
    if (!id) return null;
    const parsedId = parseInt(id, 10);
    return isNaN(parsedId) ? null : parsedId;
});

// 解析路徑並取得結構資訊（移除 /edit/[id] 後綴）
const currentPath = computed(() => {
    // 移除 /edit/[id] 部分，取得基礎路徑
    const path = route.path || "";
    const match = path.match(/^(.+)\/edit\/\d+$/);
    return match && match[1] ? match[1] : path;
});

const pathInfo = computed(() => {
    const path = currentPath.value || route.path || "";
    return resolvePath(path);
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
    return structure ? `編輯${structure.label}` : "編輯";
});

// 返回列表的路徑
const backToListPath = computed(() => {
    const { structure } = structureInfo.value;
    if (structure?.url) {
        return structure.url.startsWith("/")
            ? structure.url
            : `/${structure.url}`;
    }
    // 如果沒有自訂 URL，使用模組的 name
    const { moduleName } = structureInfo.value;
    return moduleName ? `/${moduleName}` : "/";
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

// 資料載入狀態
const loadingData = ref(true);
const itemData = ref<any>(null);

// 處理提交
const handleSubmit = () => {
    formRef.value?.submit();
};

// 載入資料
const loadItemData = async () => {
    if (!itemId.value) {
        router.push(backToListPath.value);
        return;
    }

    const { moduleName } = structureInfo.value;
    loadingData.value = true;

    try {
        if (moduleName === "news") {
            const { loadNewsData } = useAppNews();
            const data = await loadNewsData(itemId.value);
            if (data) {
                itemData.value = data;
            } else {
                router.push(backToListPath.value);
            }
        } else if (moduleName === "contact") {
            const { loadContactData } = useAppContact();
            const data = await loadContactData(itemId.value);
            if (data) {
                itemData.value = data;
            } else {
                router.push(backToListPath.value);
            }
        } else if (moduleName === "case") {
            const { loadCaseData } = useAppCase();
            const data = await loadCaseData(itemId.value);
            if (data) {
                itemData.value = data;
            } else {
                router.push(backToListPath.value);
            }
        } else if (moduleName === "progress") {
            const { loadProgressData } = useAppProgress();
            const data = await loadProgressData(itemId.value);
            if (data) {
                itemData.value = data;
            } else {
                router.push(backToListPath.value);
            }
        } else {
            // 不支援的模組類型
            router.push(backToListPath.value);
        }
    } catch (error) {
        console.error("載入資料失敗", error);
        router.push(backToListPath.value);
    } finally {
        loadingData.value = false;
    }
};


// 預覽功能（僅支援有預覽功能的模組）
const previewOpen = ref(false);
const hasPreview = computed(() => {
    // 檢查子組件是否有預覽功能
    return formRef.value?.preview !== undefined;
});

// 取得預覽相關的方法和數據
const previewData = computed(() => {
    return formRef.value?.preview?.previewData?.value ?? {};
});

const getCoverUrl = () => {
    return formRef.value?.preview?.getCoverUrl?.() ?? "";
};

const getSlideUrls = () => {
    return formRef.value?.preview?.getSlideUrls?.() ?? [];
};

// 取得模組類型（用於 FormPreview）
const moduleType = computed<"news" | "case" | "about" | "custom">(() => {
    const moduleName = structureInfo.value.moduleName;
    if (moduleName === "news" || moduleName === "case" || moduleName === "about") {
        return moduleName;
    }
    return "custom";
});


// 初始化：載入結構和模組資料
onMounted(async () => {
    if (!structureData.value?.length) {
        await fetchStructure();
    }
    if (!modulesData.value?.length) {
        await fetchModules();
    }

    // 等待結構資料載入完成後再載入項目資料
    await nextTick();
    await loadItemData();
});

// 監聽路徑變化
watch(
    () => route.path,
    () => {
        // 路徑變化時，重新載入資料
        loadItemData();
    }
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
                        label="更新"
                        type="button"
                        color="success"
                        icon="lucide:save"
                        :loading="formLoading || loadingData"
                        :disabled="formLoading || loadingData"
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
                <template #right>
                    <!-- 預覽按鈕（僅在有預覽功能的模組中顯示） -->
                    <USlideover
                        v-if="hasPreview"
                        v-model:open="previewOpen"
                        :ui="{ content: 'w-full max-w-2xl' }">
                        <UButton
                            v-if="hasPreview"
                            icon="i-lucide-eye"
                            label="預覽"
                            color="primary"
                            variant="outline"
                            size="sm"/>
                        <template #body>
                            <FormPreview
                                :data="{
                                    ...previewData,
                                    slide: previewData.slide
                                        ? [...previewData.slide]
                                        : undefined
                                }"
                                :cover-url="getCoverUrl()"
                                :slide-urls="getSlideUrls()"
                                :module-type="moduleType" />
                        </template>
                    </USlideover>
                </template>
            </UDashboardToolbar>
        </template>
        <template #body>
            <!-- 載入中狀態 -->
            <div
                v-if="isLoading || loadingData"
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
                v-else-if="itemData"
                ref="formRef"
                :is="formComponent"
                mode="edit"
                :initial-data="itemData"
                :structure-id="structureInfo.structureId"
                @submit="
                    () => {
                        // 編輯模式下，導向回列表頁
                        router.push(backToListPath);
                    }
                " />
        </template>
        <template #footer>
            <PageFooter />
        </template>
    </UDashboardPanel>
</template>
