<script setup lang="ts">
import type { CutSectionData } from "~/types/CutSectionField";
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
    loadCaseData,
    resetForm,
    addCase,
    editCase,
} = useAppCase();

const { resolvePath } = useStructureResolver();
const { getBasePath } = useBasePath();
const basePath = getBasePath(router.currentRoute.value.path);
const pathInfo = resolvePath(basePath);

// 側邊欄預覽功能
const preview = useFormPreview({
    defaultOpen: false,
    width: "500px",
    title: "建案預覽"
});

// 圖片上傳元件引用（用於調用上傳方法）
const coverUploadRef = ref<InstanceType<typeof ImageUploadSingle> | null>(null);
const slideUploadRef = ref<InstanceType<typeof ImageUploadMultiple> | null>(null);
const popImgUploadRef = ref<InstanceType<typeof ImageUploadSingle> | null>(null);

// 內容區塊（沿用 About 模組的切卡）
// 注意：區塊操作（新增、刪除、移動）會更新表單狀態，但不會立即儲存
// 需要點擊「新增」或「更新」按鈕提交表單時才會一起儲存
// 提交時會使用 props.structureId 來確保資料對應到正確的單元
const sections = computed<CutSectionData[]>(
    () => (form.content ?? []) as CutSectionData[]
);

// 新增區塊
// 注意：新增區塊後不會立即儲存，需要用戶點擊「新增」或「更新」按鈕才會儲存
// 儲存時會使用 props.structureId 來確保資料對應到正確的單元
const addCutSection = async () => {
    const newSectionId = `section-${Date.now()}`;
    (form.content as CutSectionData[]).push({
        id: newSectionId,
        index: sections.value.length + 1,
        fields: []
    });
    await nextTick();
    const target = document.querySelector(`[data-id="${newSectionId}"]`);
    if (target) {
        target.scrollIntoView({ behavior: "smooth", block: "start" });
    }
};

// 更新區塊資料
// 注意：更新區塊後不會立即儲存，需要用戶點擊「新增」或「更新」按鈕才會儲存
// 儲存時會使用 props.structureId 來確保資料對應到正確的單元
const updateSection = (updated: CutSectionData) => {
    const currentContent = (form.content as CutSectionData[]) || [];
    const idx = currentContent.findIndex((s) => s.id === updated.id);
    
    if (idx !== -1) {
        // 更新現有區塊
        const updatedContent = [...currentContent];
        updatedContent[idx] = { ...updated };
        form.content = updatedContent;
    } else {
        // 新增區塊
        form.content = [...currentContent, { ...updated }];
    }
};

// 移動區塊排序
// 確保使用 props.structureId 來儲存，避免資料錯亂
// 注意：移動區塊後不會立即儲存，需要用戶點擊「新增」或「更新」按鈕才會儲存
const moveSection = async (sectionId: string, direction: "up" | "down") => {
    const currentIndex = sections.value.findIndex((s) => s.id === sectionId);
    if (currentIndex === -1) return;

    const targetIndex =
        direction === "up" ? currentIndex - 1 : currentIndex + 1;
    if (targetIndex < 0 || targetIndex >= sections.value.length) return;

    // 深拷貝陣列和對象，確保響應式更新
    const clonedSections = sections.value.map((section) => ({
        ...section,
        fields: [...section.fields]
    }));
    
    // 交換位置
    [clonedSections[currentIndex], clonedSections[targetIndex]] = [
        clonedSections[targetIndex] as CutSectionData,
        clonedSections[currentIndex] as CutSectionData
    ];

    // 重新計算索引，創建新對象以觸發響應式更新
    const updatedSections = clonedSections.map((section, idx) => ({
        ...section,
        index: idx + 1
    }));
    
    form.content = updatedSections;

    // 只在編輯模式下立即儲存
    if (props.mode === "edit" && props.initialData?.id) {
        await editCase(Number(props.initialData.id));
    }
};

const deleteConfirmModalOpen = ref(false);
const deleteTarget = ref<{ id: string; label: string } | null>(null);

// 刪除區塊
// 確保使用 props.structureId 來儲存，避免資料錯亂
// 注意：刪除區塊後不會立即儲存，需要用戶點擊「新增」或「更新」按鈕才會儲存
const deleteSection = async (sectionId: string) => {
    const index = sections.value.findIndex((s) => s.id === sectionId);
    if (index !== -1) {
        // 創建新陣列，移除指定區塊並重新計算索引
        const updatedSections = sections.value
            .filter((s) => s.id !== sectionId)
            .map((section, idx) => ({
                ...section,
                index: idx + 1
            }));
        
        form.content = updatedSections;
        console.log(`第${index + 1}卡已刪除:`, sectionId);
    }
    
    // 只在編輯模式下立即儲存
    if (props.mode === "edit" && props.initialData?.id) {
        await editCase(Number(props.initialData.id));
    }
};


const requestDeleteSection = (sectionId: string, label: string) => {
    deleteTarget.value = { id: sectionId, label };
    deleteConfirmModalOpen.value = true;
};

const confirmDeleteSection = () => {
    if (deleteTarget.value) {
        deleteSection(deleteTarget.value.id);
    }
    deleteConfirmModalOpen.value = false;
    deleteTarget.value = null;
};

// 載入初始資料
const loadInitialData = async (data: any) => {
    if (data) {
        await loadCaseData(Number(data.id));
    }
    else {
        resetForm();
    }
    // 元件會自動監聽 modelValue 變化並載入初始值，無需手動調用
};
// 提交
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
            // 直接使用元件的 formValue（已經過濾掉臨時 ID）
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

    // 處理彈窗圖片（僅當類型為圖片時）
    if (
        form.ca_pop_type === 1 &&
        form.ca_pop &&
        form.ca_pop.startsWith("temp_")
    ) {
        const uploaded = await popImgUploadRef.value?.upload();
        if (!uploaded) return;
        // 上傳完成後，form.ca_pop 會自動更新（通過 v-model）
    }

    let success = false;
    if (props.mode === "edit") {
        const caseId = props.initialData?.id;
        if (!caseId) {
            submitError.value = "缺少建案 ID";
            return;
        }
        // 編輯模式：使用建案 ID 更新，不改變 structure_id
        success = await editCase(caseId);
    } else {
        // 新增模式：使用 structureId 確保資料對應到正確的單元
        success = await addCase(props.structureId ?? null);
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

// 監聽彈窗類型變化，切換時清空內容
watch(
    () => form.ca_pop_type,
    (newType, oldType) => {
        if (newType !== oldType) {
            if (newType === 0) {
                // 不顯示時清空
                form.ca_pop = "";
                popImgUploadRef.value?.remove();
            } else if (newType === 2) {
                // 切換到影片時清空圖片
                popImgUploadRef.value?.remove();
            } else if (newType === 1 && oldType === 2) {
                // 從影片切換到圖片時清空連結
                form.ca_pop = "";
            }
        }
    }
);

// 排序功能由 ImageUploadMultiple 元件內部處理，無需手動設置

// 監聽表單數據變化，即時更新預覽
watch(
    () => ({
        title: form.title,
        content: form.content,
        year: form.year,
        s_text: form.s_text,
        cover: form.cover,
        ca_type: form.ca_type,
        ca_area: form.ca_area,
        ca_square: form.ca_square,
        ca_phone: form.ca_phone,
        ca_adds: form.ca_adds,
        ca_map: form.ca_map,
        ca_pop_type: form.ca_pop_type,
        ca_pop: form.ca_pop,
        slide: form.slide
    }),
    () => {
        console.log("[preview] form", form);
        preview.updatePreview(
            {
                title: form.title,
                content: JSON.stringify(form.content),
                year: form.year,
                s_text: form.s_text,
                cover: form.cover,
                slide: form.slide,
                ca_type: form.ca_type,
                ca_area: form.ca_area,
                ca_square: form.ca_square,
                ca_phone: form.ca_phone,
                ca_adds: form.ca_adds,
                ca_map: form.ca_map,
                ca_pop_type: form.ca_pop_type,
                ca_pop: form.ca_pop,
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


onMounted(() => {
    console.log("[formPage] form", form);
});

defineExpose({
    loading: formLoading,
    submit: handleSubmit,
    addCutSection,
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
                            <UFormField label="年份" name="year" :error="errors.year" required>
                                <UInput
                                    v-model="form.year"
                                    type="number"
                                    placeholder="例如 2025"
                                    :ui="{ root: 'w-full' }"
                                    @update:model-value="clearError('year')" />
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
                            <UFormField label="小字" name="s_text">
                                <UInput
                                    v-model="form.s_text"
                                    placeholder="請輸入小字"
                                    :ui="{ root: 'w-full' }" />
                            </UFormField>
                            <ImageUploadSingle
                                ref="coverUploadRef"
                                v-model="form.cover"
                                label="封面圖"
                                name="cover"
                                description="建議尺寸：1920x1080"
                                :error="errors.cover"
                                :disabled="formLoading"
                                :ui="{ description: 'text-sm text-primary-500' }" />
                        </div>
                        <UCard :ui="{ body: 'space-y-4' }">
                            <FormStatusField
                                v-model="form.status"
                                label="狀態"
                                name="status"
                                :disabled="formLoading" />
                            <UFormField label="排序" name="sort">
                                <UInput v-model="form.sort" type="number" />
                            </UFormField>
                            <USwitch
                                unchecked-icon="i-lucide-x"
                                checked-icon="i-lucide-check"
                                :model-value="form.is_sale === 1"
                                @update:model-value="(val: boolean) => (form.is_sale = val ? 1 : 0)"
                                label="是否完售" />
                            <USwitch
                                unchecked-icon="i-lucide-x"
                                checked-icon="i-lucide-check"
                                :model-value="form.is_msg === 1"
                                @update:model-value="(val: boolean) => (form.is_msg = val ? 1 : 0)"
                                label="是否預約賞屋" />
                        </UCard>
                    </div>
                </UCard>

                <UCard :ui="{ body: 'space-y-4' }">
                    <template #header>
                        <h3 class="text-lg font-semibold">建案資訊</h3>
                    </template>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <UFormField label="建案規劃" name="ca_type">
                            <UInput
                                v-model="form.ca_type"
                                placeholder="請輸入建案規劃"
                                :ui="{ root: 'w-full' }" />
                        </UFormField>
                        <UFormField label="座落地點" name="ca_area">
                            <UInput
                                v-model="form.ca_area"
                                placeholder="請輸入座落地點"
                                :ui="{ root: 'w-full' }" />
                        </UFormField>
                        <UFormField label="坪數規劃" name="ca_square">
                            <UInput
                                v-model="form.ca_square"
                                placeholder="請輸入坪數規劃"
                                :ui="{ root: 'w-full' }" />
                        </UFormField>
                        <UFormField label="諮詢專線" name="ca_phone">
                            <UInput
                                v-model="form.ca_phone"
                                placeholder="請輸入諮詢專線"
                                :ui="{ root: 'w-full' }" />
                        </UFormField>
                        <UFormField label="接待會館" name="ca_adds">
                            <UInput
                                v-model="form.ca_adds"
                                placeholder="請輸入接待會館"
                                :ui="{ root: 'w-full' }" />
                        </UFormField>
                        <UFormField label="google地圖" name="ca_map">
                            <UTextarea
                                v-model="form.ca_map"
                                placeholder="可至google地圖查詢地址並點選分享->嵌入地圖->複製HTML，將其程式碼貼上即可"
                                :rows="3"
                                autoresize
                                :ui="{ root: 'w-full' }" />
                        </UFormField>
                        <UFormField label="彈窗類型" name="ca_pop_type">
                            <USelect
                                v-model="form.ca_pop_type"
                                :items="[
                                    { label: '不顯示', value: 0 },
                                    { label: '圖片', value: 1 },
                                    { label: '影片', value: 2 }
                                ]" />
                        </UFormField>
                        <ImageUploadSingle
                            v-if="form.ca_pop_type === 1"
                            ref="popImgUploadRef"
                            v-model="form.ca_pop"
                            label="彈窗圖片"
                            name="ca_pop"
                            :disabled="formLoading" />
                        <UFormField
                            v-if="form.ca_pop_type === 2"
                            label="彈窗影片連結"
                            name="ca_pop">
                            <UInput
                                v-model="form.ca_pop"
                                placeholder="請輸入影片連結 URL"
                                :ui="{ root: 'w-full' }" />
                        </UFormField>
                    </div>
                    <ImageUploadMultiple
                        ref="slideUploadRef"
                        v-model="form.slide"
                        label="輪播圖"
                        name="slide"
                        :disabled="formLoading" />
                </UCard>

                <UCard :ui="{ body: 'space-y-4' }">
                    <template #header>
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold">內容區塊</h3>
                            <UButton
                                label="新增區塊(卡)"
                                color="primary"
                                icon="i-lucide-plus"
                                @click="addCutSection" />
                        </div>
                    </template>
                    <div class="space-y-4">
                        <template v-if="sections.length === 0">
                            <div
                                class="text-center py-10 text-gray-400 border-2 border-dashed rounded-lg">
                                <UIcon
                                    name="i-lucide-layout-template"
                                    class="w-10 h-10 mx-auto mb-2" />
                                <p>尚未新增任何內容區塊</p>
                            </div>
                        </template>
                        <template v-else>
                            <CutSection
                                v-for="section in sections"
                                :key="section.id"
                                :index="section.index"
                                :data="section"
                                :can-move-up="section.index > 1"
                                :can-move-down="section.index < sections.length"
                                @update="updateSection"
                                @delete-request="requestDeleteSection(section.id, `第${section.index}卡內容編輯`)"
                                @move-up="moveSection(section.id, 'up')"
                                @move-down="moveSection(section.id, 'down')" />
                        </template>
                    </div>
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
        <DeleteConfirmModal
        v-model:open="deleteConfirmModalOpen"
        title="確認刪除區塊"
        :description="
            deleteTarget
                ? `確定要刪除「${deleteTarget.label}」嗎？此操作無法復原，區塊內的所有欄位資料將會被永久刪除。`
                : ''
        "
        :on-confirm="confirmDeleteSection" />
    </template>
</template>
