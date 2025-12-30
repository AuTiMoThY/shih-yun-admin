<script setup lang="ts">
import ImageUploadSingle from "~/components/Form/ImageUploadSingle.vue";
import ImageUploadMultiple from "~/components/Form/ImageUploadMultiple.vue";

const router = useRouter();
const props = withDefaults(
    defineProps<{
        mode: "add" | "edit";
        initialData?: any;
        loading?: boolean;
        structureId?: number | null;
        structureInfo?: any;
    }>(),
    {
        loading: false,
        structureId: null,
        structureInfo: null
    }
);

const emit = defineEmits<{
    (e: "submit", data: any): void;
}>();

const {
    form,
    errors,
    loading: formLoading,
    submitError,
    clearError,
    loadNewsData,
    resetForm,
    addNews,
    editNews
} = useAppNews();

const { resolvePath } = useStructureResolver();
const { getBasePath } = useBasePath();
const basePath = getBasePath(router.currentRoute.value.path);
const pathInfo = resolvePath(basePath);

// 側邊欄預覽功能
const preview = useFormPreview({
    defaultOpen: false,
    width: "500px",
    title: "最新消息預覽"
});

// 圖片上傳元件引用（用於調用上傳方法）
const coverUploadRef = ref<InstanceType<typeof ImageUploadSingle> | null>(null);
const slideUploadRef = ref<InstanceType<typeof ImageUploadMultiple> | null>(
    null
);

// 載入初始資料
const loadInitialData = (data: any) => {
    if (data) {
        loadNewsData(Number(data.id));
    } else {
        resetForm();
    }
};

// HTML 原始碼預覽開關
const showHtmlCode = ref(false);

// 監聽表單數據變化，即時更新預覽
watch(
    () => ({
        title: form.title,
        content: form.content,
        show_date: form.show_date,
        cover: form.cover,
        slide: form.slide
    }),
    () => {
        preview.updatePreview(
            {
                title: form.title,
                content: form.content,
                show_date: form.show_date,
                cover: form.cover,
                slide: form.slide
            },
            {
                cover: {
                    preview: (coverUploadRef.value as any)?.preview?.value || null,
                    formValue: (coverUploadRef.value as any)?.formValue?.value || form.cover || ""
                },
                slide: {
                    previews: (slideUploadRef.value as any)?.previews?.value || [],
                    formValue: (slideUploadRef.value as any)?.formValue?.value || form.slide || []
                }
            }
        );
    },
    { deep: true, immediate: true }
);

// 表單提交
const handleSubmit = async (event?: Event) => {
    if (event) event.preventDefault();

    // 在提交前，先上傳待上傳的圖片（如果有臨時 ID）
    // 上傳封面圖
    if (form.cover && form.cover.startsWith("temp_")) {
        const uploadCoverSuccess = await coverUploadRef.value?.upload();
        if (!uploadCoverSuccess) {
            return;
        }
        // 上傳完成後，form.cover 會自動更新（通過 v-model）
    } else if (!form.cover && props.mode === "edit" && props.initialData?.cover) {
        // 編輯模式下，如果沒有新上傳的圖片，保持原值
        form.cover = props.initialData.cover;
    }

    // 上傳輪播圖
    if (
        form.slide &&
        form.slide.length > 0 &&
        form.slide.some((slide: string) => slide && slide.startsWith("temp_"))
    ) {
        const uploadSlidesSuccess = await slideUploadRef.value?.upload();
        if (!uploadSlidesSuccess) {
            return;
        }
        // 上傳完成後，從元件的 formValue 獲取最新的值（已替換臨時 ID 為正式 URL）
        // 從元件的 formValue 獲取最新值
        const formValueRef = (slideUploadRef.value as any)?.formValue;
        if (formValueRef && formValueRef.value && Array.isArray(formValueRef.value)) {
            // 過濾掉臨時 ID，只保留正式 URL
            form.slide = formValueRef.value.filter(
                (url: string) => url && !url.startsWith("temp_")
            );
        } else {
            // 如果無法從元件獲取，則過濾當前的 form.slide
            form.slide = form.slide.filter(
                (url: string) => url && !url.startsWith("temp_")
            );
        }
    } else if (
        (!form.slide || form.slide.length === 0) &&
        props.mode === "edit" &&
        props.initialData?.slide
    ) {
        // 編輯模式下，如果沒有值，保持原值
        form.slide = props.initialData.slide;
    } else if (form.slide && form.slide.length > 0) {
        // 過濾掉臨時 ID，只保留正式 URL
        form.slide = form.slide.filter(
            (url: string) => url && !url.startsWith("temp_")
        );
    }

    // 提交表單
    let success = false;
    if (props.mode === "edit") {
        const newsId = props.initialData?.id;
        if (!newsId) {
            submitError.value = "缺少最新消息 ID";
            return;
        }
        success = await editNews(newsId);
    } else {
        success = await addNews(props.structureId ?? null);
        if (success) {
            setTimeout(() => {
                router.push(`/${pathInfo.structure?.url}`);
            }, 1000);
        }
    }

    if (success) {
        emit("submit", form);
    }
};


// 監聽 initialData 變化
watch(
    () => props.initialData,
    (data) => {
        if (data) {
            loadInitialData(data);
        }
    },
    { immediate: true, deep: true }
);



onMounted(() => {
    console.log("[formPage] form", form);
});

// 暴露方法給父組件
defineExpose({
    loading: formLoading,
    submit: handleSubmit,
    // 預覽相關方法
    preview: {
        isOpen: preview.isOpen,
        toggle: preview.toggle,
        open: preview.open,
        close: preview.close,
        previewData: preview.previewData,
        getCoverUrl: preview.getCoverUrl,
        getSlideUrls: preview.getSlideUrls
    }
});

</script>

<template>
    <PageLoading v-if="formLoading" />
    <template v-else>
        <UForm :state="form" @submit="handleSubmit" class="">
            <section class="frm-bd grid grid-cols-1 gap-4">
                <UCard :ui="{ body: 'space-y-4' }">
                    <template #header>
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold">編輯</h3>
                        </div>
                    </template>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-4">
                            <UFormField
                                label="標題"
                                name="title"
                                :error="errors.title"
                                required>
                                <UInput
                                    v-model="form.title"
                                    placeholder="請輸入標題"
                                    size="lg"
                                    :disabled="formLoading"
                                    class="w-full"
                                    @input="clearError('title')" />
                            </UFormField>

                            <FormDateField
                                v-model="form.show_date"
                                label="日期"
                                name="show_date"
                                :error="errors.show_date"
                                :disabled="formLoading"
                                required />
                        </div>
                        <UCard :ui="{ body: 'space-y-4' }">
                            <FormStatusField
                                v-model="form.status"
                                label="狀態"
                                name="status"
                                :error="errors.status"
                                :disabled="formLoading" />
                        </UCard>
                    </div>

                    <ImageUploadSingle
                        ref="coverUploadRef"
                        v-model="form.cover"
                        label="封面圖"
                        name="cover"
                        description="建議尺寸：1920x1080"
                        :error="errors.cover"
                        :disabled="formLoading"
                        :ui="{ description: 'text-sm text-primary-500' }" />
                    <ImageUploadMultiple
                        ref="slideUploadRef"
                        v-model="form.slide"
                        label="輪播圖"
                        name="slide"
                        :disabled="formLoading" />

                    <UFormField
                        label="內文"
                        name="content"
                        :error="errors.content">
                        <div class="space-y-2">
                            <div class="flex justify-end">
                                <UButton
                                    :icon="
                                        showHtmlCode
                                            ? 'i-lucide-code'
                                            : 'i-lucide-eye'
                                    "
                                    :label="
                                        showHtmlCode
                                            ? '顯示編輯器'
                                            : '顯示 HTML 原始碼'
                                    "
                                    color="neutral"
                                    variant="outline"
                                    size="sm"
                                    @click="showHtmlCode = !showHtmlCode" />
                            </div>
                            <div v-if="!showHtmlCode">
                                <TiptapEditor v-model="form.content" />
                            </div>
                            <div v-else class="relative">
                                <CodeEditor
                                    v-model="form.content"
                                    :disabled="formLoading"
                                    @update:model-value="
                                        clearError('content')
                                    " />
                            </div>
                        </div>
                    </UFormField>
                </UCard>
            </section>
            <section class="frm-ft">
                <div v-if="submitError" class="mt-4 text-sm text-red-500">
                    {{ submitError }}
                </div>

                <div class="mt-6 flex gap-4 justify-end">
                    <UButton
                        type="button"
                        color="neutral"
                        variant="ghost"
                        :disabled="formLoading"
                        :to="`/${pathInfo.structure?.url}`"
                        label="取消" />
                    <UButton
                        type="button"
                        color="success"
                        icon="i-lucide-save"
                        :loading="formLoading"
                        :disabled="formLoading"
                        @click="handleSubmit()"
                        label="儲存" />
                </div>
            </section>
        </UForm>
    </template>
</template>
