<script setup lang="ts">
const router = useRouter();
const toast = useToast();
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

// 圖片上傳（多圖）
const imagesUpload = useImageUploadMultiple({
    enableSortable: true
});

// 載入初始資料
const loadInitialData = async (data: any) => {
    if (!data) {
        resetForm();
        return;
    }
    if (data.id && (data.images === undefined || data.images === null)) {
        await loadProgressData(Number(data.id));
    } else {
        // 若外部直接傳入資料
        resetForm();
        form.case_id =
            data.case_id !== null && data.case_id !== undefined
                ? Number(data.case_id)
                : null;
        form.title = data.title ?? "";
        form.progress_date = data.progress_date ?? "";
        form.images = Array.isArray(data.images) ? data.images : [];
        form.sort = data.sort ?? 0;
        form.status = data.status ?? 1;
    }
    // 載入圖片預設值
    imagesUpload.loadInitialValue(
        Array.isArray(form.images) ? form.images : []
    );
};

// 提交
const handleSubmit = async (event?: Event) => {
    if (event) event.preventDefault();

    // 處理圖片上傳
    if (form.images.some((img: string) => img && img.startsWith("temp_"))) {
        const uploaded = await imagesUpload.upload();
        if (!uploaded) return;
        if (imagesUpload.formValue.value?.length) {
            form.images = imagesUpload.formValue.value.filter(
                (url: string) => url && !url.startsWith("temp_")
            );
        }
    } else if (imagesUpload.formValue.value?.length) {
        form.images = imagesUpload.formValue.value;
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

// 綁定圖片
watch(
    () => imagesUpload.formValue.value,
    (newValue) => {
        if (Array.isArray(newValue)) {
            form.images = [...newValue];
        }
    },
    { immediate: true }
);

// 啟用排序
watch(
    () => ({
        length: imagesUpload.sortableData.value.length,
        listRef: imagesUpload.sortableListRef.value
    }),
    ({ length, listRef }) => {
        if (length > 0 && listRef) {
            nextTick(() => imagesUpload.setupSortable());
        }
    },
    { immediate: true }
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
                                    " />
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
                    <UFormField label="圖片" name="images">
                        <div class="space-y-2">
                            <input
                                :ref="imagesUpload.inputRef"
                                type="file"
                                accept="image/*"
                                class="hidden"
                                multiple
                                @change="imagesUpload.handleFileSelect" />
                            <div
                                v-if="
                                    imagesUpload.sortableData.value.length > 0
                                "
                                :ref="imagesUpload.sortableListRef"
                                class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-2">
                                <div
                                    v-for="(imageId, index) in imagesUpload
                                        .sortableData.value"
                                    :key="imageId"
                                    :data-image-id="imageId"
                                    class="relative group">
                                    <img
                                        :src="
                                            (imagesUpload.previews.value &&
                                                imagesUpload.previews.value[
                                                    index
                                                ]) ||
                                            ''
                                        "
                                        :alt="`工程進度圖 ${index + 1}`"
                                        class="w-full object-cover rounded-lg border aspect-square" />
                                    <div
                                        class="drag-handle absolute top-2 left-2 cursor-grab active:cursor-grabbing bg-black/50 hover:bg-black/70 rounded p-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <UIcon
                                            name="i-lucide-grip-vertical"
                                            class="w-4 h-4 text-white" />
                                    </div>
                                    <UButton
                                        icon="i-lucide-x"
                                        size="xs"
                                        color="error"
                                        variant="solid"
                                        class="absolute top-2 right-2"
                                        @click="imagesUpload.remove(index)" />
                                </div>
                            </div>
                            <UButton
                                label="新增圖片（可多選）"
                                icon="i-lucide-plus"
                                color="primary"
                                variant="outline"
                                block
                                :loading="imagesUpload.isUploading.value"
                                :disabled="imagesUpload.isUploading.value"
                                @click="imagesUpload.triggerFileSelect" />
                        </div>
                    </UFormField>
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
