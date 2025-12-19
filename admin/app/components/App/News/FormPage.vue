<script setup lang="ts">
import {
    CalendarDate,
    DateFormatter,
    getLocalTimeZone
} from "@internationalized/date";
import type { DateValue } from "@internationalized/date";
import { shallowRef, onMounted } from "vue";

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
// 解析路徑：移除 /add 或 /edit/[id] 後綴，取得基礎路徑
const getBasePath = (path: string): string => {
    // 移除 /edit/[id] 部分
    const editMatch = path.match(/^(.+)\/edit\/\d+$/);
    if (editMatch && editMatch[1]) {
        return editMatch[1];
    }
    // 移除 /add 部分
    return path.replace('/add', '');
};
const pathInfo = resolvePath(getBasePath(router.currentRoute.value.path));
console.log("pathInfo", pathInfo);

// 日期選擇器格式
const df = new DateFormatter("zh-TW", {
    dateStyle: "long"
});
// 日期選擇器
const dateValue = shallowRef(
    form.show_date
        ? new CalendarDate(
              Number(form.show_date.split("-")[0] || "2024"),
              Number(form.show_date.split("-")[1] || "1"),
              Number(form.show_date.split("-")[2] || "1")
          )
        : new CalendarDate(2024, 1, 1)
);
// 日期選擇器文字
const dateText = computed(() => {
    return dateValue.value
        ? df.format(dateValue.value.toDate(getLocalTimeZone()))
        : "請選擇日期";
});

// 封面圖相關（使用 composable）
const coverUpload = useImageUploadSingle({
    onPreviewChange: (preview) => {
        // 可以在此處理預覽變更
    }
});
// 輪播圖相關（使用 composable）
const slideUpload = useImageUploadMultiple({
    enableSortable: true
});


// 載入初始資料
const loadInitialData = (data: any) => {
    if (data) {
        loadNewsData(data.id);
        // 載入封面圖（編輯模式下，封面圖已上傳）
        coverUpload.loadInitialValue(data.cover || null);
        // 載入輪播圖（編輯模式下，輪播圖已上傳）
        slideUpload.loadInitialValue(Array.isArray(data.slide) ? data.slide : []);
    }
};



// HTML 原始碼預覽開關
const showHtmlCode = ref(false);

// 日期選擇器更新
const handleDateUpdate = (
    date:
        | DateValue
        | DateValue[]
        | { start?: DateValue; end?: DateValue }
        | null
        | undefined
) => {
    if (date && !Array.isArray(date) && !("start" in date)) {
        form.show_date = date.toString();
    }
};

// 表單提交
const handleSubmit = async (event?: Event) => {
    console.log(form);
    if (event) event.preventDefault();

    // 在提交前，先上傳待上傳的圖片（如果有臨時 ID）
    // 上傳封面圖
    if (form.cover && form.cover.startsWith("temp_")) {
        const uploadCoverSuccess = await coverUpload.upload();
        if (!uploadCoverSuccess) {
            return;
        }
        // 上傳完成後，更新 form.cover 為正式 URL
        if (coverUpload.formValue.value && !coverUpload.formValue.value.startsWith("temp_")) {
            form.cover = coverUpload.formValue.value;
        } else if (props.mode === "edit" && props.initialData?.cover) {
            // 編輯模式下，如果上傳失敗，保持原值
            form.cover = props.initialData.cover;
        }
    } else if (form.cover && !form.cover.startsWith("temp_")) {
        // 如果已有正式 URL，直接使用
        form.cover = form.cover;
    } else if (props.mode === "edit" && props.initialData?.cover) {
        // 編輯模式下，如果沒有新上傳的圖片，保持原值
        form.cover = props.initialData.cover;
    }

    // 上傳輪播圖
    if (form.slide && form.slide.length > 0 && form.slide.some((slide: string) => slide && slide.startsWith("temp_"))) {
        const uploadSlidesSuccess = await slideUpload.upload();
        if (!uploadSlidesSuccess) {
            return;
        }
        // 上傳完成後，更新 form.slide 為正式 URL
        if (slideUpload.formValue.value && slideUpload.formValue.value.length > 0) {
            form.slide = slideUpload.formValue.value.filter((url: string) => url && !url.startsWith("temp_"));
        } else if (props.mode === "edit" && props.initialData?.slide) {
            // 編輯模式下，如果上傳失敗，保持原值
            form.slide = props.initialData.slide;
        }
    } else if (form.slide && form.slide.length > 0) {
        // 如果已有正式 URL，直接使用（過濾掉臨時 ID）
        form.slide = form.slide.filter((slide: string) => slide && !slide.startsWith("temp_"));
    } else if (props.mode === "edit" && props.initialData?.slide) {
        // 編輯模式下，如果沒有新上傳的圖片，保持原值
        form.slide = props.initialData.slide;
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
            // 根據是否有 structureId 決定導向的路徑
            if (props.structureId) {
                // 如果有 structureId，需要找到對應的路徑
                const targetPath = pathInfo.structure?.url || `/news`;
                setTimeout(() => {
                    router.push(targetPath);
                }, 1000);
            } else {
                setTimeout(() => {
                    router.push("/news");
                }, 1000);
            }
        }
    }

    if (success) {
        emit("submit", form);
    }
};

// 監聽日期變化
watch(() => form.show_date, (newValue) => {
    console.log(newValue);
});


// 綁定封面圖表單數據
// 當出現預覽圖時，使用臨時 ID 更新 form.cover
watch(
    () => coverUpload.preview.value,
    (preview) => {
        if (preview) {
            // 有預覽圖時，更新 form.cover
            // 優先使用 formValue（已上傳的 URL），如果沒有則使用臨時 ID
            if (coverUpload.formValue.value && !coverUpload.formValue.value.startsWith("temp_")) {
                // 已上傳的正式 URL
                form.cover = coverUpload.formValue.value;
            } else if (coverUpload.tempId.value) {
                // 使用臨時 ID 作為標記（用於通過驗證）
                form.cover = coverUpload.tempId.value;
            } else if (coverUpload.formValue.value) {
                // 如果 formValue 是臨時 ID，使用它
                form.cover = coverUpload.formValue.value;
            }
        } else {
            // 沒有預覽圖時，只有在新增模式下才清空，編輯模式下保持原值
            if (props.mode === "add") {
                if (!coverUpload.formValue.value || coverUpload.formValue.value.startsWith("temp_")) {
                    form.cover = "";
                }
            }
            // 編輯模式下，如果沒有預覽圖，保持 form.cover 的當前值（可能是初始載入的值）
        }
    },
    { immediate: true }
);
watch(
    () => coverUpload.formValue.value,
    (newValue) => {
        // 當 formValue 更新時（例如上傳完成），更新 form.cover
        if (newValue && !newValue.startsWith("temp_")) {
            // 只有正式 URL 才更新
            form.cover = newValue;
        } else if (!newValue && !coverUpload.preview.value && props.mode === "add") {
            // 只有在新增模式且沒有預覽圖時才清空
            form.cover = "";
        } else if (newValue && newValue.startsWith("temp_") && coverUpload.tempId.value) {
            // 如果是臨時 ID，確保 form.cover 也是臨時 ID
            form.cover = newValue;
        }
        // 編輯模式下，如果沒有新值，保持 form.cover 的當前值
    }
);
watch(
    () => form.cover,
    (newValue) => {
        // 從外部更新 form.cover 時，同步到 coverUpload（避免循環更新）
        // 只有在沒有預覽圖時才同步，避免覆蓋新上傳的圖片
        if (newValue !== coverUpload.formValue.value && !coverUpload.preview.value) {
            coverUpload.formValue.value = newValue;
        }
    }
);


// 綁定輪播圖表單數據
// 當出現預覽圖時，使用臨時 ID 更新 form.slide
watch(
    () => slideUpload.previews.value.length,
    (previewLength) => {
        if (previewLength > 0) {
            // 有預覽圖時，更新 form.slide 使其長度與預覽圖數量一致
            const formValueLength = slideUpload.formValue.value.length;
            if (formValueLength === previewLength) {
                // 如果 formValue 長度與預覽圖數量一致，使用 formValue（可能是臨時 ID 或正式 URL）
                form.slide = [...slideUpload.formValue.value];
            } else {
                // 如果長度不一致，使用 formValue 並補充臨時 ID
                const newSlide = [...slideUpload.formValue.value];
                // 補充臨時 ID 直到長度一致（用於通過驗證）
                // 注意：這裡的臨時 ID 應該已經在 handleFileSelect 時添加到 formValue 中了
                // 但如果長度不一致，可能是因為某些原因，我們需要確保長度一致
                for (let i = formValueLength; i < previewLength; i++) {
                    // 檢查是否已有臨時 ID，如果沒有則生成一個（但實際上應該已經有了）
                    // 這種情況不應該發生，因為 handleFileSelect 已經處理了
                    // 但為了安全起見，我們保持原值
                    // 注意：這裡的邏輯主要是確保長度一致，實際的臨時 ID 已經在 handleFileSelect 時添加了
                }
                form.slide = newSlide.slice(0, previewLength);
            }
        } else {
            // 沒有預覽圖時，只有在新增模式下才清空，編輯模式下保持原值
            if (props.mode === "add") {
                if (slideUpload.formValue.value.length === 0 || 
                    slideUpload.formValue.value.every((v: string) => v.startsWith("temp_"))) {
                    form.slide = [];
                }
            }
            // 編輯模式下，如果沒有預覽圖，保持 form.slide 的當前值（可能是初始載入的值）
        }
    },
    { immediate: true }
);
watch(
    () => slideUpload.formValue.value,
    (newValue) => {
        // 當 formValue 更新時（例如上傳完成），更新 form.slide
        if (newValue.length > 0) {
            // 使用 formValue（可能包含臨時 ID 或正式 URL）
            form.slide = [...newValue];
        } else if (slideUpload.previews.value.length === 0 && props.mode === "add") {
            // 只有在新增模式且沒有預覽圖時才清空
            form.slide = [];
        }
        // 編輯模式下，如果沒有新值，保持 form.slide 的當前值
    }
);
watch(
    () => form.slide,
    (newValue) => {
        // 從外部更新 form.slide 時，同步到 slideUpload（避免循環更新）
        // 只有在沒有預覽圖時才同步，避免覆蓋新上傳的圖片
        if (
            JSON.stringify(newValue) !==
            JSON.stringify(slideUpload.formValue.value) &&
            slideUpload.previews.value.length === 0
        ) {
            slideUpload.formValue.value = [...(newValue || [])];
        }
    }
);

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

// 初始化時重置表單（僅在新增模式）
onMounted(() => {
    console.log(form.show_date);
    if (props.mode === "add" && !props.initialData) {
        resetForm();
        coverUpload.reset();
        slideUpload.reset();
    }
    // 設置排序功能
    nextTick(() => {
        slideUpload.setupSortable();
    });
});

// 暴露方法給父組件
defineExpose({
    loading: formLoading,
    submit: handleSubmit
});
// console.log(form);
</script>

<template>
    <PageLoading v-if="formLoading" />
    <template v-else>
        <UForm :state="form" @submit="handleSubmit" class="">
            <section class="frm-bd grid grid-cols-1 gap-4">
                <UCard :ui="{ body: 'space-y-4' }">
                    <template #header>
                        <h3 class="text-lg font-semibold">編輯</h3>
                    </template>

                    <FormStatusField
                        v-model="form.status"
                        label="狀態"
                        name="status"
                        :error="errors.status"
                        :disabled="formLoading" />

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

                    <UFormField
                        label="日期"
                        name="show_date"
                        :error="errors.show_date"
                        required>
                        <UPopover
                            :content="{
                                side: 'bottom',
                                align: 'start'
                            }">
                            <UButton
                                color="neutral"
                                variant="outline"
                                icon="i-lucide-calendar"
                                class="w-full">
                                {{ dateText }}
                            </UButton>

                            <template #content>
                                <UCalendar
                                    v-model="dateValue"
                                    class="p-2"
                                    locale="zh-TW"
                                    :ui="{ cell: 'cursor-pointer' }"
                                    @update:model-value="handleDateUpdate" />
                            </template>
                        </UPopover>
                    </UFormField>

                    <UFormField
                        label="代表圖檔"
                        name="cover"
                        :error="errors.cover"
                        required>
                        <div class="space-y-2">
                            <input
                                :ref="coverUpload.inputRef"
                                type="file"
                                accept="image/*"
                                class="hidden"
                                @change="coverUpload.handleFileSelect" />
                            <div
                                v-if="coverUpload.preview.value || form.cover"
                                class="relative w-full max-w-lg">
                                <img
                                    :src="coverUpload.preview.value || form.cover || ''"
                                    alt="封面圖預覽"
                                    class="w-full max-w-lg object-cover rounded-lg border"
                                    style="max-height: 300px" />
                                <UButton
                                    icon="i-lucide-x"
                                    size="xs"
                                    color="error"
                                    variant="solid"
                                    class="absolute top-2 right-2"
                                    @click="coverUpload.remove" />
                            </div>
                            <UButton
                                :label="
                                    coverUpload.preview.value || form.cover
                                        ? '更換圖片'
                                        : '上傳圖片'
                                "
                                icon="i-lucide-upload"
                                color="primary"
                                variant="outline"
                                block
                                :loading="coverUpload.isUploading.value"
                                :disabled="coverUpload.isUploading.value || formLoading"
                                @click="coverUpload.triggerFileSelect" />
                        </div>
                    </UFormField>

                    <UFormField
                        label="輪播圖"
                        name="slide"
                        :error="errors.slide"
                        required>
                        <div class="space-y-2">
                            <input
                                :ref="slideUpload.inputRef"
                                type="file"
                                accept="image/*"
                                class="hidden"
                                multiple
                                @change="slideUpload.handleFileSelect" />
                            <div
                                v-if="slideUpload.sortableData.value.length > 0"
                                :ref="slideUpload.sortableListRef"
                                class="grid grid-cols-5 gap-2">
                                <div
                                    v-for="(imageId, index) in slideUpload.sortableData.value"
                                    :key="imageId"
                                    class="relative group">
                                    <img
                                        :src="(slideUpload.previews.value && slideUpload.previews.value[index]) || ''"
                                        :alt="`輪播圖 ${index + 1}`"
                                        class="w-full object-cover rounded-lg border aspect-square"/>
                                    <!-- 拖動把手 -->
                                    <div
                                        class="drag-handle absolute top-2 left-2 cursor-grab active:cursor-grabbing bg-black/50 hover:bg-black/70 rounded p-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <UIcon
                                            name="i-lucide-grip-vertical"
                                            class="w-4 h-4 text-white" />
                                    </div>
                                    <!-- 刪除按鈕 -->
                                    <UButton
                                        icon="i-lucide-x"
                                        size="xs"
                                        color="error"
                                        variant="solid"
                                        class="absolute top-2 right-2"
                                        @click="slideUpload.remove(index)" />
                                </div>
                            </div>
                            <UButton
                                label="新增輪播圖（可多選）"
                                icon="i-lucide-plus"
                                color="primary"
                                variant="outline"
                                block
                                :loading="slideUpload.isUploading.value"
                                :disabled="slideUpload.isUploading.value || formLoading"
                                @click="slideUpload.triggerFileSelect" />
                        </div>
                    </UFormField>

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
                        :color="mode === 'add' ? 'primary' : 'success'"
                        :icon="mode === 'add' ? 'lucide:plus' : 'lucide:save'"
                        :loading="formLoading"
                        :disabled="formLoading"
                        @click="handleSubmit()"
                        :label="
                            mode === 'add' ? '新增' : '更新'
                        " />
                </div>
            </section>
        </UForm>
    </template>
</template>
