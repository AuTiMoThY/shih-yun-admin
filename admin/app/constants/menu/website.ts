import type { NavigationMenuItem } from "@nuxt/ui";

export const website: (open: Ref<boolean>) => NavigationMenuItem = (open) => ({
    label: "前往網頁",
    icon: "lucide:external-link",
    to: "https://test-sys.srl.tw/", // 請替換成實際的前端網站網址
    target: "_blank",
    onSelect: () => {
        open.value = false;
    },
});

