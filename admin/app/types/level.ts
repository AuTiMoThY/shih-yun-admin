export type AddLevelForm = {
    name: string;
    is_show_frontend: boolean;
    is_show_backend: boolean;
    status: boolean;
    parent_id?: number | string | null;
};

export type AddLevelFormErrors = {
    name: string | boolean;
    is_show_frontend: string | boolean;
    is_show_backend: string | boolean;
    status: string | boolean;
};