export interface ContactForm {
    status: number;
    reply?: string; // 選填
}

export interface ContactFormErrors {
    status: number | false;
    reply?: string | false;
}