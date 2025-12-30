<script setup lang="ts">
import { PERMISSION_ACTIONS, PERMISSION_CATEGORIES } from "~/constants/permissions";

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
    addPermission,
    editPermission
} = usePermissionData();

const { data: moduleData, fetchData: fetchModules } = useModule();
const { data: structureData, fetchData: fetchStructures } = useStructure();

// 扁平化單元樹狀結構，用於下拉選單
const flattenStructures = (items: any[]): any[] => {
    const result: any[] = [];
    const traverse = (nodes: any[]) => {
        for (const node of nodes) {
            if (node && node.id) {
                // 只顯示有 URL 的單元（表示是實際的單元，而非分類）
                if (node.url) {
                    result.push({
                        id: node.id,
                        label: node.label,
                        url: node.url
                    });
                }
                // 遞迴處理子節點
                if (node.children && Array.isArray(node.children)) {
                    traverse(node.children);
                }
            }
        }
    };
    traverse(items);
    return result;
};

const structureList = computed(() =>
    flattenStructures(structureData.value || [])
);

// 選中的單元、分類、動作
const selectedStructureId = ref<number | null>(null);
const selectedCategory = ref<string>("");
const selectedAction = ref<string>("");

// 自動生成權限名稱
const generatedPermissionLabel = computed(() => {
    const structure = structureList.value.find(
        (s: any) => s.id === selectedStructureId.value
    );
    if (!structure || !structure.url) {
        return "";
    }
    const parts = [structure.label];
    if (selectedCategory.value) {
        parts.push(PERMISSION_CATEGORIES.find((c: any) => c.value === selectedCategory.value)?.label || "");
    }
    if (selectedAction.value) {
        parts.push(PERMISSION_ACTIONS.find((a: any) => a.value === selectedAction.value)?.label || "");
    }
    return parts.join("-");
});

// 根據選中的單元、分類、動作自動生成權限代碼
const generatedPermissionName = computed(() => {
    const structure = structureList.value.find(
        (s: any) => s.id === selectedStructureId.value
    );
    if (!structure || !structure.url) {
        return "";
    }

    const parts = [structure.url];
    
    // 如果有分類，加入分類
    if (selectedCategory.value) {
        parts.push(selectedCategory.value);
    }
    
    // 如果有動作，加入動作
    if (selectedAction.value) {
        parts.push(selectedAction.value);
    }
    
    return parts.join(".");
});

// 監聽生成的權限代碼，更新 form.name
watch(generatedPermissionName, (newName) => {
    if (newName) {
        form.name = newName;
    }
});

// 從權限代碼解析出單元、分類、動作
const parsePermissionName = (name: string) => {
    if (!name) {
        selectedStructureId.value = null;
        selectedCategory.value = "";
        selectedAction.value = "";
        return;
    }

    const parts = name.split(".");
    if (parts.length === 0) {
        return;
    }

    // 第一部分是單元的 URL
    const url = parts[0];
    const structure = structureList.value.find((s: any) => s.url === url);
    if (structure) {
        selectedStructureId.value = structure.id;
    }

    // 根據部分數量判斷格式
    if (parts.length === 2) {
        // 格式：單元.動作 (例如：about.view)
        selectedCategory.value = "";
        selectedAction.value = parts[1] || "";
    } else if (parts.length === 3) {
        // 格式：單元.分類.動作 (例如：about.section.create)
        selectedCategory.value = parts[1] || "";
        selectedAction.value = parts[2] || "";
    } else {
        selectedCategory.value = "";
        selectedAction.value = "";
    }
};

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

// 當 modal 開啟時重置表單或載入資料
watch(
    () => modalOpen.value,
    async (open) => {
        if (open) {
            await fetchModules();
            await fetchStructures();
            if (props.mode === "edit" && props.data) {
                // 編輯模式：載入現有資料
                loadFormData(props.data);
                // 解析權限代碼
                if (props.data.name) {
                    parsePermissionName(props.data.name);
                } else {
                    // 如果沒有權限代碼，從 category 和 action 載入
                    selectedCategory.value = props.data.category || "";
                    selectedAction.value = props.data.action || "";
                }
            } else {
                // 新增模式：重置表單
                resetForm();
                selectedStructureId.value = null;
                selectedCategory.value = "";
                selectedAction.value = "";
            }
        }
    }
);

// 監聽單元、分類、動作的變化，更新權限代碼和 form
watch([selectedStructureId, selectedCategory, selectedAction], () => {
    const newName = generatedPermissionName.value;
    const newLabel = generatedPermissionLabel.value;
    if (newName) {
        form.name = newName;
    }
    if (newLabel) {
        form.label = newLabel;
    }
    // 同步到 form.category 和 form.action
    form.category = selectedCategory.value || null;
    form.action = selectedAction.value || null;
});
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
                    label="單元"
                    name="structure_id"
                    required>
                    <USelect
                        v-model="selectedStructureId"
                        :items="[
                            ...structureList.map((s: any) => ({
                                label: s.label,
                                value: s.id
                            }))
                        ]"
                        :disabled="loading"
                        class="w-full"
                        placeholder="請選擇單元" />
                </UFormField>

                <UFormField label="分類" name="category">
                    <USelect
                        v-model="selectedCategory"
                        :items="PERMISSION_CATEGORIES"
                        :disabled="loading"
                        class="w-full"
                        placeholder="請選擇分類（選填）" />
                </UFormField>

                <UFormField
                    label="動作"
                    name="action"
                    required>
                    <USelect
                        v-model="selectedAction"
                        :items="PERMISSION_ACTIONS"
                        :disabled="loading"
                        class="w-full"
                        placeholder="請選擇動作" />
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
                <UFormField
                    label="權限代碼"
                    name="name"
                    :error="errors.name"
                    required>
                    <UInput
                        v-model="form.name"
                        placeholder="自動生成：about.view 或 about.section.create"
                        size="lg"
                        :disabled="loading || true"
                        class="w-full bg-gray-50"
                        readonly
                        @input="clearError('name')" />
                    <template #hint>
                        格式：單元.動作 或 單元.分類.動作（自動生成）
                    </template>
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
                <UFormField label="描述" name="description">
                    <UTextarea
                        v-model="form.description"
                        placeholder="請輸入權限描述（選填）"
                        :disabled="loading"
                        class="w-full"
                        :rows="3" />
                </UFormField>
                <FormStatusField
                    label="狀態"
                    name="status"
                    v-model="form.status"
                    :disabled="loading"
                    :field-ui="{ root: 'mb-4' }" />
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
