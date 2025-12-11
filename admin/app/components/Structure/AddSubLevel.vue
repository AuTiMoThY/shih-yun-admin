<script setup lang="ts">
import type { AddLevelForm, AddLevelFormErrors } from "~/types/level";

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

const toast = useToast();
const { public: runtimePublic } = useRuntimeConfig();
const apiBase = runtimePublic.apiBase;

const modalOpen = ref(false);
const loading = ref(false);
const submitError = ref("");

const form = reactive<AddLevelForm>({
    name: "",
    is_show_frontend: true,
    is_show_backend: true,
    status: true,
    parent_id: props.parentId
});

const errors = reactive<AddLevelFormErrors>({
    name: false,
    is_show_frontend: false,
    is_show_backend: false,
    status: false
});

const resetForm = () => {
    form.name = "";
    form.is_show_frontend = true;
    form.is_show_backend = true;
    form.status = true;
    form.parent_id = props.parentId;

    Object.keys(errors).forEach((key) => {
        // @ts-ignore
        errors[key] = false;
    });
    submitError.value = "";
};

const clearError = (field: keyof typeof errors) => {
    errors[field] = false;
};

const validateForm = (): boolean => {
    submitError.value = "";
    Object.keys(errors).forEach((key) => {
        // @ts-ignore
        errors[key] = false;
    });

    if (!form.name || form.name.trim() === "") {
        errors.name = "請輸入層級名稱";
        return false;
    }

    if (form.name.trim().length > 100) {
        errors.name = "層級名稱長度不能超過100個字元";
        return false;
    }

    return !Object.values(errors).some((v) => v);
};

const addSubLevel = async (event?: Event) => {
    if (event) event.preventDefault();
    if (!validateForm()) return;

    loading.value = true;
    try {
        const response = await $fetch<{ success: boolean; message: string; data?: { id: number } }>(
            "/structure/add",
            {
                baseURL: apiBase,
                method: "POST",
                body: {
                    ...form,
                    parent_id: props.parentId
                },
                headers: { "Content-Type": "application/json" },
                credentials: "include"
            }
        );

        if (response.success) {
            toast.add({
                title: response.message,
                color: "success"
            });
            emit("added");
            resetForm();
            modalOpen.value = false;
        } else {
            toast.add({
                title: response.message,
                color: "error"
            });
        }
    } catch (error: any) {
        const data = error?.data || error?.response?._data;
        const fieldErrors =
            data?.errors && typeof data.errors === "object" ? data.errors : null;

        if (fieldErrors) {
            Object.entries(fieldErrors).forEach(([key, val]) => {
                const msg = Array.isArray(val) ? val.join(", ") : String(val);
                // @ts-ignore
                errors[key] = msg;
            });
        }

        const msg =
            (typeof data?.message === "string" && data.message) ||
            (typeof data === "string" ? data : null) ||
            error?.message ||
            "新增子層級失敗，請稍後再試";

        submitError.value = msg;
        toast.add({ title: msg, color: "error" });
        console.error("addSubLevel error", error);
    } finally {
        loading.value = false;
    }
};

const openModal = () => {
    if (!props.parentId || props.parentId === 0) {
        console.error("無法打開子層級對話框：缺少父層級 ID");
        return;
    }
    resetForm();
    modalOpen.value = true;
};

defineExpose({
    openModal
});
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
            <UForm :state="form" @submit="addSubLevel" class="space-y-4">
                <UFormField
                    label="層級名稱"
                    name="name"
                    :error="errors.name"
                    required>
                    <UInput
                        v-model="form.name"
                        placeholder="請輸入層級名稱"
                        size="lg"
                        :disabled="loading"
                        class="w-full"
                        @input="clearError('name')" />
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

