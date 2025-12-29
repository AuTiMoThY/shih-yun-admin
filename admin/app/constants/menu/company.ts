import type { NavigationMenuItem } from "@nuxt/ui";

export const company: (open: Ref<boolean>) => NavigationMenuItem = (open) => ({
    label: "公司基本資料設定",
    icon: "lucide:building-2",
    to: "/company-capital",
    onSelect: () => {
        open.value = false;
    },
});

