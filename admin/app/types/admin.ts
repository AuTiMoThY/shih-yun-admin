export type AdminForm = {
    permission_name?: string; // 保留以向後兼容
    status: string | boolean; // '0' | '1' 或 boolean
    username: string;
    password: string;
    password_confirmation: string;
    name: string;
    phone: string;
    address: string;
    role_ids?: number[]; // RBAC 角色 ID 列表
    permission_ids?: number[]; // RBAC 直接權限 ID 列表
};

export type AdminFormErrors = {
    permission_name?: string | boolean;
    status: string | boolean;
    username: string | boolean;
    password: string | boolean;
    password_confirmation: string | boolean;
    name: string | boolean;
    phone: string | boolean;
    address: string | boolean;
    role_ids?: string | boolean;
    permission_ids?: string | boolean;
};

