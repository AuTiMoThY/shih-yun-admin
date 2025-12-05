<script setup lang="ts">
import type { NavigationMenuItem } from "@nuxt/ui";
import { website } from "~/data/menu/website";
import { company } from "~/data/menu/company";
import { system } from "~/data/menu/system";

const route = useRoute();
const toast = useToast();

const open = ref(false);

const links = [[website, company, system]] as NavigationMenuItem[][];

// const groups = computed(() => [
//     {
//         id: "links",
//         label: "Go to",
//         items: links.flat(),
//     },
// ]);

onMounted(async () => {
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
            :ui="{ footer: 'lg:border-t lg:border-default' }">
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
