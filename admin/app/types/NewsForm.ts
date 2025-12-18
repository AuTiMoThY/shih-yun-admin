export type NewsForm = {
    title: string;
    content: string;
    cover: string;
    slide: string[];
    show_date: string;
    status: number;
};

export type NewsFormErrors = {
    title?: string | boolean;
    content?: string | boolean;
    cover?: string | boolean;
    slide?: string | boolean;
    show_date?: string | boolean;
    status?: string | boolean;
};