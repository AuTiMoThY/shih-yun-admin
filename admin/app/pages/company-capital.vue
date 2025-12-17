<script setup lang="ts">
definePageMeta({
    middleware: "auth"
});

import { getCityArray, getDistrictArray, getZipCode } from "@simoko/tw-zip";

const { form, errors, loading, submitError, clearError, fetchData, save } =
    useCompanyBase();

const cities = computed(() => {
    return getCityArray().map((item: string) => ({ label: item, value: item }));
});

const districts = ref<any[]>([]);
const handleSave = (e: Event) => {
    e.preventDefault();
    console.log(form);
    save();
};

// 標記是否正在載入初始資料
const isInitialLoad = ref(true);

watch(
    () => form.city,
    (newVal) => {
        // 如果有選擇城市
        if (newVal) {
            // 先清空行政區列表
            districts.value = [];
            // 如果不是初始載入，清空已選的行政區（因為城市變了，原本的行政區選擇就不適用了）
            if (!isInitialLoad.value) {
                form.district = "";
            }
            // 抓取對應的行政區列表
            districts.value = getDistrictArray(newVal).map((item) => ({
                label: item.label,
                value: item.label
            }));
        }
    },
    { immediate: true }
);

watch(
    () => form.district,
    (newVal) => {
        if (newVal) {
            const zipCodes = getZipCode(newVal);
            console.log(zipCodes);
            form.zipcode = zipCodes?.[0] ?? "";
        }
    },
    { immediate: true }
);

onMounted(async () => {
    await fetchData();
    // 資料載入完成後，標記初始載入結束
    await nextTick();
    isInitialLoad.value = false;
    console.log(form);
});
</script>

<template>
    <UDashboardPanel>
        <template #header>
            <UDashboardNavbar title="公司資本資料設定" :ui="{ right: 'gap-3', title: 'text-primary' }">
                <template #leading>
                    <UDashboardSidebarCollapse color="primary" />
                </template>
                <template #right>
                    <UButton
                        label="儲存"
                        color="success"
                        icon="i-lucide-save"
                        :loading="loading"
                        :disabled="loading"
                        @click="handleSave($event)" />
                </template>
            </UDashboardNavbar>
        </template>
        <template #body>
            <PageLoading v-if="loading" />
            <UForm
                v-else
                :state="form"
                @submit="handleSave($event)"
                class="space-y-6">
                <!-- 基本資訊 -->
                <UCard
                    :ui="{ root: 'overflow-visible', header: 'bg-primary/10' }">
                    <template #header>
                        <h3 class="text-lg font-semibold">基本資訊</h3>
                    </template>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <UFormField
                            label="公司名稱"
                            name="name"
                            :error="errors.name">
                            <UInput
                                v-model="form.name"
                                type="text"
                                placeholder="請輸入公司名稱"
                                size="lg"
                                :disabled="loading"
                                class="w-full"
                                @input="clearError('name')" />
                        </UFormField>
                        <UFormField
                            label="版權資訊"
                            name="copyright"
                            :error="errors.copyright">
                            <UInput
                                v-model="form.copyright"
                                type="text"
                                placeholder="請輸入版權資訊"
                                size="lg"
                                :disabled="loading"
                                class="w-full"
                                @input="clearError('copyright')" />
                        </UFormField>
                    </div>
                </UCard>

                <!-- 聯絡資訊 -->
                <UCard
                    :ui="{ root: 'overflow-visible', header: 'bg-primary/10' }">
                    <template #header>
                        <h3 class="text-lg font-semibold">聯絡資訊</h3>
                    </template>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <UFormField
                            label="電話"
                            name="phone"
                            :error="errors.phone">
                            <UInput
                                v-model="form.phone"
                                type="text"
                                placeholder="請輸入電話"
                                size="lg"
                                :disabled="loading"
                                class="w-full"
                                @input="clearError('phone')" />
                        </UFormField>
                        <UFormField label="傳真" name="fax" :error="errors.fax">
                            <UInput
                                v-model="form.fax"
                                type="text"
                                placeholder="請輸入傳真"
                                size="lg"
                                :disabled="loading"
                                class="w-full"
                                @input="clearError('fax')" />
                        </UFormField>
                        <UFormField
                            label="電子郵件"
                            name="email"
                            :error="errors.email">
                            <UInput
                                v-model="form.email"
                                type="email"
                                placeholder="請輸入電子郵件"
                                size="lg"
                                :disabled="loading"
                                class="w-full"
                                @input="clearError('email')" />
                        </UFormField>
                        <UFormField
                            label="預約賞屋信箱"
                            name="case_email"
                            :error="errors.case_email">
                            <UInput
                                v-model="form.case_email"
                                type="email"
                                placeholder="請輸入預約賞屋信箱"
                                size="lg"
                                :disabled="loading"
                                class="w-full"
                                @input="clearError('case_email')" />
                        </UFormField>
                    </div>
                </UCard>
                <!-- 地址資訊 -->
                <UCard
                    :ui="{ root: 'overflow-visible', header: 'bg-primary/10' }">
                    <template #header>
                        <h3 class="text-lg font-semibold">地址資訊</h3>
                    </template>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                        <UFormField
                            label="郵遞區號"
                            name="zipcode"
                            :error="errors.zipcode">
                            <UInput
                                v-model="form.zipcode"
                                placeholder="請選擇郵遞區號"
                                size="lg"
                                :disabled="loading"
                                class="w-full" />
                        </UFormField>
                        <UFormField
                            label="城市"
                            name="city"
                            :error="errors.city">
                            <USelect
                                v-model="form.city"
                                :items="cities"
                                placeholder="請輸入城市"
                                size="lg"
                                :disabled="loading"
                                class="w-full" />
                        </UFormField>
                        <UFormField
                            label="行政區"
                            name="district"
                            :error="errors.district">
                            <USelect
                                v-model="form.district"
                                :items="districts"
                                placeholder="請選擇行政區"
                                size="lg"
                                :disabled="loading"
                                class="w-full" />
                        </UFormField>
                    </div>
                    <UFormField
                        label="地址"
                        name="address"
                        :error="errors.address">
                        <UInput
                            v-model="form.address"
                            type="text"
                            placeholder="請輸入完整地址"
                            size="lg"
                            :disabled="loading"
                            class="w-full"
                            @input="clearError('address')" />
                    </UFormField>
                </UCard>

                <!-- 社群媒體 -->
                <UCard
                    :ui="{ root: 'overflow-visible', header: 'bg-primary/10' }">
                    <template #header>
                        <h3 class="text-lg font-semibold">社群媒體</h3>
                    </template>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <UFormField
                            label="Facebook URL"
                            name="fb_url"
                            :error="errors.fb_url">
                            <UInput
                                v-model="form.fb_url"
                                type="url"
                                placeholder="https://www.facebook.com/..."
                                size="lg"
                                :disabled="loading"
                                class="w-full"
                                @input="clearError('fb_url')" />
                        </UFormField>
                        <UFormField
                            label="YouTube URL"
                            name="yt_url"
                            :error="errors.yt_url">
                            <UInput
                                v-model="form.yt_url"
                                type="url"
                                placeholder="https://www.youtube.com/..."
                                size="lg"
                                :disabled="loading"
                                class="w-full"
                                @input="clearError('yt_url')" />
                        </UFormField>
                        <UFormField
                            label="LINE URL"
                            name="line_url"
                            :error="errors.line_url">
                            <UInput
                                v-model="form.line_url"
                                type="url"
                                placeholder="https://line.me/..."
                                size="lg"
                                :disabled="loading"
                                class="w-full"
                                @input="clearError('line_url')" />
                        </UFormField>
                    </div>
                </UCard>
                <!-- SEO 設定 -->
                <UCard
                    :ui="{ root: 'overflow-visible', header: 'bg-primary/10' }">
                    <template #header>
                        <h3 class="text-lg font-semibold">SEO 設定</h3>
                    </template>
                    <UFormField
                        label="關鍵字"
                        name="keywords"
                        :error="errors.keywords">
                        <UTextarea
                            v-model="form.keywords"
                            placeholder="請輸入關鍵字（多個關鍵字請用逗號分隔）"
                            :rows="3"
                            :disabled="loading"
                            class="w-full"
                            @input="clearError('keywords')" />
                    </UFormField>
                    <UFormField
                        label="描述"
                        name="description"
                        :error="errors.description">
                        <UTextarea
                            v-model="form.description"
                            placeholder="請輸入網站描述"
                            :rows="4"
                            :disabled="loading"
                            class="w-full"
                            @input="clearError('description')" />
                    </UFormField>
                </UCard>

                <!-- 代碼設定 -->
                <UCard
                    :ui="{ root: 'overflow-visible', header: 'bg-primary/10' }">
                    <template #header>
                        <h3 class="text-lg font-semibold">代碼設定</h3>
                    </template>
                    <UFormField
                        label="<head> 代碼"
                        name="head_code"
                        :error="errors.head_code">
                        <UTextarea
                            v-model="form.head_code"
                            placeholder="請輸入要在 <head> 標籤中插入的代碼（如：Google Analytics）"
                            :rows="6"
                            :disabled="loading"
                            class="w-full font-mono text-sm"
                            @input="clearError('head_code')" />
                    </UFormField>
                    <UFormField
                        label="<body> 代碼"
                        name="body_code"
                        :error="errors.body_code">
                        <UTextarea
                            v-model="form.body_code"
                            placeholder="請輸入要在 <body> 標籤中插入的代碼（如：Facebook Pixel）"
                            :rows="6"
                            :disabled="loading"
                            class="w-full font-mono text-sm"
                            @input="clearError('body_code')" />
                    </UFormField>
                </UCard>
                <!-- 提交按鈕 -->
                <div class="flex justify-end gap-4 pt-4">
                    <UButton
                        label="儲存"
                        color="success"
                        icon="i-lucide-save"
                        :loading="loading"
                        :disabled="loading"
                        @click="handleSave($event)" />
                </div>

                <div
                    v-if="submitError"
                    class="text-sm text-red-500 dark:text-red-400">
                    {{ submitError }}
                </div>
            </UForm>
        </template>
        <template #footer>
            <PageFooter />
        </template>
    </UDashboardPanel>
</template>
