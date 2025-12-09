<script setup lang="ts">
definePageMeta({
    middleware: "auth"
});

const addAdmin = () => {
    console.log("新增管理員");
};

const form = reactive({
    username: "",
    password: "",
    password_confirmation: "",
    name: "",
    phone: ""
});

const loading = ref(false);
const errorMessage = ref("");
</script>

<template>
    <UDashboardPanel>
        <template #header>
            <UDashboardNavbar title="管理員設定" :ui="{ right: 'gap-3' }">
                <template #leading>
                    <UDashboardSidebarCollapse />
                </template>
            </UDashboardNavbar>

            <UDashboardToolbar>
                <template #right>
                    <UModal
                        title="新增管理員"
                        :close="{
                            color: 'primary',
                            variant: 'outline',
                            class: 'rounded-full'
                        }">
                        <UButton
                            color="primary"
                            variant="outline"
                            icon="lucide:plus"
                            label="新增管理員" />
                        <template #body>
                            <UForm
                                :state="form"
                                @submit="addAdmin"
                                class="space-y-4">
                                <UFormField
                                    label="帳號"
                                    name="username"
                                    required>
                                    <UInput
                                        v-model="form.username"
                                        placeholder="請輸入帳號"
                                        size="lg"
                                        :disabled="loading"
                                        class="w-full" />
                                </UFormField>

                                <UFormField
                                    label="密碼"
                                    name="password"
                                    required>
                                    <UInput
                                        v-model="form.password"
                                        type="password"
                                        placeholder="請輸入密碼"
                                        size="lg"
                                        :disabled="loading"
                                        class="w-full" />
                                </UFormField>

                                <UFormField
                                    label="再次輸入密碼"
                                    name="password_confirmation"
                                    required>
                                    <UInput
                                        v-model="form.password_confirmation"
                                        type="password"
                                        placeholder="請再次輸入密碼"
                                        size="lg"
                                        :disabled="loading"
                                        class="w-full" />
                                </UFormField>

                                <UFormField
                                    label="姓名"
                                    name="name"
                                    required>
                                    <UInput
                                        v-model="form.name"
                                        type="text"
                                        placeholder="請輸入姓名"
                                        size="lg"
                                        :disabled="loading"
                                        class="w-full" />
                                </UFormField>

                                <UFormField
                                    label="電話"
                                    name="phone"
                                    required>
                                    <UInput
                                        v-model="form.phone"
                                        type="text"
                                        placeholder="請輸入電話"
                                        size="lg"
                                        :disabled="loading"
                                        class="w-full" />
                                </UFormField>

                                <div
                                    v-if="errorMessage"
                                    class="text-sm text-red-500">
                                    {{ errorMessage }}
                                </div>

                                <UButton
                                    type="submit"
                                    block
                                    size="lg"
                                    :loading="loading"
                                    :disabled="loading">
                                    登入
                                </UButton>
                            </UForm>
                        </template>
                    </UModal>
                </template>
            </UDashboardToolbar>
        </template>
        <template #body>
            <UCard>
                <div class="p-8 text-center text-muted-foreground">
                    此功能尚未開發
                </div>
            </UCard>
        </template>
    </UDashboardPanel>
</template>

