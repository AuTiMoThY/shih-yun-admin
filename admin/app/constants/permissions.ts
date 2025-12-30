// 集中管理權限選項，方便後續統一調整
export const PERMISSIONS = [{ label: "管理員", value: "admin" }];

export const PERMISSION_LABEL_MAP = PERMISSIONS.reduce<Record<string, string>>(
    (acc, { label, value }) => {
        acc[value] = label;
        return acc;
    },
    {}
);

// 權限動作選項
export const PERMISSION_ACTIONS = [
    { label: "查看", value: "view" },
    { label: "新增", value: "create" },
    { label: "編輯", value: "edit" },
    { label: "刪除", value: "delete" },
    { label: "排序", value: "sort" },
    { label: "回信", value: "reply" }
];

// 權限分類選項
// section: 區塊（用於內容區塊模組，如 about）
// field: 欄位（用於內容區塊模組的欄位）
export const PERMISSION_CATEGORIES = [
    { label: "區塊", value: "section" },
    { label: "欄位", value: "field" }
];