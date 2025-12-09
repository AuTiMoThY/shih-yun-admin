export const ADMIN_STATUS = [{ label: "啟用中", value: "1" }, { label: "停用中", value: "0" }];

export const ADMIN_STATUS_LABEL_MAP = ADMIN_STATUS.reduce<Record<string, string>>((acc, { label, value }) => {
    acc[value] = label;
    return acc;
}, {});
