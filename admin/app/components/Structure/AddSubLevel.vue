<script setup lang="ts">

const props = withDefaults(defineProps<{
    parentId: number | string;
    parentName?: string;
}>(), {
    parentId: 0,
    parentName: undefined
});

const emit = defineEmits<{
    (e: "added"): void;
}>();

const modalOpen = defineModel<boolean>("open", { default: false });

const {
    form,
    errors,
    loading,
    submitError,
    clearError,
    resetForm,
    addLevel
} = useStructure();

const addSubLevel = async (event?: Event) => {
    form.parent_id = props.parentId;
    const success = await addLevel(event, {
        parentId: props.parentId,
        closeModalRef: modalOpen,
        onSuccess: () => emit("added")
    });
    return success;
};

const handleSubmit = async (event: Event) => {
    await addSubLevel(event);
};

const resetForParent = () => {
    resetForm(props.parentId);
};

watch(
    () => modalOpen.value,
    (open) => {
        if (open) {
            resetForParent();
        }
    }
);

watch(
    () => props.parentId,
    (parentId) => {
        if (modalOpen.value) {
            form.parent_id = parentId;
        }
    }
);
</script>
<template>
    <UModal
        v-model:open="modalOpen"
        :title="`新增子層級${parentName ? ` (父層級: ${parentName})` : ''}`"
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
                <UCheckbox
                    v-model="form.is_show_frontend"
                    indicator="end"
                    label="是否顯示前台"
                    :disabled="loading"
                    :ui="{ root: 'mb-4 w-fit' }" />
                <UCheckbox
                    v-model="form.is_show_backend"
                    indicator="end"
                    label="是否顯示後台"
                    :disabled="loading"
                    :ui="{ root: 'mb-4 w-fit' }" />
                <UCheckbox
                    v-model="form.status"
                    indicator="end"
                    label="是否上線"
                    :disabled="loading"
                    :ui="{ root: 'mb-4 w-fit' }" />
                <UButton
                    type="submit"
                    block
                    size="lg"
                    :loading="loading"
                    :disabled="loading">
                    新增子層級
                </UButton>
                <div v-if="submitError" class="text-sm text-red-500">
                    {{ submitError }}
                </div>
            </UForm>
        </template>
    </UModal>
</template>

