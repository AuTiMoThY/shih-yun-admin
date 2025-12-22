<script setup lang="ts">
definePageMeta({
    middleware: "auth"
});

const route = useRoute();

const newsId = computed(() => {
    const id = route.params.id as string;
    return parseInt(id, 10);
});

const { loadNewsData } = useAppNews();
const newsData = ref<any>(null);
const loadingData = ref(true);

const newsFormRef = ref<{
    loading: boolean;
    submit: () => void;
} | null>(null);

const loading = computed(
    () => (newsFormRef.value?.loading ?? false) || loadingData.value
);

const handleSubmit = () => {
    newsFormRef.value?.submit();
};

// 載入最新消息資料
onMounted(async () => {
    loadingData.value = true;
    try {
        const data = await loadNewsData(newsId.value);
        // console.log("data", data);
        if (data) {
            newsData.value = data;
        }
        // loadFormData 內部已經處理了錯誤和導航，如果返回 null 表示失敗
    } catch (error) {
        console.error("載入最新消息資料失敗", error);
    } finally {
        loadingData.value = false;
    }

    // console.log("newsData", newsData.value);
});
</script>

<template>
    <UDashboardPanel>
        <template #header>
            <UDashboardNavbar
                title="編輯最新消息"
                :ui="{ right: 'gap-3', title: 'text-primary' }">
                <template #leading>
                    <UDashboardSidebarCollapse />
                </template>
                <template #right>
                    <UButton
                        label="更新最新消息"
                        type="button"
                        color="success"
                        icon="lucide:save"
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
            <PageLoading v-if="loadingData" />
            <AppNewsFormPage
                v-else
                ref="newsFormRef"
                mode="edit"
                :initial-data="newsData" />
        </template>
        <template #footer>
            <PageFooter />
        </template>
    </UDashboardPanel>
</template>
