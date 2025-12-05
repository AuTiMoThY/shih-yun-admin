<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 px-4">
        <UCard class="w-full max-w-md">
            <template #header>
                <div class="text-center">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                        後台管理系統
                    </h2>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        請登入您的帳號
                    </p>
                </div>
            </template>

            <UForm :state="form" @submit="handleLogin" class="space-y-4">
                <UFormField label="帳號" name="username" required>
                    <UInput
                        v-model="form.username"
                        placeholder="請輸入帳號"
                        size="lg"
                        :disabled="loading"
                        class="w-full"
                    />
                </UFormField>

                <UFormField label="密碼" name="password" required>
                    <UInput
                        v-model="form.password"
                        type="password"
                        placeholder="請輸入密碼"
                        size="lg"
                        :disabled="loading"
                        class="w-full"
                    />
                </UFormField>

                <div v-if="errorMessage" class="text-sm text-red-500">
                    {{ errorMessage }}
                </div>

                <UButton
                    type="submit"
                    block
                    size="lg"
                    :loading="loading"
                    :disabled="loading"
                >
                    登入
                </UButton>
            </UForm>

            <template #footer>
                <div class="text-center text-sm text-gray-500 dark:text-gray-400">
                    <p>測試帳號：admin / admin</p>
                </div>
            </template>
        </UCard>
    </div>
</template>

<script setup lang="ts">
definePageMeta({
    layout: false, // 不使用預設 layout
    middleware: 'auth', // 使用 auth middleware（會在已登入時導向首頁）
});

const router = useRouter();
const { login } = useAuth();

const form = reactive({
    username: '',
    password: '',
});

const loading = ref(false);
const errorMessage = ref('');

const handleLogin = async () => {
    loading.value = true;
    errorMessage.value = '';

    try {
        const result = await login(form.username, form.password);

        if (result.success) {
            // 登入成功，導向首頁
            await router.push('/');
        } else {
            // 顯示錯誤訊息
            errorMessage.value = result.message;
        }
    } catch (error: any) {
        errorMessage.value = error.message || '登入時發生錯誤';
    } finally {
        loading.value = false;
    }
};
</script>

