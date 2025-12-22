<script setup lang="ts">
type ModalMode = "add-root" | "add-sub" | "edit";

const props = withDefaults(
    defineProps<{
        mode: ModalMode;
        parentId?: number | string;
        parentName?: string;
        level?: any; // 編輯時使用的層級資料
    }>(),
    {
        parentId: 0,
        parentName: undefined,
        level: undefined
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
    addLevel,
    updateLevel
} = useStructure();

const { data: modulesData, fetchData: fetchModules } = useModule();

// 下拉顯示用，不改動原始 modulesData 值，避免影響其他處（如 moduleName 顯示）
const selectModule = computed(() => {
    const modules = modulesData.value || [];
    const emptyOption = { label: "無", value: null };

    const mappedModules = modules.map((module: any) => ({
        ...module,
        label: `${module.label} (${module.name})`,
        value: module.id ?? module.value ?? module.name ?? ""
    }));

    return [emptyOption, ...mappedModules];
});

const checkboxUI = {
    root: "mb-4 w-fit",
    label: "cursor-pointer",
    base: "cursor-pointer"
};

// 標題
const modalTitle = computed(() => {
    switch (props.mode) {
        case "add-root":
            return "新增層級1";
        case "add-sub":
            return `新增子層級${
                props.parentName ? ` (父層級: ${props.parentName})` : ""
            }`;
        case "edit":
            return "編輯層級";
        default:
            return "層級";
    }
});

// 已有子層級時鎖定模組選擇
const isModuleSelectionLocked = computed(() => {
    return props.mode === "edit" && (props.level?.children?.length ?? 0) > 0;
});

// 按鈕文字
const submitButtonText = computed(() => {
    switch (props.mode) {
        case "add-root":
            return "新增層級";
        case "add-sub":
            return "新增子層級";
        case "edit":
            return "更新層級";
        default:
            return "提交";
    }
});

const changeModule = (event: Event) => {
    form.url = modulesData.value.find((module: any) => module.id === form.module_id)?.name;
};

// 處理提交
const handleSubmit = async (event: Event) => {
    if (props.mode === "edit") {
        await updateLevel(event, {
            levelId: props.level?.id,
            closeModalRef: modalOpen,
            onSuccess: () => emit("updated")
        });
    } else {
        // 新增模式
        const parentId = props.mode === "add-sub" ? props.parentId : null;
        form.parent_id = parentId ?? null;

        await addLevel(event, {
            parentId: parentId ?? null,
            closeModalRef: modalOpen,
            onSuccess: () => emit("added")
        });
    }
};

// 當 modal 開啟時重置表單或載入資料
watch(
    () => modalOpen.value,
    (open) => {
        if (open) {
            if (props.mode === "edit" && props.level) {
                // 編輯模式：載入現有資料
                loadFormData(props.level);
            } else {
                // 新增模式：重置表單
                const parentId =
                    props.mode === "add-sub" ? props.parentId : null;
                resetForm(parentId ?? null);
            }
        }
    }
);

// 當 parentId 改變時更新表單
watch(
    () => props.parentId,
    (parentId) => {
        if (modalOpen.value && props.mode === "add-sub") {
            form.parent_id = parentId ?? null;
        }
    }
);

onMounted(async () => {
    await fetchModules();
    // console.log("modulesData", modulesData.value);
    // console.log("selectModule", selectModule.value);
});
</script>

<template>
    <UModal
        v-model:open="modalOpen"
        :title="modalTitle"
        description=""
        :close="{
            color: 'primary',
            variant: 'outline',
            class: 'rounded-full'
        }">
        <template #body>
            <UForm :state="form" @submit="handleSubmit" class="space-y-4">
                <UFormField
                    label="層級名稱"
                    name="label"
                    :error="errors.label"
                    required>
                    <UInput
                        v-model="form.label"
                        placeholder="請輸入層級名稱"
                        size="lg"
                        :disabled="loading"
                        class="w-full"
                        @input="clearError('label')" />
                </UFormField>
                <UFormField
                    label="模組名稱"
                    name="module_id"
                    :error="errors.module_id">
                    <USelect
                        v-model="form.module_id"
                        :items="selectModule"
                        placeholder="請選擇模組"
                        size="lg"
                        defaultValue=""
                        :disabled="loading || isModuleSelectionLocked"
                        class="w-full"
                        @input="clearError('module_id')"
                        @change="changeModule" />
                    <p
                        v-if="isModuleSelectionLocked"
                        class="mt-1 text-xs text-gray-500">
                        已有子層級，無法變更模組
                    </p>
                </UFormField>
                <UFormField
                    label="自訂 URL（可選）"
                    name="url"
                    description="如果設定，將使用此 URL 而非模組代碼。例如：custom-contact">
                    <UInput
                        v-model="form.url"
                        placeholder="例如：custom-contact（不需要前綴斜線）"
                        size="lg"
                        :disabled="loading"
                        class="w-full" />
                </UFormField>
                <UCheckbox
                    v-model="form.status"
                    indicator="end"
                    label="是否上線"
                    :disabled="loading"
                    :ui="checkboxUI" />
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
