export const STATUS = [{ label: "啟用中", value: "1" }, { label: "停用中", value: "0" }];

export const STATUS_LABEL_MAP = STATUS.reduce<Record<string, string>>((acc, { label, value }) => {
    acc[value] = label;
    return acc;
}, {});
