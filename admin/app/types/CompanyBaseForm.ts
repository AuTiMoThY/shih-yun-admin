
export interface CompanyBaseForm {
    name: string | null;
    copyright: string | null;
    phone: string | null;
    fax: string | null;
    email: string | null;
    case_email: string | null;
    zipcode: string | null;
    city: string | null;
    district: string | null;
    address: string | null;
    fb_url: string | null;
    yt_url: string | null;
    line_url: string | null;
    keywords: string | null;
    description: string | null;
    head_code: string | null;
    body_code: string | null;
}

export interface CompanyBaseFormErrors {
    name: string | false;
    copyright: string | false;
    phone: string | false;
    fax: string | false;
    email: string | false;
    case_email: string | false;
    zipcode: string | false;
    city: string | false;
    district: string | false;
    address: string | false;
    fb_url: string | false;
    yt_url: string | false;
    line_url: string | false;
    keywords: string | false;
    description: string | false;
    head_code: string | false;
    body_code: string | false;
}