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
    loadProgressData,
    resetForm,
    addProgress,
    editProgress
} = useAppProgress();

// 獲取建案列表
const { data: caseData, fetchData: fetchCaseData } = useAppCase();

const { resolvePath } = useStructureResolver();
const { getBasePath } = useBasePath();
const basePath = getBasePath(router.currentRoute.value.path);
const pathInfo = resolvePath(basePath);

// 圖片上傳元件引用（用於調用上傳方法）
const slideUploadRef = ref<InstanceType<typeof ImageUploadMultiple> | null>(null);


// 載入初始資料
const loadInitialData = async (data: any) => {
    if (data) {
        await loadProgressData(Number(data.id));
    }
    else {
        resetForm();
    }
};

// 提交
const handleSubmit = async (event?: Event) => {
    if (event) event.preventDefault();

    // 上傳輪播圖
    if (
        form.images &&
        form.images.length > 0 &&
        form.images.some((image: string) => image && image.startsWith("temp_"))
    ) {
        const uploadSlidesSuccess = await slideUploadRef.value?.upload();
        if (!uploadSlidesSuccess) {
            return;
        }
        // 上傳完成後，從元件的 formValue 獲取最新的值（已替換臨時 ID 為正式 URL）
        // 從元件的 formValue 獲取最新值
        const formValueRef = (slideUploadRef.value as any)?.formValue;
        if (formValueRef && formValueRef.value && Array.isArray(formValueRef.value)) {
            // 直接使用元件的 formValue（已經過濾掉臨時 ID）
            form.images = formValueRef.value.filter(
                (url: string) => url && !url.startsWith("temp_")
            );
        } else {
            // 如果無法從元件獲取，則過濾當前的 form.slide
            form.images = form.images.filter(
                (url: string) => url && !url.startsWith("temp_")
            );
        }
    } else if (
        (!form.images || form.images.length === 0) &&
        props.mode === "edit" &&
        props.initialData?.images
    ) {
        // 編輯模式下，如果沒有值，保持原值
        form.images = props.initialData.images;
    } else if (form.images && form.images.length > 0) {
        // 過濾掉臨時 ID，只保留正式 URL
        form.images = form.images.filter(
            (url: string) => url && !url.startsWith("temp_")
        );
    }

    let success = false;
    if (props.mode === "edit") {
        const progressId = props.initialData?.id;
        if (!progressId) {
            submitError.value = "缺少工程進度 ID";
            return;
        }
        success = await editProgress(progressId);
    } else {
        success = await addProgress();
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

// 監聽 initialData
watch(
    () => props.initialData,
    (data) => {
        loadInitialData(data);
    },
    { immediate: true, deep: true }
);

// 載入建案列表
onMounted(async () => {
    await fetchCaseData();
});

defineExpose({
    loading: formLoading,
    submit: handleSubmit
});
</script>

<template>
    <PageLoading v-if="formLoading" />
    <template v-else>
        <UForm :state="form" @submit="handleSubmit">
            <div class="grid grid-cols-1 gap-4">
                <UCard :ui="{ body: 'space-y-4' }">
                    <template #header>
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold">基本資訊</h3>
                        </div>
                    </template>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex flex-col gap-4">
                            <UFormField
                                label="所屬建案"
                                name="case_id"
                                :error="errors.case_id">
                                <USelect
                                    v-model="form.case_id"
                                    :items="[
                                        { label: '請選擇建案', value: null },
                                        ...(caseData || []).map((c: any) => ({
                                            label: c.title || `建案 #${c.id}`,
                                            value: c.id
                                        }))
                                    ]"
                                    placeholder="請選擇建案"
                                    @update:model-value="
                                        clearError('case_id')
                                    "
                                    :ui="{ base: 'min-w-[120px]' }" />
                            </UFormField>
                            <UFormField
                                label="標題"
                                name="title"
                                :error="errors.title"
                                required>
                                <UInput
                                    v-model="form.title"
                                    placeholder="請輸入標題"
                                    @update:model-value="clearError('title')"
                                    :ui="{ root: 'w-full' }" />
                            </UFormField>
                            <FormDateField
                                v-model="form.progress_date"
                                label="日期"
                                name="progress_date"
                                :error="errors.progress_date"
                                :disabled="formLoading" />
                        </div>
                        <UCard :ui="{ body: 'space-y-4' }">
                            <FormStatusField
                                v-model="form.status"
                                label="狀態"
                                name="status"
                                :disabled="formLoading" />
                            <UFormField label="排序" name="sort">
                                <UInput
                                    v-model="form.sort"
                                    type="number"
                                    :ui="{ root: 'w-full' }" />
                            </UFormField>
                        </UCard>
                    </div>
                </UCard>

                <UCard :ui="{ body: 'space-y-4' }">
                    <template #header>
                        <h3 class="text-lg font-semibold">工程進度圖片</h3>
                    </template>
                    <ImageUploadMultiple
                        ref="slideUploadRef"
                        v-model="form.images"
                        label="工程進度圖片"
                        name="images"
                        :disabled="formLoading" />
                </UCard>
            </div>

            <section class="frm-ft mt-6">
                <div v-if="submitError" class="mb-4 text-sm text-red-500">
                    {{ submitError }}
                </div>
                <div class="flex gap-4 justify-end">
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
                        :label="props.mode === 'edit' ? '更新' : '新增'" />
                </div>
            </section>
        </UForm>
    </template>
</template>
