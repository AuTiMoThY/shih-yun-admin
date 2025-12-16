export const STATUS_ICON = [
    { label: "i-lucide-badge-check", value: "1" },
    { label: "i-lucide-ban", value: "0" }
];

export const STATUS_ICON_MAP = STATUS_ICON.reduce<Record<string, string>>(
    (acc, { label, value }) => {
        acc[value] = label;
        return acc;
    },
    {}
);
