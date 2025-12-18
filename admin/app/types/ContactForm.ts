export interface ContactForm {
    name: string;
    phone: string;
    email: string;
    project?: string; // 選填
    message?: string; // 選填
}

export interface ContactFormErrors {
    name?: string | false;
    phone?: string | false;
    email?: string | false;
    project?: string | false;
    message?: string | false;
}