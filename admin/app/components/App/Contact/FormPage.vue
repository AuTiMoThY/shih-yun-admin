<script setup lang="ts">
import type { ContactFormErrors } from "~/types";
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
    clearError,
    validateForm,
    loading: formLoading,
    updateReply,
    updateStatus
} = useContact();

// HTML 原始碼預覽開關
const showHtmlCode = ref(false);

// 載入初始資料
const loadInitialData = (data: any) => {
    if (data) {
        form.reply = data.reply || "";
        const statusValue = Number(data.status) || 0;
        form.status =
            statusValue === 0 || statusValue === 1 || statusValue === 2
                ? statusValue
                : 0;
    } else {
        form.reply = "";
    }
};

// 狀態標籤映射
const statusLabelMap: Record<number, string> = {
    0: "未處理",
    1: "已處理"
};

// 處理狀態變更
const handleStatusChange = async (value: boolean) => {
    if (!props.initialData?.id) return;

    const newStatus = value ? 1 : 0;
    const oldStatus = form.status;

    // 樂觀更新
    form.status = newStatus;

    // 調用 API 更新狀態
    const result = await updateStatus(props.initialData.id, newStatus);

    // 如果更新失敗，回滾狀態
    if (!result.success) {
        form.status = oldStatus;
    }
};

// 表單提交
const handleSubmit = async (event?: Event) => {
    if (event) event.preventDefault();

    if (!props.initialData?.id) {
        errors.reply = "缺少聯絡表單 ID";
        return;
    }

    formLoading.value = true;

    try {
        const result = await updateReply(props.initialData.id, form.reply || "");

        if (result.success) {
            emit("submit", form);
        }
    } catch (error: any) {
        errors.reply = error.message || "更新失敗";
    } finally {
        formLoading.value = false;
    }
};

// 格式化日期時間
const formatDateTime = (dateString: string | null | undefined) => {
    if (!dateString) return "";
    const date = new Date(dateString);
    return date.toLocaleString("zh-TW", {
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit"
    });
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

// 初始化時重置表單（僅在新增模式）
onMounted(() => {
    if (props.mode === "add" && !props.initialData) {
        form.reply = "";
    }
});

// 暴露方法給父組件
defineExpose({
    loading: formLoading,
    submit: handleSubmit
});
</script>

<template>
    <PageLoading v-if="formLoading" />
    <template v-else>
        <UForm :state="form" @submit="handleSubmit" class="">
            <section class="frm-bd grid grid-cols-1 gap-4">
                <!-- 表單訊息（只讀） -->
                <UCard :ui="{ body: 'space-y-4' }">
                    <template #header>
                        <h3 class="text-lg font-semibold">表單訊息</h3>
                    </template>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <UFormField label="姓名" name="name">
                            <UInput
                                :model-value="initialData?.name || ''"
                                disabled
                                size="lg"
                                class="w-full cursor-not-allowed! opacity-60! bg-gray-50! dark:bg-gray-800!" />
                        </UFormField>

                        <UFormField label="電話" name="phone">
                            <UInput
                                :model-value="initialData?.phone || ''"
                                disabled
                                size="lg"
                                class="w-full cursor-not-allowed! opacity-60! bg-gray-50! dark:bg-gray-800!" />
                        </UFormField>

                        <UFormField label="信箱" name="email">
                            <UInput
                                :model-value="initialData?.email || ''"
                                disabled
                                size="lg"
                                class="w-full cursor-not-allowed! opacity-60! bg-gray-50! dark:bg-gray-800!" />
                        </UFormField>
                    </div>

                    <UFormField label="留言" name="message">
                        <UTextarea
                            :model-value="initialData?.message || ''"
                            disabled
                            autoresize
                            size="lg"
                            :rows="5"
                            class="w-full !cursor-not-allowed !opacity-60 !bg-gray-50 dark:!bg-gray-800" />
                    </UFormField>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <UFormField label="提交時間" name="created_at">
                            <UInput
                                :model-value="
                                    formatDateTime(initialData?.created_at)
                                "
                                disabled
                                size="lg"
                                class="w-full cursor-not-allowed! opacity-60! bg-gray-50! dark:bg-gray-800!" />
                        </UFormField>
                    </div>
                </UCard>

                <!-- 回信 -->
                <UCard :ui="{ body: 'space-y-4' }">
                    <template #header>
                        <h3 class="text-lg font-semibold">回信</h3>
                    </template>
                    <UFormField label="處理狀態" name="status">
                        <UCheckbox
                            :model-value="form.status === 1"
                            :label="statusLabelMap[form.status] || '未處理'"
                            :disabled="formLoading"
                            @update:model-value="
                                (value) => {
                                    if (typeof value === 'boolean') {
                                        handleStatusChange(value);
                                    }
                                }
                            " />
                    </UFormField>

                    <UFormField
                        label="回信內容"
                        name="reply"
                        :error="errors.reply">
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
                                <TiptapEditor
                                    :model-value="form.reply || ''"
                                    @update:model-value="(value: string) => {
                                        form.reply = value;
                                    }" />
                            </div>
                            <div v-else class="relative">
                                <CodeEditor
                                    :model-value="form.reply || ''"
                                    :disabled="formLoading"
                                    @update:model-value="(value: string) => {
                                        form.reply = value;
                                        clearError('reply');
                                    }" />
                            </div>
                        </div>
                    </UFormField>
                </UCard>
            </section>
            <section class="frm-ft">
                <div v-if="errors.reply" class="mt-4 text-sm text-red-500">
                    {{ errors.reply }}
                </div>

                <div class="mt-6 flex gap-4 justify-end">
                    <UButton
                        type="button"
                        color="neutral"
                        variant="ghost"
                        :disabled="formLoading"
                        to="/contact"
                        label="取消" />
                    <UButton
                        type="button"
                        color="success"
                        icon="lucide:save"
                        :loading="formLoading"
                        :disabled="formLoading"
                        @click="handleSubmit()"
                        label="儲存回信" />
                </div>
            </section>
        </UForm>
    </template>
</template>
