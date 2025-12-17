import type { NavigationMenuItem } from "@nuxt/ui";

/**
 * 系統選單項目定義
 * 每個項目都有對應的權限，只有擁有權限的使用者才能看到該選單項目
 * super_admin 自動擁有所有權限
 */
interface SystemMenuItem {
    label: string;
    icon: string;
    to: string;
    permission?: string; // 需要的權限名稱；未設定表示任何登入管理員皆可見
    onSelect: () => void;
}

/**
 * 建立系統選單
 * 會根據使用者權限過濾選單項目
 */
export const system: (open: Ref<boolean>) => NavigationMenuItem = (open) => {
    const { hasPermission, isSuperAdmin } = usePermission();

    // 定義所有系統選單項目及其對應權限
    const allMenuItems: SystemMenuItem[] = [
        {
            label: "管理員設定",
            icon: "lucide:user-cog",
            to: "/system/admins",
            onSelect: () => {
                open.value = false;
            },
        },
        {
            label: "系統架構設定",
            icon: "lucide:network",
            to: "/system/structure",
            permission: "system.structure.view",
            onSelect: () => {
                open.value = false;
            },
        },
        {
            label: "模組設定",
            icon: "lucide:package",
            to: "/system/module",
            permission: "system.module.view",
            onSelect: () => {
                open.value = false;
            },
        },

        {
            label: "權限設定",
            icon: "lucide:shield",
            to: "/system/permissions",
            permission: "system.permissions.view",
            onSelect: () => {
                open.value = false;
            },
        },
        {
            label: "角色設定",
            icon: "lucide:shield",
            to: "/system/roles",
            permission: "system.roles.view",
            onSelect: () => {
                open.value = false;
            },
        },
    ];

    // 過濾出有權限的選單項目
    const filteredChildren = allMenuItems
        .filter((item) => {
            // 超級管理員可以看到所有選單
            if (isSuperAdmin()) {
                return true;
            }
            // 其他角色需要檢查權限
            return !item.permission || hasPermission(item.permission);
        })
        .map((item) => ({
            label: item.label,
            icon: item.icon,
            to: item.to,
            onSelect: item.onSelect,
        }));

    // 如果沒有任何子項目，返回空選單（children 為空陣列）
    // 在 default.vue 中會過濾掉沒有子項目的選單
    return {
        label: "系統設定",
        icon: "lucide:settings",
        defaultOpen: open.value, // 如果當前路由在 system 子項目中，展開
        children: filteredChildren,
    };
};

