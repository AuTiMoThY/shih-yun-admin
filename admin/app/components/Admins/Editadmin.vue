<script lang="ts" setup>
import type { AddAdminForm, AddAdminFormErrors } from "~/types/admin";
import { PERMISSIONS } from "~/constants/permissions";

const props = defineProps<{
    admin: any | null;
}>();

const emit = defineEmits<{
    (e: "updated"): void;
}>();

const toast = useToast();
const { public: runtimePublic } = useRuntimeConfig();
const apiBase = runtimePublic.apiBase;

const modalOpen = defineModel<boolean>("open", { default: false });
const password1Show = ref(false);
const password2Show = ref(false);
const loading = ref(false);
const submitError = ref("");
const editingAdminId = ref<number | null>(null);

const permission_names = ref(PERMISSIONS);

const form = reactive<AddAdminForm>({
    permission_name: "admin",
    status: true,
    username: "",
    password: "",
    password_confirmation: "",
    name: "",
    phone: "",
    address: ""
});

const errors = reactive<AddAdminFormErrors>({
    permission_name: false,
    status: false,
    username: false,
    password: false,
    password_confirmation: false,
    name: false,
    phone: false,
    address: false
});

const resetForm = () => {
    form.permission_name = "admin";
    form.status = true;
    form.username = "";
    form.password = "";
    form.password_confirmation = "";
    form.name = "";
    form.phone = "";
    form.address = "";

    Object.keys(errors).forEach((key) => {
        // @ts-ignore
        errors[key] = false;
    });
    submitError.value = "";
    editingAdminId.value = null;
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

    if (!form.permission_name) {
        errors.permission_name = "請選擇權限名稱";
    }

    if (!form.username || form.username.trim() === "") {
        errors.username = "請輸入帳號";
    } else if (form.username.trim().length < 3) {
        errors.username = "帳號長度至少需要3個字元";
    }

    // 編輯時密碼為可選，但如果填了就要驗證
    if (form.password || form.password_confirmation) {
        if (!form.password || form.password === "") {
            errors.password = "請輸入密碼";
        }

        if (!form.password_confirmation || form.password_confirmation === "") {
            errors.password_confirmation = "請再次輸入密碼";
        } else if (form.password !== form.password_confirmation) {
            errors.password_confirmation = "兩次輸入的密碼不一致";
        }
    }

    if (!form.name || form.name.trim() === "") {
        errors.name = "請輸入姓名";
    }

    if (form.phone && form.phone.trim() !== "") {
        const phoneRegex = /^[0-9-+()]+$/;
        if (!phoneRegex.test(form.phone.trim())) {
            errors.phone = "電話格式不正確";
        }
    }

    return !Object.values(errors).some((v) => v);
};

const updateAdmin = async (event?: Event) => {
    if (event) event.preventDefault();
    if (!validateForm()) return;
    if (!editingAdminId.value) return;

    loading.value = true;
    try {
        const body: any = {
            id: editingAdminId.value,
            permission_name: form.permission_name,
            status: form.status,
            username: form.username,
            name: form.name,
            phone: form.phone,
            address: form.address
        };

        // 只有當密碼有填寫時才加入
        if (form.password) {
            body.password = form.password;
            body.password_confirmation = form.password_confirmation;
        }

        const response = await $fetch<{ success: boolean; message: string }>(
            "/api/admins/update",
            {
                baseURL: apiBase,
                method: "POST",
                body,
                headers: { "Content-Type": "application/json" },
                credentials: "include"
            }
        );

        if (response.success) {
            toast.add({
                title: response.message,
                color: "success"
            });
            emit("updated");
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
            "更新管理員失敗，請稍後再試";

        submitError.value = msg;
        toast.add({ title: msg, color: "error" });
        console.error("updateAdmin error", error);
    } finally {
        loading.value = false;
    }
};

// 監聽 props.admin 變化，當有管理員資料時填入表單
watch(
    () => props.admin,
    (admin) => {
        if (admin) {
            editingAdminId.value = admin.id;
            form.permission_name = admin.permission_name || "admin";
            form.status = admin.status === 1 || admin.status === "1";
            form.username = admin.username || "";
            form.password = "";
            form.password_confirmation = "";
            form.name = admin.name || "";
            form.phone = admin.phone || "";
            form.address = admin.address || "";
        } else {
            resetForm();
        }
    },
    { immediate: true }
);
</script>

<template>
    <UModal
        v-model:open="modalOpen"
        title="編輯管理員"
        :close="{
            color: 'primary',
            variant: 'outline',
            class: 'rounded-full'
        }">
        <template #body>
            <UForm :state="form" @submit="updateAdmin" class="space-y-4">
                <div class="w-full grid grid-cols-2 gap-4">
                    <UFormField
                        label="權限名稱"
                        name="permission_name"
                        :error="errors.permission_name"
                        required>
                        <USelect
                            v-model="form.permission_name"
                            :items="permission_names"
                            placeholder="請選擇權限名稱" />
                    </UFormField>
                    <UFormField
                        label="狀態"
                        name="status"
                        :error="errors.status">
                        <UCheckbox v-model="form.status" />
                    </UFormField>
                </div>
                <UFormField
                    label="帳號"
                    name="username"
                    :error="errors.username"
                    required>
                    <UInput
                        v-model="form.username"
                        placeholder="請輸入帳號"
                        size="lg"
                        :disabled="loading"
                        class="w-full"
                        @input="clearError('username')" />
                </UFormField>

                <UFormField
                    label="密碼（留空則不修改）"
                    name="password"
                    :error="errors.password">
                    <UInput
                        v-model="form.password"
                        :type="password1Show ? 'text' : 'password'"
                        placeholder="請輸入新密碼（選填）"
                        size="lg"
                        :disabled="loading"
                        class="w-full"
                        @input="clearError('password')">
                        <template #trailing>
                            <UButton
                                color="neutral"
                                variant="link"
                                size="sm"
                                :icon="
                                    password1Show
                                        ? 'i-lucide-eye-off'
                                        : 'i-lucide-eye'
                                "
                                :aria-label="
                                    password1Show
                                        ? 'Hide password'
                                        : 'Show password'
                                "
                                :aria-pressed="password1Show"
                                aria-controls="password"
                                @click="password1Show = !password1Show" />
                        </template>
                    </UInput>
                </UFormField>

                <UFormField
                    label="再次輸入密碼"
                    name="password_confirmation"
                    :error="errors.password_confirmation">
                    <UInput
                        v-model="form.password_confirmation"
                        :type="password2Show ? 'text' : 'password'"
                        placeholder="請再次輸入新密碼（選填）"
                        size="lg"
                        :disabled="loading"
                        class="w-full"
                        @input="clearError('password_confirmation')">
                        <template #trailing>
                            <UButton
                                color="neutral"
                                variant="link"
                                size="sm"
                                :icon="
                                    password2Show
                                        ? 'i-lucide-eye-off'
                                        : 'i-lucide-eye'
                                "
                                :aria-label="
                                    password2Show
                                        ? 'Hide password'
                                        : 'Show password'
                                "
                                :aria-pressed="password2Show"
                                aria-controls="password"
                                @click="password2Show = !password2Show" />
                        </template>
                    </UInput>
                </UFormField>

                <UFormField
                    label="姓名"
                    name="name"
                    required
                    :error="errors.name">
                    <UInput
                        v-model="form.name"
                        type="text"
                        placeholder="請輸入姓名"
                        size="lg"
                        :disabled="loading"
                        class="w-full"
                        @input="clearError('name')" />
                </UFormField>

                <UFormField label="電話" name="phone" :error="errors.phone">
                    <UInput
                        v-model="form.phone"
                        type="text"
                        placeholder="請輸入電話"
                        size="lg"
                        :disabled="loading"
                        class="w-full"
                        @input="clearError('phone')" />
                </UFormField>

                <UFormField label="地址" name="address" :error="errors.address">
                    <UInput
                        v-model="form.address"
                        type="text"
                        placeholder="請輸入地址"
                        size="lg"
                        :disabled="loading"
                        class="w-full"
                        @input="clearError('address')" />
                </UFormField>
                <UButton
                    type="submit"
                    block
                    size="lg"
                    :loading="loading"
                    :disabled="loading">
                    更新管理員
                </UButton>
                <div v-if="submitError" class="text-sm text-red-500">
                    {{ submitError }}
                </div>
            </UForm>
        </template>
    </UModal>
</template>

