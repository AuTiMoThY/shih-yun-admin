import type { NavigationMenuItem } from "@nuxt/ui";

export const system: NavigationMenuItem = {
    label: "系統設定",
    icon: "lucide:settings",
    children: [
        {
            label: "管理系統架構",
            icon: "lucide:network",
            to: "/system/structure",
        },
        {
            label: "管理員設定",
            icon: "lucide:user-cog",
            to: "/system/admins",
        },
        {
            label: "權限設定",
            icon: "lucide:shield",
            to: "/system/permissions",
        },
    ],
};

