import type { CaseForm, CaseFormErrors } from "~/types/CaseForm";
import type { CutSectionData } from "~/types/CutSectionField";

export const useAppCase = () => {
    const { public: runtimePublic } = useRuntimeConfig();
    const apiBase = runtimePublic.apiBase;
    const toast = useToast();

    const data = useState<any[]>("app-case-data", () => []);
    const loading = useState("app-case-loading", () => false);
    const submitError = ref("");

    const form = reactive<CaseForm>({
        year: null,
        title: "",
        s_text: "",
        cover: "",
        content: [],
        slide: [],
        ca_type: "",
        ca_area: "",
        ca_square: "",
        ca_phone: "",
        ca_adds: "",
        ca_map: "",
        ca_pop_type: "",
        ca_pop_img: "",
        is_sale: 0,
        is_msg: 0,
        sort: 0,
        status: 1
    });

    const errors = reactive<CaseFormErrors>({
        year: false,
        title: false,
        cover: false,
        slide: false,
        content: false
    });

    const clearError = (field: keyof typeof errors) => {
        errors[field] = false;
    };

    const validateForm = (): boolean => {
        submitError.value = "";
        Object.keys(errors).forEach((key) => {
            // @ts-ignore
            errors[key] = false;
        });

        if (!form.title || form.title.trim() === "") {
            errors.title = "請輸入標題";
        }

        if (!form.cover || (form.cover.trim() === "" && !form.cover.startsWith("temp_"))) {
            errors.cover = "請上傳封面圖";
        }

        if (!form.slide || form.slide.length === 0) {
            errors.slide = "請上傳輪播圖";
        }

        // 內容區塊 JSON（可選，但若存在需為有效陣列）
        if (form.content && !Array.isArray(form.content)) {
            errors.content = "內容格式不正確";
        }

        return !Object.values(errors).some((v) => v);
    };

    const resetForm = () => {
        form.year = null;
        form.title = "";
        form.s_text = "";
        form.cover = "";
        form.content = [];
        form.slide = [];
        form.ca_type = "";
        form.ca_area = "";
        form.ca_square = "";
        form.ca_phone = "";
        form.ca_adds = "";
        form.ca_map = "";
        form.ca_pop_type = "";
        form.ca_pop_img = "";
        form.is_sale = 0;
        form.is_msg = 0;
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
        form.year = data.year !== undefined && data.year !== null ? Number(data.year) : null;
        form.title = data.title || "";
        form.s_text = data.s_text || "";
        form.cover = data.cover || "";
        form.content = Array.isArray(data.content) ? data.content : [];
        form.slide = Array.isArray(data.slide) ? data.slide : [];
        form.ca_type = data.ca_type || "";
        form.ca_area = data.ca_area || "";
        form.ca_square = data.ca_square || "";
        form.ca_phone = data.ca_phone || "";
        form.ca_adds = data.ca_adds || "";
        form.ca_map = data.ca_map || "";
        form.ca_pop_type = data.ca_pop_type || "";
        form.ca_pop_img = data.ca_pop_img || "";
        form.is_sale = data.is_sale !== undefined ? Number(data.is_sale) : 0;
        form.is_msg = data.is_msg !== undefined ? Number(data.is_msg) : 0;
        form.sort = data.sort !== undefined ? Number(data.sort) : 0;
        form.status = data.status !== undefined ? Number(data.status) : 1;
    };

    const fetchData = async (structureId?: number | null) => {
        loading.value = true;
        try {
            const queryParams = new URLSearchParams();
            if (structureId !== undefined && structureId !== null) {
                queryParams.append("structure_id", String(structureId));
            }
            const queryString = queryParams.toString();
            const res = await $fetch<{
                success: boolean;
                data: any[];
                message?: string;
            }>(`${apiBase}/app-case/get${queryString ? `?${queryString}` : ""}`);
            if (res.success) {
                data.value = res.data || [];
            } else {
                toast.add({ title: res.message, color: "error" });
                data.value = [];
            }
        } catch (error: any) {
            submitError.value = error?.message || "取得建案資料失敗，請稍後再試";
            console.error(error);
        } finally {
            loading.value = false;
        }
    };

    const addCase = async (structureId?: number | null) => {
        loading.value = true;
        submitError.value = "";

        if (!validateForm()) {
            loading.value = false;
            return false;
        }
        try {
            const body: any = {
                ...form,
                content: form.content as CutSectionData[]
            };
            if (structureId !== undefined && structureId !== null) {
                body.structure_id = structureId;
            }
            const res = await $fetch<{
                success: boolean;
                message: string;
            }>(`${apiBase}/app-case/add`, {
                method: "POST",
                body
            });
            if (res.success) {
                toast.add({
                    title: res.message ?? "新增建案成功",
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
                "新增建案失敗，請稍後再試";
            submitError.value = msg;
            toast.add({ title: msg, color: "error" });
            console.error("add case error", error);
            return false;
        } finally {
            loading.value = false;
        }
    };

    const editCase = async (id: number | string) => {
        if (!validateForm()) return false;
        loading.value = true;
        try {
            const res = await $fetch<{
                success: boolean;
                message: string;
            }>(`${apiBase}/app-case/update`, {
                method: "POST",
                body: {
                    id,
                    ...form,
                    content: form.content as CutSectionData[]
                }
            });
            if (res.success) {
                toast.add({
                    title: res.message ?? "更新建案成功",
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
                "更新建案失敗，請稍後再試";
            submitError.value = msg;
            toast.add({ title: msg, color: "error" });
            console.error("edit case error", error);
            return false;
        } finally {
            loading.value = false;
        }
    };

    const loadCaseData = async (caseId: number) => {
        loading.value = true;
        try {
            const res = await $fetch<{
                success: boolean;
                data: any;
            }>(`/app-case/get-by-id`, {
                baseURL: apiBase,
                method: "GET",
                params: { id: caseId },
                headers: { "Content-Type": "application/json" },
                credentials: "include"
            });
            if (res.success) {
                const parsedContent =
                    res.data?.content && typeof res.data.content === "string"
                        ? JSON.parse(res.data.content)
                        : res.data?.content;
                const parsedSlide =
                    res.data?.slide && typeof res.data.slide === "string"
                        ? JSON.parse(res.data.slide)
                        : res.data?.slide;
                loadDataToForm({
                    ...res.data,
                    content: parsedContent ?? [],
                    slide: Array.isArray(parsedSlide) ? parsedSlide : []
                });
                return res.data;
            } else {
                toast.add({ title: "載入建案失敗", color: "error" });
                return null;
            }
        } catch (error: any) {
            console.error("loadCaseData error", error);
            toast.add({
                title: error?.message || "載入建案失敗，請稍後再試",
                color: "error"
            });
            return null;
        } finally {
            loading.value = false;
        }
    };

    const deleteCase = async (id: number | string, options?: {
        onSuccess?: () => void;
    }) => {
        if (!id) return false;
        loading.value = true;
        try {
            const res = await $fetch<{
                success: boolean;
                message: string;
            }>(`${apiBase}/app-case/delete`, {
                method: "POST",
                body: { id }
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
            console.error("deleteCase error", error);
            toast.add({
                title: error?.message || "刪除建案失敗，請稍後再試",
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
        addCase,
        editCase,
        deleteCase,
        loadCaseData
    };
};

