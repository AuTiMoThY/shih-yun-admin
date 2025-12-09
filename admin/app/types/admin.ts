export type AddAdminForm = {
    permission_name: string;
    status: boolean;
    username: string;
    password: string;
    password_confirmation: string;
    name: string;
    phone: string;
    address: string;
};

export type AddAdminFormErrors = {
    permission_name: string | boolean;
    status: string | boolean;
    username: string | boolean;
    password: string | boolean;
    password_confirmation: string | boolean;
    name: string | boolean;
    phone: string | boolean;
    address: string | boolean;
};

