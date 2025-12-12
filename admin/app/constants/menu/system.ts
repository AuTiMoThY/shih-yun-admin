import type { NavigationMenuItem } from "@nuxt/ui";

export const system: (open: Ref<boolean>) => NavigationMenuItem = (open) => {
    return {
        label: "系統設定",
        icon: "lucide:settings",
        defaultOpen: open.value, // 如果當前路由在 system 子項目中，展開
        children: [
            {
                label: "管理系統架構",
                icon: "lucide:network",
                to: "/system/structure",
                onSelect: () => {
                    open.value = false;
                },
            },
            {
                label: "模組設定",
                icon: "lucide:package",
                to: "/system/module",
                onSelect: () => {
                    open.value = false;
                },
            },
            {
                label: "管理員設定",
                icon: "lucide:user-cog",
                to: "/system/admins",
                onSelect: () => {
                    open.value = false;
                },
            },
            {
                label: "權限設定",
                icon: "lucide:shield",
                to: "/system/permissions",
                onSelect: () => {
                    open.value = false;
                },
            },
        ],
    };
};

