import { useDateFormat, useNow } from "@vueuse/core";

export interface ProgressForm {
    case_id: number | null;
    title: string;
    progress_date: string;
    images: string[];
    sort: number;
    status: number;
}

export interface ProgressFormErrors {
    case_id: string | false;
    title: string | false;
    progress_date: string | false;
    images: string | false;
}

export const useAppProgress = () => {
    const { public: runtimePublic } = useRuntimeConfig();
    const apiBase = runtimePublic.apiBase;
    const toast = useToast();
    const data = useState<any[]>("app-progress-data", () => []);
    const loading = useState("app-progress-loading", () => false);
    const submitError = ref("");

    // 取得今天的日期（格式：YYYY-MM-DD）
    const getTodayDate = (): string => {
        return (
            useDateFormat(useNow(), "YYYY-MM-DD", { locales: "zh-TW" })
                ?.value ?? ""
        );
    };

    const form = reactive<ProgressForm>({
        case_id: null,
        title: "",
        progress_date: getTodayDate(),
        images: [],
        sort: 0,
        status: 1
    });

    const errors = reactive<ProgressFormErrors>({
        case_id: false,
        title: false,
        progress_date: false,
        images: false
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

        // 驗證標題
        if (!form.title || form.title.trim() === "") {
            errors.title = "請輸入標題";
        } else if (form.title.trim().length > 255) {
            errors.title = "標題長度不能超過255個字元";
        }

        // 驗證日期格式（如果提供）
        if (form.progress_date && form.progress_date.trim() !== "") {
            const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
            if (!dateRegex.test(form.progress_date)) {
                errors.progress_date = "日期格式不正確，請使用 YYYY-MM-DD 格式";
            }
        }

        return !Object.values(errors).some((v) => v);
    };

    const resetForm = () => {
        form.case_id = null;
        form.title = "";
        form.progress_date = getTodayDate();
        form.images = [];
        form.sort = 0;
        form.status = 1;

        Object.keys(errors).forEach((key) => {
            // @ts-ignore
            errors[key] = false;
        });
        submitError.value = "";
    };

    const loadDataToForm = (data: any) => {
        if (!data) return;
        form.case_id = data.case_id !== null && data.case_id !== undefined ? Number(data.case_id) : null;
        form.title = data.title || "";
        form.progress_date = data.progress_date || getTodayDate();
        form.images = Array.isArray(data.images) ? data.images : [];
        form.sort = data.sort !== undefined ? Number(data.sort) : 0;
        form.status = data.status !== undefined ? Number(data.status) : 1;
    };

    const fetchData = async (caseId?: number | null) => {
        loading.value = true;
        try {
            const queryParams = new URLSearchParams();
            if (caseId !== undefined && caseId !== null) {
                queryParams.append("case_id", String(caseId));
            }
            const queryString = queryParams.toString();

            const res = await $fetch<{
                success: boolean;
                data: any[];
                message?: string;
            }>(
                `${apiBase}/app-progress/get${queryString ? `?${queryString}` : ""}`
            );
            if (res.success) {
                // 後端已確保類型正確，這裡直接使用（如需可在此處再次轉換）
                data.value = res.data || [];
            } else {
                toast.add({ title: res.message, color: "error" });
                data.value = [];
            }
        } catch (error: any) {
            submitError.value =
                error?.message || "取得工程進度失敗，請稍後再試";
            console.error(error);
        } finally {
            loading.value = false;
        }
    };

    const loadProgressData = async (progressId: number) => {
        loading.value = true;
        try {
            const res = await $fetch<{
                success: boolean;
                data: any;
            }>(`/app-progress/get-by-id`, {
                baseURL: apiBase,
                method: "GET",
                params: { id: progressId },
                headers: { "Content-Type": "application/json" },
                credentials: "include"
            });
            if (res.success) {
                loadDataToForm(res.data);
                return res.data;
            } else {
                toast.add({ title: "載入工程進度失敗", color: "error" });
                return null;
            }
        } catch (error: any) {
            console.error("loadProgressData error", error);
            toast.add({
                title: error?.message || "載入工程進度失敗，請稍後再試",
                color: "error"
            });
            return null;
        } finally {
            loading.value = false;
        }
    };

    const addProgress = async () => {
        loading.value = true;
        submitError.value = "";

        if (!validateForm()) {
            loading.value = false;
            submitError.value = useValidateFormErrorMsg(errors);
            toast.add({
                title: submitError.value,
                color: "error"
            });
            return false;
        }
        try {
            const res = await $fetch<{
                success: boolean;
                message: string;
            }>(`${apiBase}/app-progress/add`, {
                method: "POST",
                body: form
            });
            if (res.success) {
                toast.add({
                    title: res.message ?? "新增工程進度成功",
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
                "新增工程進度失敗，請稍後再試";
            submitError.value = msg;
            toast.add({ title: msg, color: "error" });
            console.error("add error", error);
            return false;
        } finally {
            loading.value = false;
        }
    };

    const editProgress = async (id: number | string) => {
        loading.value = true;
        if (!validateForm()) {
            loading.value = false;

            submitError.value = useValidateFormErrorMsg(errors);
            toast.add({
                title: submitError.value,
                color: "error"
            });
            return false;
        }

        try {
            const res = await $fetch<{
                success: boolean;
                message: string;
            }>(`${apiBase}/app-progress/update`, {
                method: "POST",
                body: {
                    id: id,
                    ...form
                }
            });
            if (res.success) {
                toast.add({
                    title: res.message ?? "更新工程進度成功",
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
                "更新工程進度失敗，請稍後再試";
            submitError.value = msg;
            toast.add({ title: msg, color: "error" });
            console.error("edit error", error);
            return false;
        } finally {
            loading.value = false;
        }
    };

    const deleteProgress = async (
        id: number | string,
        options?: {
            onSuccess?: () => void;
        }
    ) => {
        if (!id) return false;
        loading.value = true;
        try {
            const res = await $fetch<{
                success: boolean;
                message: string;
            }>(`${apiBase}/app-progress/delete`, {
                method: "POST",
                body: { id: id }
            });
            if (res.success) {
                toast.add({ title: res.message, color: "success" });
                options?.onSuccess?.();
                return true;
            } else {
                toast.add({ title: res.message, color: "error" });
                return false;
            }
        } catch (error: any) {
            console.error("deleteProgress error", error);
            toast.add({
                title: error?.message || "刪除工程進度失敗，請稍後再試",
                color: "error"
            });
            return false;
        } finally {
            loading.value = false;
        }
    };

    return {
        data,
        loading,
        submitError,
        form,
        errors,
        clearError,
        resetForm,
        loadDataToForm,
        fetchData,
        loadProgressData,
        addProgress,
        editProgress,
        deleteProgress
    };
};

