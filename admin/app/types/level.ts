export type LevelForm = {
    label: string;
    module_id: number | string | null;
    is_show_frontend: boolean;
    is_show_backend: boolean;
    status: boolean;
    parent_id?: number | string | null;
};

export type LevelFormErrors = {
    label: string | boolean;
    module_id: string | boolean;
    is_show_frontend: string | boolean;
    is_show_backend: string | boolean;
    status: string | boolean;
};