import type { Ref } from "vue";
import type { LevelForm, LevelFormErrors } from "~/types/level";
export const useStructure = () => {
    const modalOpen = ref(false);
    const { public: runtimePublic } = useRuntimeConfig();
    const apiBase = runtimePublic.apiBase;
    const toast = useToast();
    const data = useState<any[]>("structure-data", () => []);

    const loading = useState("structure-loading", () => false);
    const submitError = ref("");

    const form = reactive<LevelForm>({
        label: "",
        module_id: null,
        is_show_frontend: true,
        is_show_backend: true,
        status: true,
        parent_id: null
    });

    const errors = reactive<LevelFormErrors>({
        label: false,
        module_id: false,
        is_show_frontend: false,
        is_show_backend: false,
        status: false
    });

    const fetchData = async () => {
        loading.value = true;
        const res = await $fetch<{
            success: boolean;
            data: any[];
            message?: string;
        }>(`${apiBase}/structure/get?tree=1`, {
            method: "GET"
        });
        if (res?.success) {
            data.value = (res.data || []).filter(Boolean);
            console.log("fetchData success", {
                count: data.value.length,
                ids: data.value.map((x) => x?.id),
                data: data.value
            });
        } else {
            console.error(res.message);
            toast.add({ title: res.message, color: "error" });
        }
        loading.value = false;
    };

    const updateSortOrder = async (list: any[]) => {
        const payload = (list || []).map((item, index) => ({
            id: item?.id,
            sort_order: index
        }));
        console.log("updateSortOrder payload", payload);
        try {
            const res = await $fetch<{
                success: boolean;
                message?: string;
            }>(`${apiBase}/structure/update-sort-order`, {
                method: "POST",
                body: payload
            });
            if (res?.success) {
                toast.add({ title: "排序已更新", color: "success" });
            } else {
                console.error(res.message);
                toast.add({ title: res.message, color: "error" });
            }
        } catch (error: any) {
            console.error(error.message);
            toast.add({ title: error.message, color: "error" });
        }
    };



    const resetForm = (parentId: LevelForm["parent_id"] = null) => {
        form.label = "";
        form.module_id = null;
        form.is_show_frontend = true;
        form.is_show_backend = true;
        form.status = true;
        form.parent_id = parentId ?? null;

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

        // 驗證層級名稱
        if (!form.label || form.label.trim() === "") {
            errors.label = "請輸入層級名稱";
            isValid = false;
        } else if (form.label.trim().length > 100) {
            errors.label = "層級名稱長度不能超過100個字元";
            isValid = false;
        }

        return isValid;
    };

    const loadFormData = (level: any) => {
        if (!level) return;
        form.label = level.label || "";
        form.module_id = level.module_id ?? null;
        form.is_show_frontend = level.is_show_frontend === "1" || level.is_show_frontend === 1 || level.is_show_frontend === true;
        form.is_show_backend = level.is_show_backend === "1" || level.is_show_backend === 1 || level.is_show_backend === true;
        form.status = level.status === "1" || level.status === 1 || level.status === true;
        form.parent_id = level.parent_id ?? null;
    };

    const normalizeModuleId = (val: LevelForm["module_id"]) => {
        if (val === null || val === undefined || val === "") return null;
        // USelect 會回傳值本身或物件，統一取 value/id
        if (typeof val === "object") {
            // @ts-ignore
            return val.value ?? val.id ?? null;
        }
        return val;
    };

    const addLevel = async (
        event?: Event,
        options?: {
            parentId?: LevelForm["parent_id"];
            closeModalRef?: Ref<boolean>;
            onSuccess?: () => void;
        }
    ) => {
        if (event) event.preventDefault();
        if (!validateForm()) return false;

        loading.value = true;
        const targetModal = options?.closeModalRef ?? modalOpen;
        const parentId = options?.parentId ?? form.parent_id ?? null;
        const moduleId = normalizeModuleId(form.module_id);
        try {
            const response = await $fetch<{
                success: boolean;
                message: string;
                data?: { id: number };
            }>("/structure/add", {
                baseURL: apiBase,
                method: "POST",
                body: {
                    ...form,
                    module_id: moduleId,
                    parent_id: parentId
                },
                headers: { "Content-Type": "application/json" },
                credentials: "include"
            });

            if (response.success) {
                toast.add({
                    title: response.message,
                    color: "success"
                });
                resetForm(parentId);
                targetModal.value = false;
                options?.onSuccess?.();
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
                "新增層級失敗，請稍後再試";

            submitError.value = msg;
            toast.add({ title: msg, color: "error" });
            console.error("addLevel error", error);
            return false;
        } finally {
            loading.value = false;
        }
    };

    const deleteLevel = async (
        level: any,
        options?: { onSuccess?: () => void }
    ) => {
        if (!level?.id) return false;
        try {
            const response = await $fetch<{
                success: boolean;
                message: string;
            }>("/structure/delete", {
                baseURL: apiBase,
                method: "POST",
                body: { id: level.id },
                headers: { "Content-Type": "application/json" },
                credentials: "include"
            });

            if (response.success) {
                toast.add({
                    title: response.message ?? "刪除成功",
                    color: "success"
                });
                options?.onSuccess?.();
                return true;
            }

            toast.add({
                title: response.message || "刪除層級失敗，請稍後再試",
                color: "error"
            });
            return false;
        } catch (error: any) {
            const data = error?.data || error?.response?._data;
            const msg =
                (typeof data?.message === "string" && data.message) ||
                (typeof data === "string" ? data : null) ||
                error?.message ||
                "刪除層級失敗，請稍後再試";

            console.error("deleteLevel error", error);
            toast.add({ title: msg, color: "error" });
            return false;
        }
    };

    const updateLevel = async (
        event?: Event,
        options?: {
            levelId: number | string;
            closeModalRef?: Ref<boolean>;
            onSuccess?: () => void;
        }
    ) => {
        if (event) event.preventDefault();
        if (!options?.levelId) {
            toast.add({ title: "缺少層級 ID", color: "error" });
            return false;
        }

        if (!validateForm()) return false;

        loading.value = true;
        const targetModal = options?.closeModalRef ?? modalOpen;
        const moduleId = normalizeModuleId(form.module_id);
        try {
            const response = await $fetch<{
                success: boolean;
                message: string;
            }>("/structure/update", {
                baseURL: apiBase,
                method: "POST",
                body: {
                    id: options.levelId,
                    ...form,
                    module_id: moduleId
                },
                headers: { "Content-Type": "application/json" },
                credentials: "include"
            });

            if (response.success) {
                toast.add({
                    title: response.message,
                    color: "success"
                });
                resetForm();
                targetModal.value = false;
                options?.onSuccess?.();
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
                "更新層級失敗，請稍後再試";

            submitError.value = msg;
            toast.add({ title: msg, color: "error" });
            console.error("updateLevel error", error);
            return false;
        } finally {
            loading.value = false;
        }
    };

    return {
        data,
        loading,
        fetchData,
        updateSortOrder,
        deleteLevel,
        form,
        errors,
        submitError,
        clearError,
        resetForm,
        loadFormData,
        addLevel,
        updateLevel,
        modalOpen
    };
};
