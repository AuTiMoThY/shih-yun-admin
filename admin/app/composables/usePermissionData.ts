import type { RermissionForm, RermissionFormErrors } from "~/types";
export const usePermissionData = () => {
    const { public: runtimePublic } = useRuntimeConfig();
    const apiBase = runtimePublic.apiBase;
    const toast = useToast();
    const data = useState<any[]>("permission-data", () => []);
    const loading = useState("permission-loading", () => false);
    const submitError = ref("");

    const form = reactive<RermissionForm>({
        name: "",
        label: "",
        description: "",
        module_id: null as number | null,
        category: "",
        action: "",
        status: 1
    });

    const errors = reactive<RermissionFormErrors>({
        name: false,
        label: false
    });

    const clearError = (field: keyof typeof errors) => {
        errors[field] = false;
    };

    const fetchData = async (moduleId?: number) => {
        loading.value = true;
        try {
            const params: any = {};
            if (moduleId) params.module_id = moduleId;

            const res = await $fetch<{
                success: boolean;
                data: any[];
            }>(`${apiBase}/permission/get`, {
                method: "GET",
                params
            });
            if (res.success) {
                data.value = res.data || [];
            } else {
                toast.add({ title: "取得權限資料失敗", color: "error" });
                data.value = [];
            }
        } catch (error: any) {
            const msg =
                error?.data?.message ||
                error?.message ||
                "取得權限資料失敗，請稍後再試";
            toast.add({ title: msg, color: "error" });
            console.error("fetchData error", error);
            data.value = [];
        } finally {
            loading.value = false;
        }
    };

    const fetchById = async (id: number | string) => {
        loading.value = true;
        try {
            const res = await $fetch<{
                success: boolean;
                data: any;
            }>(`${apiBase}/permission/get-by-id`, {
                method: "GET",
                params: { id }
            });
            if (res.success) {
                return res.data;
            } else {
                toast.add({ title: "取得權限資料失敗", color: "error" });
                return null;
            }
        } catch (error: any) {
            const msg =
                error?.data?.message ||
                error?.message ||
                "取得權限資料失敗，請稍後再試";
            toast.add({ title: msg, color: "error" });
            console.error("fetchById error", error);
            return null;
        } finally {
            loading.value = false;
        }
    };

    const validateForm = (): boolean => {
        submitError.value = "";
        Object.keys(errors).forEach((key) => {
            // @ts-ignore
            errors[key] = false;
        });

        let isValid = true;

        if (!form.name || form.name.trim() === "") {
            errors.name = "請輸入權限名稱";
            isValid = false;
        } else if (form.name.trim().length > 255) {
            errors.name = "權限名稱長度不能超過255個字元";
            isValid = false;
        }

        if (!form.label || form.label.trim() === "") {
            errors.label = "請輸入權限顯示名稱";
            isValid = false;
        } else if (form.label.trim().length > 255) {
            errors.label = "權限顯示名稱長度不能超過255個字元";
            isValid = false;
        }

        return isValid;
    };

    const resetForm = () => {
        form.name = "";
        form.label = "";
        form.description = "";
        form.module_id = null;
        form.category = "";
        form.action = "";
        form.status = 1;

        Object.keys(errors).forEach((key) => {
            // @ts-ignore
            errors[key] = false;
        });
        submitError.value = "";
    };

    const loadFormData = (data: any) => {
        if (!data) return;
        form.name = data.name || "";
        form.label = data.label || "";
        form.description = data.description || "";
        form.module_id = data.module_id || null;
        form.category = data.category || "";
        form.action = data.action || "";
        form.status = data.status !== undefined ? data.status : 1;
    };

    const addPermission = async (options?: {
        closeModalRef?: Ref<boolean>;
        onSuccess?: () => void;
    }) => {
        if (!validateForm()) return false;

        loading.value = true;

        const targetModal = options?.closeModalRef;

        try {
            const res = await $fetch<{
                success: boolean;
                message: string;
            }>(`${apiBase}/permission/add`, {
                method: "POST",
                body: form
            });
            if (res.success) {
                resetForm();
                if (targetModal) targetModal.value = false;
                options?.onSuccess?.();
                toast.add({
                    title: res.message || "新增權限成功",
                    color: "success"
                });
                return true;
            } else {
                submitError.value = res?.message;
                toast.add({
                    title: res?.message || "新增權限失敗",
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
                "新增權限失敗，請稍後再試";
            submitError.value = msg;
            toast.add({ title: msg, color: "error" });
            console.error("add error", error);
            return false;
        } finally {
            loading.value = false;
        }
    };

    const editPermission = async (options?: {
        closeModalRef?: Ref<boolean>;
        onSuccess?: () => void;
        id?: number | string;
    }) => {
        if (!validateForm()) return false;

        loading.value = true;

        const targetModal = options?.closeModalRef;

        try {
            const res = await $fetch<{
                success: boolean;
                message: string;
            }>(`${apiBase}/permission/update`, {
                method: "POST",
                body: {
                    id: options?.id,
                    ...form
                }
            });
            if (res.success) {
                console.group("res", res);
                resetForm();
                if (targetModal) targetModal.value = false;
                options?.onSuccess?.();
                toast.add({
                    title: res.message || "更新權限成功",
                    color: "success"
                });
                return true;
            } else {
                submitError.value = res?.message;
                toast.add({
                    title: res?.message || "更新權限失敗",
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
            console.group("editPermission error", error);
            console.log("data", data);
            console.log("error", data.model_errors);
            console.groupEnd();

            const msg =
                data?.model_errors?.name ||
                data?.message ||
                "更新權限失敗，請稍後再試";
            submitError.value = msg;
            toast.add({ title: msg, color: "error" });
            console.error("edit error", error);
            return false;
        } finally {
            loading.value = false;
        }
    };

    const deletePermission = async (
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
            }>(`${apiBase}/permission/delete`, {
                method: "POST",
                body: { id }
            });
            if (res.success) {
                toast.add({
                    title: res.message ?? "刪除成功",
                    color: "success"
                });
                options?.onSuccess?.();
                return true;
            } else {
                submitError.value = res?.message;
                toast.add({
                    title: res?.message ?? "刪除失敗",
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
                "刪除權限失敗，請稍後再試";
            submitError.value = msg;
            toast.add({ title: msg, color: "error" });
            console.error("delete error", error);
            return false;
        } finally {
            loading.value = false;
        }
    };

    return {
        form,
        errors,
        data,
        loading,
        submitError,
        clearError,
        fetchData,
        fetchById,
        resetForm,
        loadFormData,
        addPermission,
        editPermission,
        deletePermission
    };
};
