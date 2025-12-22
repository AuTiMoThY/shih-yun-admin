<script setup lang="ts">
definePageMeta({
    middleware: "auth"
});

const { submitContact, loading } = useAppContact();
const toast = useToast();
const route = useRoute();

// 從 URL 查詢參數取得 structureId（適用於前台獨立應用）
// const structureId = computed(() => {
//     const id = route.query.structure_id || route.query.structureId;
//     if (id) {
//         const parsedId = typeof id === 'string' ? parseInt(id, 10) : Number(id);
//         return isNaN(parsedId) ? null : parsedId;
//     }
//     return null;
// });
const structureId = ref(28);


const form = reactive<{
    name: string;
    phone: string;
    email: string;
    message?: string;
}>({
    name: "",
    phone: "",
    email: "",
    message: undefined,
});

const errors = reactive<{
    name: string | false;
    phone: string | false;
    email: string | false;
    message?: string | false;
}>({
    name: false,
    phone: false,
    email: false,
    message: false,
});

const clearError = (field: keyof typeof errors) => {
    errors[field as keyof typeof errors] = false;
};

const validateForm = (): boolean => {
    // 清除所有錯誤
    Object.keys(errors).forEach((key) => {
        errors[key as keyof typeof errors] = false;
    });

    let isValid = true;

    // 驗證姓名
    if (!form.name || form.name.trim() === "") {
        errors.name = "請輸入姓名";
        isValid = false;
    }

    // 驗證電話
    if (!form.phone || form.phone.trim() === "") {
        errors.phone = "請輸入電話";
        isValid = false;
    }
    else {
        const phonePattern = /^[0-9-+()]+$/;
        if (!phonePattern.test(form.phone.trim())) {
            errors.phone = "請輸入有效的電話格式";
            isValid = false;
        }
    }

    // 驗證信箱
    if (!form.email || form.email.trim() === "") {
        errors.email = "請輸入信箱";
        isValid = false;
    } 
    else {
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(form.email.trim())) {
            errors.email = "請輸入有效的信箱格式";
            isValid = false;
        }
    }

    return isValid;
};

const handleSubmit = async (event: Event) => {
    event.preventDefault();

    if (!validateForm()) {
        return;
    }

    const submitData: typeof form = {
        name: form.name.trim(),
        phone: form.phone.trim(),
        email: form.email.trim(),
        message: form.message?.trim() || undefined,
    };

    // 從 URL 查詢參數取得 structureId
    const currentStructureId = structureId.value;

    const result = await submitContact({ status: 0, ...submitData }, currentStructureId);

    if (result.success) {
        // 重置表單
        form.name = "";
        form.phone = "";
        form.email = "";
        form.message = "";
        Object.keys(errors).forEach((key) => {
            errors[key as keyof typeof errors] = false;
        });
    }
};

</script>
<template>
    <UDashboardPanel>
        <template #header>
            <UDashboardNavbar
                title="聯絡表單測試"
                :ui="{ right: 'gap-3', title: 'text-primary' }">
                <template #leading>
                    <UDashboardSidebarCollapse />
                </template>
            </UDashboardNavbar>
        </template>
        <template #body>
            <div class="w-2xl mx-auto p-6">
                <UCard>
                    <template #header>
                        <div class="text-xl font-semibold">聯絡表單</div>
                    </template>
                    <UForm :state="form" @submit="handleSubmit" class="space-y-4">
                        <UFormField
                            label="姓名"
                            name="name"
                            :error="errors.name"
                            required>
                            <UInput
                                v-model="form.name"
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
                            required>
                            <UInput
                                v-model="form.phone"
                                placeholder="請輸入電話"
                                size="lg"
                                :disabled="loading"
                                class="w-full"
                                @input="clearError('phone')" />
                        </UFormField>

                        <UFormField
                            label="信箱"
                            name="email"
                            :error="errors.email"
                            required>
                            <UInput
                                v-model="form.email"
                                type="email"
                                placeholder="請輸入信箱"
                                size="lg"
                                :disabled="loading"
                                class="w-full"
                                @input="clearError('email')" />
                        </UFormField>

                        <UFormField
                            label="留言"
                            name="message"
                            :error="errors.message">
                            <UTextarea
                                v-model="form.message"
                                placeholder="請輸入留言（選填）"
                                :rows="5"
                                :disabled="loading"
                                class="w-full"
                                @input="clearError('message')" />
                        </UFormField>

                        <div class="flex justify-end gap-3 pt-4">
                            <UButton
                                type="submit"
                                color="primary"
                                size="lg"
                                :loading="loading"
                                :disabled="loading">
                                提交表單
                            </UButton>
                        </div>
                    </UForm>
                </UCard>
            </div>
        </template>
        <template #footer>
            <PageFooter />
        </template>
    </UDashboardPanel>
</template>
