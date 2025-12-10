<script setup lang="ts">
definePageMeta({
    middleware: "auth"
});
import type { TableColumn } from "@nuxt/ui";
import { h, resolveComponent } from "vue";
import type { AddAdminForm, AddAdminFormErrors } from "~/types/admin";
import { PERMISSIONS } from "~/constants/permissions";

const toast = useToast();

const { getUsers } = useUsers();
const users = ref<any[]>([]);
const loading = ref(false);
const table = ref();

import { PERMISSION_LABEL_MAP } from "~/constants/permissions";
import { ADMIN_STATUS_LABEL_MAP } from "~/constants/admin_status";

const { public: runtimePublic } = useRuntimeConfig();
const apiBase = runtimePublic.apiBase;

// 編輯 modal 相關
const editModalOpen = ref(false);
const editPassword1Show = ref(false);
const editPassword2Show = ref(false);
const editLoading = ref(false);
const editSubmitError = ref("");
const editingAdminId = ref<number | null>(null);

const permission_names = ref(PERMISSIONS);

const editForm = reactive<AddAdminForm>({
    permission_name: "admin",
    status: true,
    username: "",
    password: "",
    password_confirmation: "",
    name: "",
    phone: "",
    address: ""
});

const editErrors = reactive<AddAdminFormErrors>({
    permission_name: false,
    status: false,
    username: false,
    password: false,
    password_confirmation: false,
    name: false,
    phone: false,
    address: false
});

// 參考 Addadmin.vue 的權限設定，轉換顯示文字
const permissionLabelMap = PERMISSION_LABEL_MAP;
const adminStatusLabelMap = ADMIN_STATUS_LABEL_MAP;
const statusIconMap: Record<string, string> = {
    "1": "i-lucide-badge-check",
    "0": "i-lucide-ban"
};
const columns: TableColumn<any>[] = [
    { accessorKey: "username", header: "帳號" },
    { accessorKey: "name", header: "姓名" },
    {
        accessorKey: "permission_name",
        header: "權限名稱",
        cell: ({ row }) =>
            permissionLabelMap[row.original.permission_name] ??
            row.original.permission_name
    },
    {
        accessorKey: "status",
        header: "狀態",
        cell: ({ row }) => {
            const status = String(row.original.status);
            const label = adminStatusLabelMap[status] ?? status;
            const icon =
                statusIconMap[status] ?? "i-lucide-help-circle";
            const UIcon = resolveComponent("UIcon");

            return h(
                "div",
                { class: "flex items-center gap-2" },
                [
                    h(UIcon, {
                        name: icon,
                        class:
                            status === "1"
                                ? "text-emerald-500"
                                : "text-rose-500"
                    }),
                    h("span", label)
                ]
            );
        }
    },
    {
        header: "操作",
        cell: ({ row }) => {
            const UButton = resolveComponent("UButton");
            return h("div", { class: "flex items-center gap-2" }, [
                h(UButton, {
                    icon: "i-lucide-edit",
                    label: "編輯",
                    color: "primary",
                    size: "xs",
                    onClick: () => editAdmin(row.original)
                }),
                h(UButton, {
                    icon: "i-lucide-trash",
                    label: "刪除",
                    color: "error",
                    variant: "ghost",
                    size: "xs",
                    onClick: () => deleteAdmin(row.original)
                })
            ]);
        }
    }
];

const resetEditForm = () => {
    editForm.permission_name = "admin";
    editForm.status = true;
    editForm.username = "";
    editForm.password = "";
    editForm.password_confirmation = "";
    editForm.name = "";
    editForm.phone = "";
    editForm.address = "";

    Object.keys(editErrors).forEach((key) => {
        // @ts-ignore
        editErrors[key] = false;
    });
    editSubmitError.value = "";
    editingAdminId.value = null;
};

const clearEditError = (field: keyof typeof editErrors) => {
    editErrors[field] = false;
};

const validateEditForm = (): boolean => {
    editSubmitError.value = "";
    Object.keys(editErrors).forEach((key) => {
        // @ts-ignore
        editErrors[key] = false;
    });

    if (!editForm.permission_name) {
        editErrors.permission_name = "請選擇權限名稱";
    }

    if (!editForm.username || editForm.username.trim() === "") {
        editErrors.username = "請輸入帳號";
    } else if (editForm.username.trim().length < 3) {
        editErrors.username = "帳號長度至少需要3個字元";
    }

    // 編輯時密碼為可選，但如果填了就要驗證
    if (editForm.password || editForm.password_confirmation) {
        if (!editForm.password || editForm.password === "") {
            editErrors.password = "請輸入密碼";
        }

        if (!editForm.password_confirmation || editForm.password_confirmation === "") {
            editErrors.password_confirmation = "請再次輸入密碼";
        } else if (editForm.password !== editForm.password_confirmation) {
            editErrors.password_confirmation = "兩次輸入的密碼不一致";
        }
    }

    if (!editForm.name || editForm.name.trim() === "") {
        editErrors.name = "請輸入姓名";
    }

    if (editForm.phone && editForm.phone.trim() !== "") {
        const phoneRegex = /^[0-9-+()]+$/;
        if (!phoneRegex.test(editForm.phone.trim())) {
            editErrors.phone = "電話格式不正確";
        }
    }

    return !Object.values(editErrors).some((v) => v);
};

const editAdmin = (admin: any) => {
    editingAdminId.value = admin.id;
    editForm.permission_name = admin.permission_name || "admin";
    editForm.status = admin.status === 1 || admin.status === "1";
    editForm.username = admin.username || "";
    editForm.password = "";
    editForm.password_confirmation = "";
    editForm.name = admin.name || "";
    editForm.phone = admin.phone || "";
    editForm.address = admin.address || "";
    editModalOpen.value = true;
};

const updateAdmin = async (event?: Event) => {
    if (event) event.preventDefault();
    if (!validateEditForm()) return;
    if (!editingAdminId.value) return;

    editLoading.value = true;
    try {
        const body: any = {
            id: editingAdminId.value,
            permission_name: editForm.permission_name,
            status: editForm.status,
            username: editForm.username,
            name: editForm.name,
            phone: editForm.phone,
            address: editForm.address
        };

        // 只有當密碼有填寫時才加入
        if (editForm.password) {
            body.password = editForm.password;
            body.password_confirmation = editForm.password_confirmation;
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
            fetchUsers();
            resetEditForm();
            editModalOpen.value = false;
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
                editErrors[key] = msg;
            });
        }

        const msg =
            (typeof data?.message === "string" && data.message) ||
            (typeof data === "string" ? data : null) ||
            error?.message ||
            "更新管理員失敗，請稍後再試";

        editSubmitError.value = msg;
        toast.add({ title: msg, color: "error" });
        console.error("updateAdmin error", error);
    } finally {
        editLoading.value = false;
    }
};

const deleteAdmin = async (admin: any) => {
    console.log(admin);
    try {
        const response = await $fetch<{ success: boolean; message: string }>(
            "/api/admins/delete",
            {
                baseURL: apiBase,
                method: "POST",
                body: { id: admin.id },
                headers: { "Content-Type": "application/json" },
                credentials: "include"
            }
        );
        console.log(response);
        if (response.success) {
            toast.add({ title: response.message, color: "success" });
            fetchUsers();
        } else {
            toast.add({ title: response.message, color: "error" });
        }
    } catch (error: any) {
        console.error("deleteAdmin error", error);
        toast.add({ title: error.message || "刪除管理員失敗，請稍後再試", color: "error" });
    }
};

const fetchUsers = async () => {
    loading.value = true;
    const res = await getUsers();
    if (res?.success) {
        users.value = res.data;
    } else {
        console.error(res.message);
        toast.add({
            title: res.message,
            color: "error"
        });
    }
    loading.value = false;
};

onMounted(fetchUsers);
</script>

<template>
    <UDashboardPanel id="admins">
        <template #header>
            <UDashboardNavbar title="管理員設定" :ui="{ right: 'gap-3' }">
                <template #leading>
                    <UDashboardSidebarCollapse />
                </template>
            </UDashboardNavbar>

            <UDashboardToolbar>
                <template #right>
                    <AdminsAddadmin @added="fetchUsers" />
                </template>
            </UDashboardToolbar>
        </template>
        <template #body>
            <UTable
                ref="table"
                class="shrink-0"
                :data="users"
                :columns="columns"
                :loading="loading"
                :ui="{
                    base: 'table-fixed border-separate border-spacing-0',
                    thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
                    tbody: '[&>tr]:last:[&>td]:border-b-0',
                    th: 'py-2 first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r',
                    td: 'border-b border-default',
                    separator: 'h-0'
                }" />
        </template>
    </UDashboardPanel>

    <!-- 編輯管理員 Modal -->
    <UModal
        v-model:open="editModalOpen"
        title="編輯管理員"
        :close="{
            color: 'primary',
            variant: 'outline',
            class: 'rounded-full'
        }">
        <template #body>
            <UForm :state="editForm" @submit="updateAdmin" class="space-y-4">
                <div class="w-full grid grid-cols-2 gap-4">
                    <UFormField
                        label="權限名稱"
                        name="permission_name"
                        :error="editErrors.permission_name"
                        required>
                        <USelect
                            v-model="editForm.permission_name"
                            :items="permission_names"
                            placeholder="請選擇權限名稱" />
                    </UFormField>
                    <UFormField
                        label="狀態"
                        name="status"
                        :error="editErrors.status">
                        <UCheckbox v-model="editForm.status" />
                    </UFormField>
                </div>
                <UFormField
                    label="帳號"
                    name="username"
                    :error="editErrors.username"
                    required>
                    <UInput
                        v-model="editForm.username"
                        placeholder="請輸入帳號"
                        size="lg"
                        :disabled="editLoading"
                        class="w-full"
                        @input="clearEditError('username')" />
                </UFormField>

                <UFormField
                    label="密碼（留空則不修改）"
                    name="password"
                    :error="editErrors.password">
                    <UInput
                        v-model="editForm.password"
                        :type="editPassword1Show ? 'text' : 'password'"
                        placeholder="請輸入新密碼（選填）"
                        size="lg"
                        :disabled="editLoading"
                        class="w-full"
                        @input="clearEditError('password')">
                        <template #trailing>
                            <UButton
                                color="neutral"
                                variant="link"
                                size="sm"
                                :icon="
                                    editPassword1Show
                                        ? 'i-lucide-eye-off'
                                        : 'i-lucide-eye'
                                "
                                :aria-label="
                                    editPassword1Show
                                        ? 'Hide password'
                                        : 'Show password'
                                "
                                :aria-pressed="editPassword1Show"
                                aria-controls="password"
                                @click="editPassword1Show = !editPassword1Show" />
                        </template>
                    </UInput>
                </UFormField>

                <UFormField
                    label="再次輸入密碼"
                    name="password_confirmation"
                    :error="editErrors.password_confirmation">
                    <UInput
                        v-model="editForm.password_confirmation"
                        :type="editPassword2Show ? 'text' : 'password'"
                        placeholder="請再次輸入新密碼（選填）"
                        size="lg"
                        :disabled="editLoading"
                        class="w-full"
                        @input="clearEditError('password_confirmation')">
                        <template #trailing>
                            <UButton
                                color="neutral"
                                variant="link"
                                size="sm"
                                :icon="
                                    editPassword2Show
                                        ? 'i-lucide-eye-off'
                                        : 'i-lucide-eye'
                                "
                                :aria-label="
                                    editPassword2Show
                                        ? 'Hide password'
                                        : 'Show password'
                                "
                                :aria-pressed="editPassword2Show"
                                aria-controls="password"
                                @click="editPassword2Show = !editPassword2Show" />
                        </template>
                    </UInput>
                </UFormField>

                <UFormField
                    label="姓名"
                    name="name"
                    required
                    :error="editErrors.name">
                    <UInput
                        v-model="editForm.name"
                        type="text"
                        placeholder="請輸入姓名"
                        size="lg"
                        :disabled="editLoading"
                        class="w-full"
                        @input="clearEditError('name')" />
                </UFormField>

                <UFormField label="電話" name="phone" :error="editErrors.phone">
                    <UInput
                        v-model="editForm.phone"
                        type="text"
                        placeholder="請輸入電話"
                        size="lg"
                        :disabled="editLoading"
                        class="w-full"
                        @input="clearEditError('phone')" />
                </UFormField>

                <UFormField label="地址" name="address" :error="editErrors.address">
                    <UInput
                        v-model="editForm.address"
                        type="text"
                        placeholder="請輸入地址"
                        size="lg"
                        :disabled="editLoading"
                        class="w-full"
                        @input="clearEditError('address')" />
                </UFormField>
                <UButton
                    type="submit"
                    block
                    size="lg"
                    :loading="editLoading"
                    :disabled="editLoading">
                    更新管理員
                </UButton>
                <div v-if="editSubmitError" class="text-sm text-red-500">
                    {{ editSubmitError }}
                </div>
            </UForm>
        </template>
    </UModal>
</template>
