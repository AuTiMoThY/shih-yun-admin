// 集中管理權限選項，方便後續統一調整
export const PERMISSIONS = [{ label: "管理員", value: "admin" }];

export const PERMISSION_LABEL_MAP = PERMISSIONS.reduce<Record<string, string>>(
    (acc, { label, value }) => {
        acc[value] = label;
        return acc;
    },
    {}
);

