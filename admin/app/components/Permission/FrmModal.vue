<script setup lang="ts">
type ModalMode = "add" | "edit";

import { STATUS_LABEL_MAP } from "~/constants/system/status";

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
    addPermission,
    editPermission
} = usePermissionData();

const { data: moduleData, fetchData: fetchModules } = useModule();

// 標題
const modalTitle = computed(() => {
    switch (props.mode) {
        case "add":
            return "新增權限";
        case "edit":
            return "編輯權限";
        default:
            return "權限";
    }
});

const submitButtonText = computed(() => {
    switch (props.mode) {
        case "add":
            return "新增權限";
        case "edit":
            return "更新權限";
        default:
            return "送出";
    }
});

const handleSubmit = async () => {
    if (props.mode === "add") {
        await addPermission({
            closeModalRef: modalOpen,
            onSuccess: () => emit("added")
        });
    }
    if (props.mode === "edit") {
        await editPermission({
            id: props.data?.id,
            closeModalRef: modalOpen,
            onSuccess: () => emit("updated")
        });
    }
};

// 狀態的計算屬性，用於 UCheckbox 的 v-model（需要 boolean 類型）
const statusBoolean = computed({
    get: () => {
        if (typeof form.status === "boolean") {
            return form.status;
        }
        return form.status === 1;
    },
    set: (value: boolean) => {
        form.status = value ? 1 : 0;
    }
});

// 當 modal 開啟時重置表單或載入資料
watch(
    () => modalOpen.value,
    async (open) => {
        if (open) {
            await fetchModules();
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
                    label="權限代碼"
                    name="name"
                    :error="errors.name"
                    required>
                    <UInput
                        v-model="form.name"
                        placeholder="例如：module.view 或 module.tw.create"
                        size="lg"
                        :disabled="loading"
                        class="w-full"
                        @input="clearError('name')" />
                    <template #hint>
                        格式：模組.動作 或 模組.分類.動作
                    </template>
                </UFormField>
                <UFormField
                    label="權限名稱"
                    name="label"
                    :error="errors.label"
                    required>
                    <UInput
                        v-model="form.label"
                        placeholder="請輸入權限顯示名稱"
                        size="lg"
                        :disabled="loading"
                        class="w-full"
                        @input="clearError('label')" />
                </UFormField>
                <UFormField label="模組" name="module_id">
                    <USelect
                        v-model="form.module_id"
                        :items="[
                            { label: '無', value: null },
                            ...moduleData.map((m: any) => ({ label: m.label, value: m.id }))
                        ]"
                        :disabled="loading"
                        class="w-full" />
                </UFormField>
                <UFormField label="分類" name="category">
                    <UInput
                        v-model="form.category"
                        placeholder="例如：tw, sg, mm（選填）"
                        size="lg"
                        :disabled="loading"
                        class="w-full" />
                </UFormField>
                <UFormField label="動作" name="action">
                    <UInput
                        v-model="form.action"
                        placeholder="例如：view, create, edit, delete, manage（選填）"
                        size="lg"
                        :disabled="loading"
                        class="w-full" />
                </UFormField>
                <UFormField label="描述" name="description">
                    <UTextarea
                        v-model="form.description"
                        placeholder="請輸入權限描述（選填）"
                        :disabled="loading"
                        class="w-full"
                        :rows="3" />
                </UFormField>
                <UFormField label="狀態" name="status" :ui="{ root: 'mb-4' }">
                    <UCheckbox
                        v-model="statusBoolean"
                        :label="STATUS_LABEL_MAP[statusBoolean ? '1' : '0']" />
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
