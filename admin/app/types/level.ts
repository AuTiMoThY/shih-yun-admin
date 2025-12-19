export type LevelForm = {
    label: string;
    module_id: number | string | null;
    url: string | null;
    status: boolean;
    parent_id?: number | string | null;
};

export type LevelFormErrors = {
    label: string | boolean;
    module_id: string | boolean;
    status: string | boolean;
};