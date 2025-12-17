type NewsForm = {
    title: string;
    content: string;
    cover: string;
    slide: string[];
    show_date: string;
    status: number;
};

type NewsFormErrors = {
    title?: string | boolean;
    content?: string | boolean;
    cover?: string | boolean;
    slide?: string | boolean;
    show_date?: string | boolean;
    status?: string | boolean;
};

import { useDateFormat, useNow } from '@vueuse/core'

export const useAppNews = () => {
    const { public: runtimePublic } = useRuntimeConfig();
    const apiBase = runtimePublic.apiBase;
    const toast = useToast();
    const data = useState<any[]>("app-news-data", () => []);
    const loading = useState("app-news-loading", () => false);
    const submitError = ref("");

    // 取得今天的日期（格式：YYYY-MM-DD）
    const getTodayDate = (): string => {
        return useDateFormat(useNow(), "YYYY-MM-DD", { locales: "zh-TW" })?.value ?? "";
    };

    const form = reactive<NewsForm>({
        title: "",
        content: "",
        cover: "",
        slide: [],
        show_date: getTodayDate(),
        status: 1
    });

    const errors = reactive<NewsFormErrors>({
        title: false,
        content: false,
        cover: false,
        slide: false,
        show_date: false,
        status: false
    });

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

        // 驗證標題
        if (!form.title || form.title.trim() === "") {
            errors.title = "請輸入標題";
            isValid = false;
        } else if (form.title.trim().length > 255) {
            errors.title = "標題長度不能超過255個字元";
            isValid = false;
        }

        // 驗證日期格式（如果提供）
        if (form.show_date && form.show_date.trim() !== "") {
            const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
            if (!dateRegex.test(form.show_date)) {
                errors.show_date = "日期格式不正確，請使用 YYYY-MM-DD 格式";
                isValid = false;
            }
        }

        return isValid;
    };

    const resetForm = () => {
        form.title = "";
        form.content = "";
        form.cover = "";
        form.slide = [];
        form.show_date = getTodayDate();
        form.status = 1;

        Object.keys(errors).forEach((key) => {
            // @ts-ignore
            errors[key] = false;
        });
        submitError.value = "";
    };

    const loadFormData = (data: any) => {
        if (!data) return;
        form.title = data.title || "";
        form.content = data.content || "";
        form.cover = data.cover || "";
        form.slide = Array.isArray(data.slide) ? data.slide : [];
        form.show_date = data.show_date || "";
        form.status = data.status !== undefined ? Number(data.status) : 1;
    };

    const fetchData = async () => {
        loading.value = true;
        try {
            const res = await $fetch<{
                success: boolean;
                data: any[];
                message?: string;
            }>(`${apiBase}/app-news/get`);
            if (res.success) {
                data.value = res.data || [];
            } else {
                toast.add({ title: res.message, color: "error" });
                data.value = [];
            }
        }
        catch (error: any) {
            submitError.value = error?.message || "取得最新消息失敗，請稍後再試";
            console.error(error);
        }
        finally {
            loading.value = false;
        }
    };

    const addNews = async () => {
        if (!validateForm()) return false;

        loading.value = true;

        try {
            const res = await $fetch<{
                success: boolean;
                message: string;
            }>(`${apiBase}/app-news/add`, {
                method: "POST",
                body: form
            });
            if (res.success) {
                resetForm();
                toast.add({
                    title: res.message ?? "新增最新消息成功",
                    color: "success"
                });
                return true;
            } else {
                submitError.value = res?.message;
                toast.add({
                    title: res?.message ?? "新增失敗",
                    color: "error"
                });
                return false;
            }
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
                "新增最新消息失敗，請稍後再試";
            submitError.value = msg;
            toast.add({ title: msg, color: "error" });
            console.error("add error", error);
            return false;
        } finally {
            loading.value = false;
        }
    };

    const editNews = async (id: number | string) => {
        if (!validateForm()) return false;

        loading.value = true;

        try {
            const res = await $fetch<{
                success: boolean;
                message: string;
            }>(`${apiBase}/app-news/update`, {
                method: "POST",
                body: {
                    id: id,
                    ...form
                }
            });
            if (res.success) {
                toast.add({
                    title: res.message ?? "更新最新消息成功",
                    color: "success"
                });
                return true;
            } else {
                submitError.value = res?.message;
                toast.add({
                    title: res?.message ?? "更新失敗",
                    color: "error"
                });
                return false;
            }
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
                "更新最新消息失敗，請稍後再試";
            submitError.value = msg;
            toast.add({ title: msg, color: "error" });
            console.error("edit error", error);
            return false;
        } finally {
            loading.value = false;
        }
    };

    return {
        data,
        loading,
        submitError,
        fetchData,
        form,
        errors,
        clearError,
        resetForm,
        loadFormData,
        addNews,
        editNews
    };
};