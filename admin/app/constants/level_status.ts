export const LEVEL_STATUS = [{ label: "上線中", value: "1" }, { label: "關閉", value: "0" }];

export const LEVEL_STATUS_LABEL_MAP = LEVEL_STATUS.reduce<Record<string, string>>((acc, { label, value }) => {
    acc[value] = label;
    return acc;
}, {});