export type Permission = {
    id: number;
    name: string;
    label: string;
    description?: string;
    module_id?: number | null;
    category?: string | null;
    action?: string | null;
    status: number;
    created_at?: string;
    updated_at?: string;
};

export type Role = {
    id: number;
    name: string;
    label: string;
    description?: string;
    status: number;
    created_at?: string;
    updated_at?: string;
    permissions?: Permission[];
};

export type RermissionForm = {
    name: string;
    label: string;
    description?: string;
    module_id?: number | null;
    category?: string | null;
    action?: string | null;
    status: number;
};

export type RermissionFormErrors = {
    name: string | boolean;
    label: string | boolean;
};

export type RoleForm = {
    name: string;
    label: string;
    description: string;
    status: number;
    permission_ids: number[];
};

export type RoleFormErrors = {
    name: string | boolean;
    label: string | boolean;
};