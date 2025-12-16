<script lang="ts" setup>
import type { AdminForm, AdminFormErrors } from "~/types/admin";
import { STATUS, STATUS_LABEL_MAP } from "~/constants/system/status";

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
    (e: "submit", data: AdminForm): void;
}>();

const password1Show = ref(false);
const password2Show = ref(false);
const submitButtonRef = ref<HTMLButtonElement | null>(null);

// RBAC 相關
const { data: roleData, fetchData: fetchRoles } = useRole();
const { data: permissionData, fetchData: fetchPermissions } =
    usePermissionData();

const {
    addAdmin,
    editAdmin,
    loading,
    submitError,
    errors,
    form,
    clearError,
    loadDataToForm,
    validateFormForEdit
} = useUsers();

// 載入初始資料
const loadInitialData = (data: any) => {
    if (data) {
        loadDataToForm(data);
    }
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

const handleSubmit = (event?: Event) => {
    if (event) event.preventDefault();

    if (props.mode === "edit") {
        // 編輯模式：使用 editAdmin，驗證時密碼可選
        if (!validateFormForEdit()) {
            return;
        }
        // 需要從 initialData 或路由獲取 adminId
        const adminId = props.initialData?.id;
        if (!adminId) {
            submitError.value = "缺少管理員 ID";
            return;
        }
        editAdmin(form, adminId);
    } else {
        // 新增模式：使用 addAdmin
        addAdmin(form);
    }
};

// 載入角色和權限資料
onMounted(async () => {
    await fetchRoles();
    await fetchPermissions();
});

// 狀態的計算屬性，用於 UCheckbox 的 v-model（需要 boolean 類型）
const statusBoolean = computed({
    get: () => {
        if (typeof form.status === "boolean") {
            return form.status;
        }
        return form.status === "1";
    },
    set: (value: boolean) => {
        form.status = value;
    }
});

// 暴露方法給父組件
defineExpose({
    loading,
    submit: handleSubmit
});
</script>

<template>
    <UForm :state="form" @submit="handleSubmit" class="space-y-4">
        <UCard>
            <template #header>
                <h3 class="text-lg font-semibold">基本資訊</h3>
            </template>

            <UFormField
                label="狀態"
                name="status"
                :error="errors.status"
                :ui="{ root: 'mb-4' }">
                <UCheckbox
                    v-model="statusBoolean"
                    :label="STATUS_LABEL_MAP[statusBoolean ? '1' : '0']" />
            </UFormField>

            <UFormField
                label="帳號"
                name="username"
                :error="errors.username"
                required
                :ui="{ root: 'mb-4' }">
                <UInput
                    v-model="form.username"
                    placeholder="請輸入帳號"
                    size="lg"
                    :disabled="loading"
                    class="w-full"
                    @input="clearError('username')" />
            </UFormField>

            <UFormField
                :label="mode === 'add' ? '密碼' : '密碼（留空則不修改）'"
                name="password"
                :error="errors.password"
                :required="mode === 'add'"
                :ui="{ root: 'mb-4' }">
                <UInput
                    v-model="form.password"
                    :type="password1Show ? 'text' : 'password'"
                    :placeholder="
                        mode === 'add' ? '請輸入密碼' : '請輸入新密碼（選填）'
                    "
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
                :label="
                    mode === 'add' ? '再次輸入密碼' : '再次輸入密碼（選填）'
                "
                name="password_confirmation"
                :error="errors.password_confirmation"
                :required="mode === 'add'"
                :ui="{ root: 'mb-4' }">
                <UInput
                    v-model="form.password_confirmation"
                    :type="password2Show ? 'text' : 'password'"
                    :placeholder="
                        mode === 'add'
                            ? '請再次輸入密碼'
                            : '請再次輸入新密碼（選填）'
                    "
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
                :error="errors.name"
                :ui="{ root: 'mb-4' }">
                <UInput
                    v-model="form.name"
                    type="text"
                    placeholder="請輸入姓名"
                    size="lg"
                    :disabled="loading"
                    class="w-full"
                    @input="clearError('name')" />
            </UFormField>

            <UFormField
                label="電話"
                name="phone"
                :error="errors.phone"
                :ui="{ root: 'mb-4' }">
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
        </UCard>

        <UCard>
            <template #header>
                <h3 class="text-lg font-semibold">角色&權限</h3>
            </template>

            <UFormField
                label="角色"
                name="role_ids"
                :error="errors.role_ids"
                :ui="{ root: 'mb-4' }">
                <div
                    class="space-y-2 max-h-60 overflow-y-auto border rounded-lg p-4">
                    <div
                        v-if="roleData.length === 0"
                        class="text-sm text-gray-500">
                        暫無角色資料
                    </div>
                    <div v-else class="space-y-2">
                        <label
                            v-for="role in roleData"
                            :key="role.id"
                            class="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 p-2 rounded">
                            <input
                                type="checkbox"
                                :value="role.id"
                                v-model="form.role_ids"
                                :disabled="loading"
                                class="rounded" />
                            <span class="text-sm"
                                >{{ role.label }} ({{ role.name }})</span
                            >
                        </label>
                    </div>
                </div>
            </UFormField>

            <UFormField
                label="直接權限（可選，會覆蓋角色權限）"
                name="permission_ids"
                :error="errors.permission_ids">
                <div
                    class="space-y-2 max-h-60 overflow-y-auto border rounded-lg p-4">
                    <div
                        v-if="permissionData.length === 0"
                        class="text-sm text-gray-500">
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
                            <span class="text-sm"
                                >{{ permission.label }} ({{
                                    permission.name
                                }})</span
                            >
                        </label>
                    </div>
                </div>
            </UFormField>
        </UCard>

        <div v-if="submitError" class="mt-4 text-sm text-red-500">
            {{ submitError }}
        </div>

        <div class="mt-6 flex gap-4 justify-end">
            <UButton
                type="button"
                color="neutral"
                variant="ghost"
                :disabled="loading"
                to="/system/admins"
                label="取消" />
            <UButton
                type="button"
                color="primary"
                variant="outline"
                :icon="mode === 'add' ? 'lucide:plus' : 'lucide:save'"
                :loading="loading"
                :disabled="loading"
                @click="handleSubmit()"
                :label="mode === 'add' ? '新增管理員' : '更新管理員'" />
        </div>
    </UForm>
</template>
