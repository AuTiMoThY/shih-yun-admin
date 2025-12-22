import type { CutSectionData } from "~/types/CutSectionField";

export interface CaseForm {
    year: number | null;
    title: string;
    s_text: string;
    cover: string;
    content: CutSectionData[];
    slide: string[];
    ca_type: string;
    ca_area: string;
    ca_square: string;
    ca_phone: string;
    ca_adds: string;
    ca_map: string;
    ca_pop_type: string;
    ca_pop_img: string;
    is_sale: number;
    is_msg: number;
    sort: number;
    status: number;
}

export interface CaseFormErrors {
    year: string | boolean;
    title: string | boolean;
    cover: string | boolean;
    slide: string | boolean;
    content: string | boolean;
}

