export const IS_SHOW_FRONTEND = [{ label: "顯示", value: "1" }, { label: "不顯示", value: "0" }];

export const IS_SHOW_FRONTEND_LABEL_MAP = IS_SHOW_FRONTEND.reduce<Record<string, string>>((acc, { label, value }) => {
    acc[value] = label;
    return acc;
}, {});