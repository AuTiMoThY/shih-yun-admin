<script setup lang="ts">
import type { NavigationMenuItem } from "@nuxt/ui";

import { website } from "~/constants/menu/website";
import { company } from "~/constants/menu/company";
import { system } from "~/constants/menu/system";

const route = useRoute();
const open = ref(false);
const { data: structureData, fetchData: fetchStructure } = useStructure();
const { data: modulesData, fetchData: fetchModules } = useModule();

// 檢查某個 menu item 或其子層級是否包含當前路由
const hasActiveRoute = (item: any, currentPath: string): boolean => {
    // 如果當前 item 有 to 且匹配當前路由
    const itemPath = item?.to || item?.path || (item?.name ? `/${item.name}` : undefined);
    if (itemPath && currentPath === itemPath) {
        return true;
    }
    
    // 檢查子層級
    if (item?.children && item.children.length > 0) {
        return item.children.some((child: any) => hasActiveRoute(child, currentPath));
    }
    
    return false;
};

const resolveModulePath = (moduleId: any): string | undefined => {
    if (!moduleId) return undefined;
    const found = modulesData.value?.find(
        (m: any) => String(m.id) === String(moduleId)
    );
    return found?.name ? `/${found.name}` : undefined;
};

const mapStructureToMenu = (item: any, sidebarOpen: Ref<boolean>): NavigationMenuItem => {
    const hasChildren = item?.children && item?.children.length > 0;
    const currentPath = route.path;
    const isActive = hasActiveRoute(item, currentPath);
    
    const baseMenu: NavigationMenuItem = {
        label: item?.label,
        icon: item?.icon || "lucide:network",
    };
    
    if (hasChildren) {
        // 有子層級：建立 children 陣列
        return {
            ...baseMenu,
            open: !isActive, // 如果包含當前路由，不收起（展開）
            children: item.children.map((child: any) => mapStructureToMenu(child, sidebarOpen))
        };
    } else {
        // 無子層級：設定 to 屬性
        return {
            ...baseMenu,
            to:
                resolveModulePath(item?.module_id) ||
                item?.to ||
                item?.path ||
                (item?.name ? `/${item.name}` : undefined),
            onSelect: () => {
                sidebarOpen.value = false;
            },
        };
    }
};


const buildStructureMenu = (sidebarOpen: Ref<boolean>): NavigationMenuItem[] => {
    return (structureData.value || []).map(item => mapStructureToMenu(item, sidebarOpen));
};

// 檢查當前路由是否在 system 子項目中
const checkSystemActive = (): boolean => {
    const currentPath = route.path;
    const systemPaths = ["/system/structure", "/system/module", "/system/admins", "/system/permissions"];
    return systemPaths.includes(currentPath);
};

const isSystemActive = computed(() => checkSystemActive());
const links = computed(() => {
    const structureMenuItems = buildStructureMenu(open);
    return [
        [website(open), ...structureMenuItems, company(open), system(isSystemActive)]
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
    if (!modulesData.value?.length) {
        await fetchModules();
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
