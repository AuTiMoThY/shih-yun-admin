<script setup>
const colorMode = useColorMode();

// 確保初始模式為 light
if (colorMode.preference !== "light") {
    colorMode.preference = "light";
}

// 監聽 colorMode 的變化，確保 HTML class 同步（只在客戶端執行）
if (process.client) {
    watchEffect(() => {
        const currentValue = colorMode.value;

        console.log("colorMode preference:", colorMode.preference);
        console.log("colorMode value:", currentValue);
        console.log("HTML class:", document.documentElement.className);

        // 確保 HTML class 與 colorMode.value 同步
        if (currentValue === "light") {
            document.documentElement.classList.remove("dark");
        } else if (currentValue === "dark") {
            document.documentElement.classList.add("dark");
        }
    });

    // 在 mounted 時確保設定正確
    onMounted(() => {
        // 確保 preference 為 light
        if (colorMode.preference !== "light") {
            colorMode.preference = "light";
        }

        // 立即同步 HTML class
        if (colorMode.value === "light") {
            document.documentElement.classList.remove("dark");
        } else {
            document.documentElement.classList.add("dark");
        }
    });
}

useHead({
    meta: [
        { name: "viewport", content: "width=device-width, initial-scale=1" }
    ],
    link: [{ rel: "icon", href: "/favicon.ico" }],
    htmlAttrs: {
        lang: "zh-TW"
    }
});

const title = "石云建設 | 後台管理系統";
const description = "後台管理系統";

useSeoMeta({
    title,
    description,
    ogTitle: title,
    ogDescription: description
    // ogImage: "https://ui.nuxt.com/assets/templates/nuxt/starter-light.png",
    // twitterImage: "https://ui.nuxt.com/assets/templates/nuxt/starter-light.png",
    // twitterCard: "summary_large_image"
});
</script>

<template>
    <UApp>
        <NuxtLoadingIndicator />

        <NuxtLayout>
            <NuxtPage />
        </NuxtLayout>
    </UApp>
</template>
