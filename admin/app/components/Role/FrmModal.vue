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
    addRole,
    editRole
} = useRole();

const { data: permissionData, fetchData: fetchPermissions } = usePermissionData();
const { data: moduleData, fetchData: fetchModules } = useModule();

// 標題
const modalTitle = computed(() => {
    switch (props.mode) {
        case "add":
            return "新增角色";
        case "edit":
            return "編輯角色";
        default:
            return "角色";
    }
});

const submitButtonText = computed(() => {
    switch (props.mode) {
        case "add":
            return "新增角色";
        case "edit":
            return "更新角色";
        default:
            return "送出";
    }
});

const handleSubmit = async () => {
    if (props.mode === "add") {
        await addRole({
            closeModalRef: modalOpen,
            onSuccess: () => emit("added")
        });
    }
    if (props.mode === "edit") {
        await editRole({
            id: props.data?.id,
            closeModalRef: modalOpen,
            onSuccess: () => emit("updated")
        });
    }
};

// 當 modal 開啟時重置表單或載入資料
watch(
    () => modalOpen.value,
    async (open) => {
        if (open) {
            await fetchPermissions();
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
                    label="角色代碼"
                    name="name"
                    :error="errors.name"
                    required>
                    <UInput
                        v-model="form.name"
                        placeholder="請輸入角色代碼（英數字、底線、連字號）"
                        size="lg"
                        :disabled="loading"
                        class="w-full"
                        @input="clearError('name')" />
                </UFormField>
                <UFormField
                    label="角色名稱"
                    name="label"
                    :error="errors.label"
                    required>
                    <UInput
                        v-model="form.label"
                        placeholder="請輸入角色名稱"
                        size="lg"
                        :disabled="loading"
                        class="w-full"
                        @input="clearError('label')" />
                </UFormField>
                <UFormField
                    label="描述"
                    name="description">
                    <UTextarea
                        v-model="form.description"
                        placeholder="請輸入角色描述（選填）"
                        :disabled="loading"
                        class="w-full"
                        :rows="3" />
                </UFormField>
                <FormStatusField
                        v-model="form.status"
                        label="狀態"
                        name="status"
                        :disabled="loading" />
                <UFormField
                    label="權限"
                    name="permission_ids">
                    <div class="space-y-2 max-h-60 overflow-y-auto border rounded-lg p-4">
                        <div v-if="permissionData.length === 0" class="text-sm text-gray-500">
                            暫無權限資料
                        </div>
                        <div v-else class="space-y-2">
                            <label
                                v-for="permission in permissionData"
                                :key="permission.id"
                                class="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 p-2 rounded">
                                <input
                                    type="checkbox"
                                    :value="permission.id"
                                    v-model="form.permission_ids"
                                    :disabled="loading"
                                    class="rounded" />
                                <span class="text-sm">{{ permission.label }} ({{ permission.name }})</span>
                            </label>
                        </div>
                    </div>
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
