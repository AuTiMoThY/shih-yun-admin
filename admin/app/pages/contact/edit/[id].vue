<script setup lang="ts">
definePageMeta({
    middleware: "auth"
});

const router = useRouter();
const route = useRoute();

const contactId = computed(() => {
    const id = route.params.id as string;
    return parseInt(id, 10);
});

const { loadContactData } = useContact();
const contactData = ref<any>(null);
const loadingData = ref(true);

const contactFormRef = ref<{
    loading: boolean;
    submit: () => void;
} | null>(null);

const loading = computed(
    () => (contactFormRef.value?.loading ?? false) || loadingData.value
);

const handleSubmit = () => {
    contactFormRef.value?.submit();
};

// 載入聯絡表單資料
onMounted(async () => {
    loadingData.value = true;
    try {
        const data = await loadContactData(contactId.value);
        if (data) {
            contactData.value = data;
        } else {
            // 如果載入失敗，導航回列表頁
            router.push("/contact");
        }
    } catch (error) {
        console.error("載入聯絡表單資料失敗", error);
        router.push("/contact");
    } finally {
        loadingData.value = false;
    }

    console.log("contactData", contactData.value);
    
});
</script>

<template>
    <UDashboardPanel>
        <template #header>
            <UDashboardNavbar
                title="編輯聯絡表單"
                :ui="{ right: 'gap-3', title: 'text-primary' }">
                <template #leading>
                    <UDashboardSidebarCollapse />
                </template>
                <template #right>
                    <UButton
                        label="儲存回信"
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
                        to="/contact" />
                </template>
            </UDashboardToolbar>
        </template>
        <template #body>
            <PageLoading v-if="loadingData" />
            <AppContactFormPage
                v-else-if="contactData"
                ref="contactFormRef"
                mode="edit"
                :initial-data="contactData"
                @submit="() => router.push('/contact')" />
        </template>
        <template #footer>
            <PageFooter />
        </template>
    </UDashboardPanel>
</template>
