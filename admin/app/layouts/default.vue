<script setup lang="ts">
import type { NavigationMenuItem } from "@nuxt/ui";

import { website } from "~/constants/menu/website";
import { company } from "~/constants/menu/company";
import { system } from "~/constants/menu/system";

const route = useRoute();
const open = ref(false);
const { asideData: structureData, fetchDataForAside: fetchStructureForAside } = useStructure();
const { data: modulesData, fetchData: fetchModules } = useModule();
const { hasPermission, isSuperAdmin } = usePermission();

// 檢查某個 menu item 或其子層級是否包含當前路由
const hasActiveRoute = (item: any, currentPath: string): boolean => {
    // 如果當前 item 有 to 且匹配當前路由
    const itemPath =
        item?.to || item?.path || (item?.name ? `/${item.name}` : undefined);
    if (itemPath && currentPath === itemPath) {
        return true;
    }

    // 檢查子層級
    if (item?.children && item.children.length > 0) {
        return item.children.some((child: any) =>
            hasActiveRoute(child, currentPath)
        );
    }

    return false;
};

const resolveModulePath = (item: any): string | undefined => {
    // 優先使用自訂 URL
    if (item?.url) {
        return item.url.startsWith('/') ? item.url : `/${item.url}`;
    }
    
    // 如果沒有自訂 URL，使用模組的 name
    if (item?.module_id) {
        const found = modulesData.value?.find(
            (m: any) => String(m.id) === String(item.module_id)
        );
        return found?.name ? `/${found.name}` : undefined;
    }
    
    return undefined;
};

// 檢查項目是否啟用
const isItemActive = (item: any): boolean => {
    const status = item?.status;
    return status === 1 || status === '1' || status === true;
};

// 檢查項目是否有權限
const hasItemPermission = (item: any): boolean => {
    // 超級管理員擁有所有權限
    if (isSuperAdmin()) {
        return true;
    }

    // 如果沒有關聯模組，則不需要檢查權限（例如父層級）
    if (!item?.module_id) {
        return true;
    }

    // 如果沒有 url，表示不是實際的單元（可能是父層級），不需要檢查權限
    if (!item?.url) {
        return true;
    }

    // 根據單元的 url 構建權限名稱（格式：{url}.view）
    // 例如：url='about' → 權限名稱='about.view'
    // 例如：url='media' → 權限名稱='media.view'
    const permissionName = `${item.url}.view`;

    // 檢查是否有權限
    return hasPermission(permissionName);
};

const mapStructureToMenu = (
    item: any,
    sidebarOpen: Ref<boolean>
): NavigationMenuItem | null => {
    // 過濾停用的項目
    if (!isItemActive(item)) {
        return null;
    }

    // 過濾沒有權限的項目
    if (!hasItemPermission(item)) {
        return null;
    }

    const hasChildren = item?.children && item?.children.length > 0;
    const currentPath = route.path;
    const isActive = hasActiveRoute(item, currentPath);

    const baseMenu: NavigationMenuItem = {
        label: item?.label,
        icon: item?.icon || "i-lucide-network"
    };

    if (hasChildren) {
        // 有子層級：建立 children 陣列，並過濾停用的子項目
        const activeChildren = item.children
            .map((child: any) => mapStructureToMenu(child, sidebarOpen))
            .filter((child: any) => child !== null);
        
        // 如果所有子項目都被過濾掉，則不顯示此項目
        if (activeChildren.length === 0) {
            return null;
        }

        return {
            ...baseMenu,
            defaultOpen: !isActive, // 如果包含當前路由，不收起（展開）
            children: activeChildren
        };
    } else {
        // 無子層級：設定 to 屬性
        return {
            ...baseMenu,
            to:
                resolveModulePath(item) ||
                item?.to ||
                item?.path ||
                (item?.name ? `/${item.name}` : undefined),
            onSelect: () => {
                sidebarOpen.value = false;
            }
        };
    }
};

const buildStructureMenu = (
    sidebarOpen: Ref<boolean>
): NavigationMenuItem[] => {
    return (structureData.value || [])
        .map((item) => mapStructureToMenu(item, sidebarOpen))
        .filter((item): item is NavigationMenuItem => item !== null);
};

// 檢查當前路由是否在 system 子項目中
const checkSystemActive = (): boolean => {
    const currentPath = route.path;
    const systemPaths = [
        "/system/structure",
        "/system/module",
        "/system/admins",
        "/system/permissions",
        "/system/roles"
    ];
    return systemPaths.includes(currentPath);
};

const isSystemActive = computed(() => checkSystemActive());

// 建立系統選單並檢查是否有子項目
const systemMenu = computed(() => system(isSystemActive));

const links = computed(() => {
    const structureMenuItems = buildStructureMenu(open);
    const menuItems: NavigationMenuItem[] = [
        ...[
            {
                label: "模擬前台聯絡表單",
                icon: "i-lucide-form-input",
                to: "/contact/contact-frontend",
                onSelect: () => {
                    open.value = false;
                }
            }
        ],
        website(open),
        ...structureMenuItems,
        company(open)
    ];
    // 只有當系統選單有子項目時才加入
    const systemMenuItem = systemMenu.value;
    if (systemMenuItem.children && systemMenuItem.children.length > 0) {
        menuItems.push(systemMenuItem);
    }

    return [menuItems] as NavigationMenuItem[][];
});

// const groups = computed(() => [
//     {
//         id: "links",
//         label: "Go to",
//         items: links.flat(),
//     },
// ]);

onMounted(() => {
    console.log("[default layout] onMounted, route:", route.path);
    console.log("[default layout] structureData:", structureData.value);
    // 確保初次載入取得樹狀結構，並共享後續更新（如排序變動）
    // 使用非阻塞方式載入，避免阻塞頁面渲染
    if (!structureData.value?.length) {
        fetchStructureForAside().catch((err) => {
            console.error("fetchStructureForAside error:", err);
        });
    }
    if (!modulesData.value?.length) {
        fetchModules().catch((err) => {
            console.error("fetchModules error:", err);
        });
    }
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
                    v-if="links && links[0] && links[0].length > 0"
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
