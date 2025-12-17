<script setup lang="ts">
import {
    CalendarDate,
    DateFormatter,
    getLocalTimeZone
} from "@internationalized/date";
import type { DateValue } from "@internationalized/date";

const props = withDefaults(
    defineProps<{
        mode: "add" | "edit";
        initialData?: any;
        loading?: boolean;
    }>(),
    {
        loading: false
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
    loadFormData,
    resetForm,
    addNews,
    editNews
} = useAppNews();
// form.show_date = new Date().toISOString().split("T")[0] || "";

const { uploadImage, getImagePreview } = useImageUpload();
const toast = useToast();

const df = new DateFormatter("zh-TW", {
    dateStyle: "long"
});
const dateValue = shallowRef(
    new CalendarDate(
        Number(form.show_date.split("-")[0]),
        Number(form.show_date.split("-")[1]),
        Number(form.show_date.split("-")[2])
    )
);
// 封面圖相關
const coverInputRef = ref<HTMLInputElement | null>(null);
const coverPreview = ref<string | null>(null);
const isUploadingCover = ref(false);

// 輪播圖相關
const slideInputRef = ref<HTMLInputElement | null>(null);
const slidePreviews = ref<string[]>([]);
const isUploadingSlide = ref(false);

// 載入初始資料
const loadInitialData = (data: any) => {
    if (data) {
        loadFormData(data);
        // 載入封面圖預覽
        if (data.cover) {
            coverPreview.value = data.cover;
        } else {
            coverPreview.value = null;
        }
        // 載入輪播圖預覽（與 form.slide 同步）
        if (Array.isArray(form.slide) && form.slide.length > 0) {
            slidePreviews.value = [...form.slide];
        } else {
            slidePreviews.value = [];
        }
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

// 處理封面圖上傳
const handleCoverUpload = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) return;

    if (!file.type.startsWith("image/")) {
        toast.add({
            title: "檔案格式錯誤",
            description: "請選擇圖片檔案",
            color: "error"
        });
        return;
    }

    const maxSize = 5 * 1024 * 1024; // 5MB
    if (file.size > maxSize) {
        toast.add({
            title: "檔案過大",
            description: "圖片大小不能超過 5MB",
            color: "error"
        });
        return;
    }

    isUploadingCover.value = true;

    try {
        const preview = await getImagePreview(file);
        coverPreview.value = preview;

        const uploadedUrl = await uploadImage(file);
        if (uploadedUrl) {
            form.cover = uploadedUrl;
            coverPreview.value = uploadedUrl;
        }
    } catch (error) {
        console.error("封面圖處理錯誤:", error);
    } finally {
        isUploadingCover.value = false;
        if (coverInputRef.value) {
            coverInputRef.value.value = "";
        }
    }
};

const triggerCoverUpload = () => {
    coverInputRef.value?.click();
};

const removeCover = () => {
    coverPreview.value = null;
    form.cover = "";
    if (coverInputRef.value) {
        coverInputRef.value.value = "";
    }
};

// 處理輪播圖上傳
const handleSlideUpload = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) return;

    if (!file.type.startsWith("image/")) {
        toast.add({
            title: "檔案格式錯誤",
            description: "請選擇圖片檔案",
            color: "error"
        });
        return;
    }

    const maxSize = 5 * 1024 * 1024; // 5MB
    if (file.size > maxSize) {
        toast.add({
            title: "檔案過大",
            description: "圖片大小不能超過 5MB",
            color: "error"
        });
        return;
    }

    isUploadingSlide.value = true;

    try {
        const preview = await getImagePreview(file);
        slidePreviews.value.push(preview);

        const uploadedUrl = await uploadImage(file);
        if (uploadedUrl) {
            form.slide.push(uploadedUrl);
            // 更新預覽為上傳後的 URL
            const index = slidePreviews.value.length - 1;
            if (index >= 0) {
                slidePreviews.value[index] = uploadedUrl;
            }
        } else {
            // 上傳失敗，移除預覽
            slidePreviews.value.pop();
        }
    } catch (error) {
        console.error("輪播圖處理錯誤:", error);
        slidePreviews.value.pop();
    } finally {
        isUploadingSlide.value = false;
        if (slideInputRef.value) {
            slideInputRef.value.value = "";
        }
    }
};

const triggerSlideUpload = () => {
    slideInputRef.value?.click();
};

const removeSlide = (index: number) => {
    form.slide.splice(index, 1);
    // 同步更新預覽
    if (slidePreviews.value.length > index) {
        slidePreviews.value.splice(index, 1);
    }
};

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

const handleSubmit = async (event?: Event) => {
    console.log(form);
    if (event) event.preventDefault();

    // if (props.mode === "edit") {
    //     const newsId = props.initialData?.id;
    //     if (!newsId) {
    //         submitError.value = "缺少最新消息 ID";
    //         return;
    //     }
    //     const success = await editNews(newsId);
    //     if (success) {
    //         emit("submit", form);
    //     }
    // } else {
    //     const success = await addNews();
    //     if (success) {
    //         emit("submit", form);
    //     }
    // }
};

// 初始化時重置表單（僅在新增模式）
onMounted(() => {
    console.log(form.show_date);
    if (props.mode === "add" && !props.initialData) {
        resetForm();
        coverPreview.value = null;
        slidePreviews.value = [];
    }
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
                        <h3 class="text-lg font-semibold">基本資訊</h3>
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
                        :error="errors.show_date">
                        <UPopover
                            :content="{
                                side: 'bottom',
                                align: 'start',
                            }">
                            <UButton
                                color="neutral"
                                variant="outline"
                                icon="i-lucide-calendar"
                                class="w-full">
                                {{
                                    dateValue
                                        ? df.format(
                                            dateValue.toDate(
                                                getLocalTimeZone()
                                            )
                                        )
                                        : "請選擇日期"
                                }}
                            </UButton>

                            <template #content>
                                <UCalendar
                                    v-model="dateValue"
                                    class="p-2"
                                    locale="zh-TW"
                                    :ui="{ cell: 'cursor-pointer'}"
                                    @update:model-value="handleDateUpdate" />
                            </template>
                        </UPopover>
                    </UFormField>

                    <UFormField
                        label="內文"
                        name="content"
                        :error="errors.content">
                        <TiptapEditor v-model="form.content" />
                        <UTextarea
                            v-model="form.content"
                            placeholder="請輸入內文"
                            :rows="10"
                            :disabled="formLoading"
                            autoresize
                            class="w-full"
                            @input="clearError('content')" />
                    </UFormField>

                    <UFormField
                        label="代表圖檔"
                        name="cover"
                        :error="errors.cover">
                        <div class="space-y-2">
                            <input
                                ref="coverInputRef"
                                type="file"
                                accept="image/*"
                                class="hidden"
                                @change="handleCoverUpload" />
                            <div
                                v-if="coverPreview || form.cover"
                                class="relative w-full max-w-lg">
                                <img
                                    :src="coverPreview || form.cover"
                                    alt="封面圖預覽"
                                    class="w-full max-w-lg object-cover rounded-lg border"
                                    style="max-height: 300px" />
                                <UButton
                                    icon="i-lucide-x"
                                    size="xs"
                                    color="error"
                                    variant="solid"
                                    class="absolute top-2 right-2"
                                    @click="removeCover" />
                            </div>
                            <UButton
                                :label="
                                    coverPreview || form.cover
                                        ? '更換圖片'
                                        : '上傳圖片'
                                "
                                icon="i-lucide-upload"
                                color="primary"
                                variant="outline"
                                block
                                :loading="isUploadingCover"
                                :disabled="isUploadingCover || formLoading"
                                @click="triggerCoverUpload" />
                        </div>
                    </UFormField>

                    <UFormField
                        label="輪播圖（JSON格式）"
                        name="slide"
                        :error="errors.slide">
                        <div class="space-y-2">
                            <input
                                ref="slideInputRef"
                                type="file"
                                accept="image/*"
                                class="hidden"
                                @change="handleSlideUpload" />
                            <div
                                v-if="form.slide.length > 0"
                                class="grid grid-cols-2 gap-2">
                                <div
                                    v-for="(image, index) in form.slide"
                                    :key="index"
                                    class="relative">
                                    <img
                                        :src="image"
                                        :alt="`輪播圖 ${index + 1}`"
                                        class="w-full object-cover rounded-lg border"
                                        style="max-height: 150px" />
                                    <UButton
                                        icon="i-lucide-x"
                                        size="xs"
                                        color="error"
                                        variant="solid"
                                        class="absolute top-2 right-2"
                                        @click="removeSlide(index)" />
                                </div>
                            </div>
                            <UButton
                                label="新增輪播圖"
                                icon="i-lucide-plus"
                                color="primary"
                                variant="outline"
                                block
                                :loading="isUploadingSlide"
                                :disabled="isUploadingSlide || formLoading"
                                @click="triggerSlideUpload" />
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
                        to="/news"
                        label="取消" />
                    <UButton
                        type="button"
                        :color="mode === 'add' ? 'primary' : 'success'"
                        :icon="mode === 'add' ? 'lucide:plus' : 'lucide:save'"
                        :loading="formLoading"
                        :disabled="formLoading"
                        @click="handleSubmit()"
                        :label="
                            mode === 'add' ? '新增最新消息' : '更新最新消息'
                        " />
                </div>
            </section>
        </UForm>
    </template>
</template>
