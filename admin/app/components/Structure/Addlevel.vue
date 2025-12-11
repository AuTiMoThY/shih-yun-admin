<script setup lang="ts">

const emit = defineEmits<{
    (e: "added"): void;
}>();

const { form, errors, loading, submitError, clearError, validateForm, resetForm, addLevel, modalOpen } = useStructure();

const handleSubmit = async (event: Event) => {
    await addLevel(event, { onSuccess: () => emit("added") });
};



</script>
<template>
    <UModal
        v-model:open="modalOpen"
        title="新增層級1"
        description=""
        :close="{
            color: 'primary',
            variant: 'outline',
            class: 'rounded-full'
        }">
        <UButton
            color="primary"
            variant="outline"
            icon="lucide:plus"
            label="新增層級1"
            @click="modalOpen = true" />
        <template #body>
            <UForm
                :state="form"
                @submit="handleSubmit"
                class="space-y-4">
                <UFormField
                    label="層級名稱"
                    name="name"
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
                    新增層級
                </UButton>
                <div v-if="submitError" class="text-sm text-red-500">
                    {{ submitError }}
                </div>
            </UForm>
        </template>
    </UModal>
</template>
