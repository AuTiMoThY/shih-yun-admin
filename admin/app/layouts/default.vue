<script setup lang="ts">
import type { NavigationMenuItem } from "@nuxt/ui";

import { website } from "~/data/menu/website";
import { company } from "~/data/menu/company";
import { system } from "~/data/menu/system";

const route = useRoute();
const open = ref(false);
const { data: structureData, fetchData: fetchStructure } = useStructure();

const mapStructureToMenu = (item: any): NavigationMenuItem => ({
    label: item?.label,
    icon: "lucide:network",
    // 遞迴建立子層級選單
    children: (item?.children || []).map(mapStructureToMenu)
});

const buildStructureMenu = (): NavigationMenuItem[] => {
    return (structureData.value || []).map(mapStructureToMenu);
};

const links = computed(() => {
    const structureMenuItems = buildStructureMenu();
    return [
        [website, ...structureMenuItems, company, system]
    ] as NavigationMenuItem[][];
});

// const groups = computed(() => [
//     {
//         id: "links",
//         label: "Go to",
//         items: links.flat(),
//     },
// ]);

onMounted(async () => {
    // 確保初次載入取得樹狀結構，並共享後續更新（如排序變動）
    if (!structureData.value?.length) {
        await fetchStructure();
    }
    // const cookie = useCookie("cookie-consent");
    // if (cookie.value === "accepted") {
    //     return;
    // }
    // toast.add({
    //     title: "We use first-party cookies to enhance your experience on our website.",
    //     duration: 0,
    //     close: false,
    //     actions: [
    //         {
    //             label: "Accept",
    //             color: "neutral",
    //             variant: "outline",
    //             onClick: () => {
    //                 cookie.value = "accepted";
    //             },
    //         },
    //         {
    //             label: "Opt out",
    //             color: "neutral",
    //             variant: "ghost",
    //         },
    //     ],
    // });
});
</script>

<template>
    <UDashboardGroup unit="rem">
        <UDashboardSidebar
            id="default"
            v-model:open="open"
            collapsible
            resizable
            class="bg-elevated/25"
            :ui="{
                header: 'dark:bg-gray-100',
                footer: 'lg:border-t lg:border-default'
            }">
            <template #header="{ collapsed }">
                <NuxtLink to="/">
                    <NuxtImg
                        v-if="!collapsed"
                        src="/images/logo.svg"
                        alt="logo"
                        class="w-auto h-8 shrink-0" />
                    <span v-else class="text-2xl font-bold">石</span>
                </NuxtLink>
            </template>

            <template #default="{ collapsed }">
                <UDashboardSearchButton
                    :collapsed="collapsed"
                    class="bg-transparent ring-default" />

                <UNavigationMenu
                    :collapsed="collapsed"
                    :items="links[0]"
                    orientation="vertical"
                    tooltip
                    popover
                    highlight
                    :ui="{
                        linkLabel: 'menu-item'
                    }" />
            </template>

            <template #footer="{ collapsed }">
                <UserMenu v-if="!collapsed" :collapsed="collapsed" />
                <UButton
                    v-else
                    color="neutral"
                    variant="ghost"
                    icon="lucide:user"
                    class="w-full" />
            </template>
        </UDashboardSidebar>

        <slot />
    </UDashboardGroup>
</template>
