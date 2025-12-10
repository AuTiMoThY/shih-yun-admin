export const IS_SHOW_BACKEND = [{ label: "顯示", value: "1" }, { label: "不顯示", value: "0" }];

export const IS_SHOW_BACKEND_LABEL_MAP = IS_SHOW_BACKEND.reduce<Record<string, string>>((acc, { label, value }) => {
    acc[value] = label;
    return acc;
}, {});