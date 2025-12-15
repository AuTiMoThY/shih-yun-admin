import type { Ref } from "vue";
import type { CompanyBaseForm, CompanyBaseFormErrors } from "~/types/CompanyBaseFormts";

export const useCompanyBase = () => {
    const { public: runtimePublic } = useRuntimeConfig();
    const apiBase = runtimePublic.apiBase;
    const toast = useToast();

    const data = useState<any | null>("company-base-data", () => null);
    const loading = useState("company-base-loading", () => false);
    const submitError = ref("");

    const form = reactive<CompanyBaseForm>({
        name: null,
        copyright: null,
        phone: null,
        fax: null,
        email: null,
        case_email: null,
        zipcode: null,
        city: null,
        district: null,
        address: null,
        fb_url: null,
        yt_url: null,
        line_url: null,
        keywords: null,
        description: null,
        head_code: null,
        body_code: null,
    });

    const errors = reactive<CompanyBaseFormErrors>({
        name: false,
        copyright: false,
        phone: false,
        fax: false,
        email: false,
        case_email: false,
        zipcode: false,
        city: false,
        district: false,
        address: false,
        fb_url: false,
        yt_url: false,
        line_url: false,
        keywords: false,
        description: false,
        head_code: false,
        body_code: false,
    });

    const fetchData = async () => {
        loading.value = true;
        try {
            const res = await $fetch<{
                success: boolean;
                data: any | null;
                message?: string;
            }>(`${apiBase}/company-base/get`, {
                method: "GET",
                credentials: "include"
            });
            if (res?.success) {
                data.value = res.data;
                if (res.data) {
                    loadFormData(res.data);
                }
            } else {
                console.error(res.message);
                toast.add({ title: res.message || "取得資料失敗", color: "error" });
            }
        } catch (error: any) {
            console.error("fetchData error", error);
            toast.add({ title: error.message || "取得資料失敗，請稍後再試", color: "error" });
        } finally {
            loading.value = false;
        }
    };

    const loadFormData = (companyData: any) => {
        if (!companyData) return;
        form.name = companyData.name ?? null;
        form.copyright = companyData.copyright ?? null;
        form.phone = companyData.phone ?? null;
        form.fax = companyData.fax ?? null;
        form.email = companyData.email ?? null;
        form.case_email = companyData.case_email ?? null;
        form.zipcode = companyData.zipcode ?? null;
        form.city = companyData.city ?? null;
        form.district = companyData.district ?? null;
        form.address = companyData.address ?? null;
        form.fb_url = companyData.fb_url ?? null;
        form.yt_url = companyData.yt_url ?? null;
        form.line_url = companyData.line_url ?? null;
        form.keywords = companyData.keywords ?? null;
        form.description = companyData.description ?? null;
        form.head_code = companyData.head_code ?? null;
        form.body_code = companyData.body_code ?? null;
    };

    const resetForm = () => {
        form.name = null;
        form.copyright = null;
        form.phone = null;
        form.fax = null;
        form.email = null;
        form.case_email = null;
        form.zipcode = null;
        form.city = null;
        form.district = null;
        form.address = null;
        form.fb_url = null;
        form.yt_url = null;
        form.line_url = null;
        form.keywords = null;
        form.description = null;
        form.head_code = null;
        form.body_code = null;

        Object.keys(errors).forEach((key) => {
            // @ts-ignore
            errors[key] = false;
        });
        submitError.value = "";
    };

    const clearError = (field: keyof typeof errors) => {
        errors[field] = false;
    };

    const validateForm = (): boolean => {
        submitError.value = "";
        // 先清除所有錯誤
        Object.keys(errors).forEach((key) => {
            // @ts-ignore
            errors[key] = false;
        });

        let isValid = true;

        // 驗證 email 格式
        if (form.email && form.email.trim() !== "") {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(form.email.trim())) {
                errors.email = "電子郵件格式不正確";
                isValid = false;
            }
        }

        // 驗證 case_email 格式
        if (form.case_email && form.case_email.trim() !== "") {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(form.case_email.trim())) {
                errors.case_email = "預約賞屋信箱格式不正確";
                isValid = false;
            }
        }

        // 驗證 URL 格式
        if (form.fb_url && form.fb_url.trim() !== "") {
            try {
                new URL(form.fb_url.trim());
            } catch {
                errors.fb_url = "Facebook URL 格式不正確";
                isValid = false;
            }
        }

        if (form.yt_url && form.yt_url.trim() !== "") {
            try {
                new URL(form.yt_url.trim());
            } catch {
                errors.yt_url = "YouTube URL 格式不正確";
                isValid = false;
            }
        }

        if (form.line_url && form.line_url.trim() !== "") {
            try {
                new URL(form.line_url.trim());
            } catch {
                errors.line_url = "LINE URL 格式不正確";
                isValid = false;
            }
        }

        return isValid;
    };

    const save = async (event?: Event) => {
        if (event) event.preventDefault();
        if (!validateForm()) return false;

        loading.value = true;
        try {
            const response = await $fetch<{
                success: boolean;
                message: string;
            }>("/company-base/save", {
                baseURL: apiBase,
                method: "POST",
                body: form,
                headers: { "Content-Type": "application/json" },
                credentials: "include"
            });

            if (response.success) {
                toast.add({
                    title: response.message,
                    color: "success"
                });
                await fetchData();
                return true;
            }

            toast.add({
                title: response.message,
                color: "error"
            });
            return false;
        } catch (error: any) {
            const data = error?.data || error?.response?._data;
            const fieldErrors =
                data?.errors && typeof data.errors === "object"
                    ? data.errors
                    : null;

            if (fieldErrors) {
                Object.entries(fieldErrors).forEach(([key, val]) => {
                    const msg = Array.isArray(val)
                        ? val.join(", ")
                        : String(val);
                    // @ts-ignore
                    errors[key] = msg;
                });
            }

            const msg =
                (typeof data?.message === "string" && data.message) ||
                (typeof data === "string" ? data : null) ||
                error?.message ||
                "儲存公司基本資料失敗，請稍後再試";

            submitError.value = msg;
            toast.add({ title: msg, color: "error" });
            console.error("save error", error);
            return false;
        } finally {
            loading.value = false;
        }
    };

    return {
        data,
        loading,
        fetchData,
        form,
        errors,
        submitError,
        clearError,
        resetForm,
        loadFormData,
        save,
    };
};
