<script setup lang="ts">
import type { CutSectionData } from "~/types/CutSectionField";

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
    editCase
} = useAppCase();

const { resolvePath } = useStructureResolver();
const getBasePath = (path: string): string => {
    const editMatch = path.match(/^(.+)\/edit\/\d+$/);
    if (editMatch && editMatch[1]) {
        return editMatch[1];
    }
    return path.replace("/add", "");
};
const pathInfo = resolvePath(getBasePath(router.currentRoute.value.path));

// 圖片上傳
const coverUpload = useImageUploadSingle();
const slideUpload = useImageUploadMultiple({
    enableSortable: true
});

// 內容區塊（沿用 About 模組的切卡）
const sections = computed<CutSectionData[]>(
    () => (form.content ?? []) as CutSectionData[]
);

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

const updateSection = (updated: CutSectionData) => {
    const idx = sections.value.findIndex((s) => s.id === updated.id);
    if (idx !== -1) {
        sections.value[idx] = { ...updated };
    } else {
        sections.value.push({ ...updated });
    }
};

const deleteSection = (sectionId: string) => {
    const idx = sections.value.findIndex((s) => s.id === sectionId);
    if (idx !== -1) {
        sections.value.splice(idx, 1);
        sections.value.forEach((s, i) => (s.index = i + 1));
    }
};

const moveSection = (sectionId: string, direction: "up" | "down") => {
    const currentIndex = sections.value.findIndex((s) => s.id === sectionId);
    if (currentIndex === -1) return;
    const targetIndex =
        direction === "up" ? currentIndex - 1 : currentIndex + 1;
    if (targetIndex < 0 || targetIndex >= sections.value.length) return;
    const cloned = [...sections.value];
    const current = cloned[currentIndex];
    const target = cloned[targetIndex];
    if (!current || !target) return;
    cloned[currentIndex] = target;
    cloned[targetIndex] = current;
    cloned.forEach((s, i) => (s.index = i + 1));
    form.content = cloned;
};

// 載入初始資料
const loadInitialData = async (data: any) => {
    if (!data) {
        resetForm();
        return;
    }
    if (data.id && (data.content === undefined || data.content === null)) {
        await loadCaseData(Number(data.id));
    } else {
        // 若外部直接傳入資料
        resetForm();
        form.year = data.year ?? null;
        form.title = data.title ?? "";
        form.s_text = data.s_text ?? "";
        form.cover = data.cover ?? "";
        form.slide = Array.isArray(data.slide) ? data.slide : [];
        form.content = Array.isArray(data.content) ? data.content : [];
        form.ca_type = data.ca_type ?? "";
        form.ca_area = data.ca_area ?? "";
        form.ca_square = data.ca_square ?? "";
        form.ca_phone = data.ca_phone ?? "";
        form.ca_adds = data.ca_adds ?? "";
        form.ca_map = data.ca_map ?? "";
        form.ca_pop_type = data.ca_pop_type ?? "";
        form.ca_pop_img = data.ca_pop_img ?? "";
        form.is_sale = data.is_sale ?? 0;
        form.is_msg = data.is_msg ?? 0;
        form.sort = data.sort ?? 0;
        form.status = data.status ?? 1;
    }
    // 載入圖片預設值
    coverUpload.loadInitialValue(form.cover || null);
    slideUpload.loadInitialValue(Array.isArray(form.slide) ? form.slide : []);
};

// 監聽 initialData
watch(
    () => props.initialData,
    (data) => {
        loadInitialData(data);
    },
    { immediate: true, deep: true }
);

// 綁定封面圖
watch(
    () => coverUpload.preview.value,
    (preview) => {
        if (preview) {
            form.cover =
                coverUpload.formValue.value &&
                !coverUpload.formValue.value.startsWith("temp_")
                    ? coverUpload.formValue.value
                    : coverUpload.tempId.value ||
                      coverUpload.formValue.value ||
                      form.cover;
        } else if (props.mode === "add") {
            form.cover = "";
        }
    },
    { immediate: true }
);
watch(
    () => coverUpload.formValue.value,
    (newValue) => {
        if (newValue && !newValue.startsWith("temp_")) {
            form.cover = newValue;
        }
    }
);

// 綁定輪播圖
watch(
    () => slideUpload.formValue.value,
    (newValue) => {
        if (Array.isArray(newValue)) {
            form.slide = [...newValue];
        }
    },
    { immediate: true }
);

// 啟用排序
watch(
    () => ({
        length: slideUpload.sortableData.value.length,
        listRef: slideUpload.sortableListRef.value
    }),
    ({ length, listRef }) => {
        if (length > 0 && listRef) {
            nextTick(() => slideUpload.setupSortable());
        }
    },
    { immediate: true }
);

// 提交
const handleSubmit = async (event?: Event) => {
    if (event) event.preventDefault();
    // 先處理封面
    if (form.cover && form.cover.startsWith("temp_")) {
        const uploaded = await coverUpload.upload();
        if (!uploaded) return;
        if (
            coverUpload.formValue.value &&
            !coverUpload.formValue.value.startsWith("temp_")
        ) {
            form.cover = coverUpload.formValue.value;
        }
    }
    // 處理輪播
    if (form.slide.some((s: string) => s && s.startsWith("temp_"))) {
        const uploaded = await slideUpload.upload();
        if (!uploaded) return;
        if (slideUpload.formValue.value?.length) {
            form.slide = slideUpload.formValue.value.filter(
                (url: string) => url && !url.startsWith("temp_")
            );
        }
    } else if (slideUpload.formValue.value?.length) {
        form.slide = slideUpload.formValue.value;
    }

    let success = false;
    if (props.mode === "edit") {
        const caseId = props.initialData?.id;
        if (!caseId) {
            submitError.value = "缺少建案 ID";
            return;
        }
        success = await editCase(caseId);
    } else {
        success = await addCase(props.structureId ?? null);
        if (success) {
            const targetPath = pathInfo.structure?.url || `/case`;
            setTimeout(() => {
                router.push(targetPath);
            }, 600);
        }
    }
    if (success) {
        emit("submit", form);
    }
};

defineExpose({
    loading: formLoading,
    submit: handleSubmit,
    addCutSection
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
                            <div class="flex items-center gap-2">
                                <UBadge
                                    color="primary"
                                    variant="soft"
                                    size="xs"
                                    label="建案" />
                            </div>
                        </div>
                    </template>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-4">
                            <UFormField label="年份" name="year">
                                <UInput
                                    v-model="form.year"
                                    type="number"
                                    placeholder="例如 2025"
                                    :ui="{ root: 'w-full' }" />
                            </UFormField>
                            <UFormField
                                label="標題"
                                name="title"
                                :error="errors.title">
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
                            <UFormField
                                label="封面圖"
                                name="cover"
                                :error="errors.cover">
                                <div class="space-y-2">
                                    <input
                                        ref="coverUpload.inputRef"
                                        type="file"
                                        accept="image/*"
                                        class="hidden"
                                        @change="
                                            coverUpload.handleFileSelect
                                        " />
                                    <div
                                        v-if="
                                            coverUpload.preview.value ||
                                            form.cover
                                        "
                                        class="relative w-full max-w-lg">
                                        <img
                                            :src="
                                                coverUpload.preview.value ||
                                                form.cover
                                            "
                                            alt="封面預覽"
                                            class="w-full max-w-lg object-cover rounded-lg border" />
                                        <UButton
                                            icon="i-lucide-x"
                                            size="xs"
                                            color="error"
                                            variant="solid"
                                            class="absolute top-2 right-2"
                                            @click="
                                                () => {
                                                    coverUpload.remove();
                                                    form.cover = '';
                                                }
                                            " />
                                    </div>
                                    <UButton
                                        :label="
                                            coverUpload.preview.value ||
                                            form.cover
                                                ? '更換圖片'
                                                : '上傳圖片'
                                        "
                                        icon="i-lucide-upload"
                                        color="primary"
                                        variant="outline"
                                        block
                                        :loading="coverUpload.isUploading.value"
                                        :disabled="
                                            coverUpload.isUploading.value
                                        "
                                        @click="
                                            coverUpload.triggerFileSelect
                                        " />
                                    <div
                                        v-if="errors.cover"
                                        class="text-sm text-red-500">
                                        {{ errors.cover }}
                                    </div>
                                </div>
                            </UFormField>
                        </div>
                        <UCard :ui="{ body: 'space-y-4' }">
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
                            <USwitch
                                unchecked-icon="i-lucide-x"
                                checked-icon="i-lucide-check"
                                :model-value="form.status === 1"
                                @update:model-value="(val: boolean) => (form.status = val ? 1 : 0)"
                                label="是否上線" />
                        </UCard>
                    </div>
                </UCard>

                <UCard :ui="{ body: 'space-y-4' }">
                    <template #header>
                        <h3 class="text-lg font-semibold">內容編輯</h3>
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
                        <UFormField label="建案嵌入地圖" name="ca_map">
                            <UTextarea
                                v-model="form.ca_map"
                                placeholder="貼上地圖 iframe 或網址"
                                :rows="3"
                                autoresize
                                :ui="{ root: 'w-full' }" />
                        </UFormField>
                        <UFormField label="彈窗類型" name="ca_pop_type">
                            <UInput
                                v-model="form.ca_pop_type"
                                placeholder="輸入彈窗類型"
                                :ui="{ root: 'w-full' }" />
                        </UFormField>
                        <UFormField label="彈窗圖片" name="ca_pop_img">
                            <UInput
                                v-model="form.ca_pop_img"
                                placeholder="圖片 URL"
                                :ui="{ root: 'w-full' }" />
                        </UFormField>

                    </div>
                    <UFormField
                        label="輪播圖"
                        name="slide"
                        :error="errors.slide">
                        <div class="space-y-2">
                            <input
                                :ref="slideUpload.inputRef"
                                type="file"
                                accept="image/*"
                                class="hidden"
                                multiple
                                @change="slideUpload.handleFileSelect" />
                            <div
                                v-if="
                                    slideUpload.sortableData.value.length >
                                    0
                                "
                                :ref="slideUpload.sortableListRef"
                                class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-2">
                                <div
                                    v-for="(imageId, index) in slideUpload
                                        .sortableData.value"
                                    :key="imageId"
                                    :data-image-id="imageId"
                                    class="relative group">
                                    <img
                                        :src="
                                            (slideUpload.previews.value &&
                                                slideUpload.previews.value[
                                                    index
                                                ]) ||
                                            ''
                                        "
                                        :alt="`輪播圖 ${index + 1}`"
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
                                        @click="
                                            slideUpload.remove(index)
                                        " />
                                </div>
                            </div>
                            <UButton
                                label="新增輪播圖（可多選）"
                                icon="i-lucide-plus"
                                color="primary"
                                variant="outline"
                                block
                                :loading="slideUpload.isUploading.value"
                                :disabled="slideUpload.isUploading.value"
                                @click="slideUpload.triggerFileSelect" />
                            <div
                                v-if="errors.slide"
                                class="text-sm text-red-500">
                                {{ errors.slide }}
                            </div>
                        </div>
                    </UFormField>
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
                            <AppAboutCutSection
                                v-for="section in sections"
                                :key="section.id"
                                :index="section.index"
                                :data="section"
                                :can-move-up="section.index > 1"
                                :can-move-down="section.index < sections.length"
                                @update="updateSection"
                                @delete-request="(id) => deleteSection(id)"
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
                        :to="pathInfo.structure?.url || '/case'"
                        label="取消" />
                    <UButton
                        type="button"
                        color="primary"
                        icon="lucide:save"
                        :loading="formLoading"
                        :disabled="formLoading"
                        @click="handleSubmit()"
                        :label="props.mode === 'edit' ? '更新' : '新增'" />
                </div>
            </section>
        </UForm>
    </template>
</template>
