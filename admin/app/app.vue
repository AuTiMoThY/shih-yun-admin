<script setup>
const colorMode = useColorMode();
console.log("colorMode", colorMode.value);
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
    ogDescription: description,
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
