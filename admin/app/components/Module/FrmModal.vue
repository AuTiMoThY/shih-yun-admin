<script setup lang="ts">
type ModalMode = "add" | "edit";
const props = withDefaults(
    defineProps<{
        mode: ModalMode;
        data?: any;
    }>(),
    {
        data: undefined
    }
);

const emit = defineEmits<{
    (e: "added"): void;
    (e: "updated"): void;
}>();

const modalOpen = defineModel<boolean>("open", { default: false });

const {
    form,
    errors,
    loading,
    submitError,
    clearError,
    resetForm,
    loadFormData,
    addModule,
    editModule
} = useModule();

// 標題
const modalTitle = computed(() => {
    switch (props.mode) {
        case "add":
            return "新增模組";
        case "edit":
            return "編輯模組";
        default:
            return "模組";
    }
});

const submitButtonText = computed(() => {
    switch (props.mode) {
        case "add":
            return "新增模組";
        case "edit":
            return "更新模組";
        default:
            return "送出";
    }
});

const handleSubmit = async () => {
    if (props.mode === "add") {
        await addModule({
            closeModalRef: modalOpen,
            onSuccess: () => emit("added")
        });
    }
    if (props.mode === "edit") {
        await editModule({
            id: props.data?.id,
            closeModalRef: modalOpen,
            onSuccess: () => emit("updated")
        });
    }
};

// 當 modal 開啟時重置表單或載入資料
watch(
    () => modalOpen.value,
    (open) => {
        if (open) {
            if (props.mode === "edit" && props.data) {
                // 編輯模式：載入現有資料
                loadFormData(props.data);
            } else {
                // 新增模式：重置表單
                resetForm();
            }
        }
    }
);
</script>
<template>
    <UModal
        v-model:open="modalOpen"
        :title="modalTitle"
        :close="{
            color: 'primary',
            variant: 'outline',
            class: 'rounded-full'
        }">
        <template #body>
            <UForm :state="form" @submit="handleSubmit" class="space-y-4">
                <UFormField
                    label="模組名稱"
                    name="label"
                    :error="errors.label"
                    required>
                    <UInput
                        v-model="form.label"
                        placeholder="請輸入模組名稱"
                        size="lg"
                        :disabled="loading"
                        class="w-full"
                        @input="clearError('label')" />
                </UFormField>
                <UFormField
                    label="模組代碼"
                    name="name"
                    :error="errors.name"
                    required>
                    <UInput
                        v-model="form.name"
                        placeholder="請輸入模組代碼"
                        size="lg"
                        :disabled="loading"
                        class="w-full"
                        @input="clearError('name')" />
                </UFormField>
                <UButton
                    type="submit"
                    block
                    size="lg"
                    :loading="loading"
                    :disabled="loading">
                    {{ submitButtonText }}
                </UButton>
                <div v-if="submitError" class="text-sm text-red-500">
                    {{ submitError }}
                </div>
            </UForm>
        </template>
    </UModal>
</template>
