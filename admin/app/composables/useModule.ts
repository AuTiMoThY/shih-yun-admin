import type { ModuleForm, ModuleFormErrors } from "~/types/module";
export const useModule = () => {
    const modalOpen = ref(false);
    const { public: runtimePublic } = useRuntimeConfig();
    const apiBase = runtimePublic.apiBase;
    const toast = useToast();
    const data = useState<any[]>("module-data", () => []);

    const loading = useState("module-loading", () => false);
    const submitError = ref("");

    const form = reactive<ModuleForm>({
        label: "",
        name: ""
    });
    const errors = reactive<ModuleFormErrors>({
        label: false,
        name: false
    });

    const clearError = (field: keyof typeof errors) => {
        errors[field] = false;
    };

    const fetchData = async () => {
        loading.value = true;
        try {
            const res = await $fetch<{
                success: boolean;
                data: any[];
            }>(`${apiBase}/module/get`, {
                method: "GET"
            });
            if (res.success) {
                data.value = (res.data || []).map((item: any) => ({
                    id: item?.id,
                    label: item?.label,
                    value: item?.id,
                    name: item?.name
                }));
            } else {
                toast.add({ title: "取得模組資料失敗", color: "error" });
                data.value = [];
            }
        } catch (error: any) {
            const msg = error?.data?.message || error?.message || "取得模組資料失敗，請稍後再試";
            toast.add({ title: msg, color: "error" });
            console.error("fetchData error", error);
            data.value = [];
        } finally {
            loading.value = false;
        }
    };

    const validateForm = (): boolean => {
        submitError.value = "";
        // 先清除所有錯誤
        Object.keys(errors).forEach((key) => {
            // @ts-ignore
            errors[key] = false;
        });

        let isValid = true;

        // 驗證模組名稱
        if (!form.label || form.label.trim() === "") {
            errors.label = "請輸入模組名稱";
            isValid = false;
        } else if (form.label.trim().length > 100) {
            errors.label = "模組名稱長度不能超過100個字元";
            isValid = false;
        }

        if (!form.name || form.name.trim() === "") {
            errors.name = "請輸入模組代碼";
            isValid = false;
        } else if (form.name.trim().length > 100) {
            errors.name = "模組代碼長度不能超過100個字元";
            isValid = false;
        }

        if (form.name && form.name.trim() !== "") {
            const namePattern = /^[a-zA-Z0-9_-]+$/;
            if (!namePattern.test(form.name.trim())) {
                errors.name = "模組代碼只能包含英文字母、數字、底線和連字號";
                isValid = false;
            }
        }

        return isValid;
    };

    const resetForm = () => {
        form.label = "";
        form.name = "";

        Object.keys(errors).forEach((key) => {
            // @ts-ignore
            errors[key] = false;
        });
        submitError.value = "";
    };

    const loadFormData = (data: any) => {
        if (!data) return;
        form.label = data.label || "";
        form.name = data.name || "";
    }

    const addModule = async (
        options?: {
            closeModalRef?: Ref<boolean>;
            onSuccess?: () => void;
        }
    ) => {
        if (!validateForm()) return false;
        
        loading.value = true;

        const targetModal = options?.closeModalRef ?? modalOpen;

        console.log("add", form);
        
        try {
            const res = await $fetch<{
                success: boolean;
                message: string;
            }>(`${apiBase}/module/add`, {
                method: "POST",
                body: form
            });
            if (res.success) {
                resetForm();
                targetModal.value = false;
                options?.onSuccess?.();
            } else {
                submitError.value = res?.message;
            }
        } catch (error: any) {
            const data = error?.data || error?.response?._data;
            const fieldErrors =
                data?.errors && typeof data.errors === "object"
                    ? data.errors
                    : null;
            if (fieldErrors) {
                Object.entries(fieldErrors).forEach(([key, val]) => {
                    const msg = Array.isArray(val) ? val.join(", ") : String(val);
                    // @ts-ignore
                    errors[key] = msg;
                });
            }
            const msg =
                (typeof data?.message === "string" && data.message) ||
                (typeof data === "string" ? data : null) ||
                error?.message ||
                "新增模組失敗，請稍後再試";
            submitError.value = msg;
            toast.add({ title: msg, color: "error" });
            console.error("add error", error);
            return false;
        } finally {
            loading.value = false;
        }
    }

    const editModule = async (
        options?: {
            closeModalRef?: Ref<boolean>;
            onSuccess?: () => void;
            id?: number | string;
        }
    ) => {
        if (!validateForm()) return false;
        
        loading.value = true;

        const targetModal = options?.closeModalRef ?? modalOpen;

        console.log("edit", form);
        
        try {
            const res = await $fetch<{
                success: boolean;
                message: string;
            }>(`${apiBase}/module/update`, {
                method: "POST",
                body: {
                    id: options?.id,
                    ...form
                }
            });
            if (res.success) {
                resetForm();
                targetModal.value = false;
                options?.onSuccess?.();
            } else {
                submitError.value = res?.message;
            }
        } catch (error: any) {
            const data = error?.data || error?.response?._data;
            const fieldErrors =
                data?.errors && typeof data.errors === "object"
                    ? data.errors
                    : null;
            if (fieldErrors) {
                Object.entries(fieldErrors).forEach(([key, val]) => {
                    const msg = Array.isArray(val) ? val.join(", ") : String(val);
                    // @ts-ignore
                    errors[key] = msg;
                });
            }
            const msg =
                (typeof data?.message === "string" && data.message) ||
                (typeof data === "string" ? data : null) ||
                error?.message ||
                "修改模組失敗，請稍後再試";
            submitError.value = msg;
            toast.add({ title: msg, color: "error" });
            console.error("edit error", error);
            return false;
        } finally {
            loading.value = false;
        }
    }
    const deleteModule = async (
        options?: {
            id?: number | string;
            onSuccess?: () => void;
        }
    ) => {
        if (!options?.id) return false;
        loading.value = true;
        try {
            const res = await $fetch<{
                success: boolean;
                message: string;
            }>(`${apiBase}/module/delete`, {
                method: "POST",
                body: { id: options.id }
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
                    const msg = Array.isArray(val) ? val.join(", ") : String(val);
                    // @ts-ignore
                    errors[key] = msg;
                });
            }
            const msg =
                (typeof data?.message === "string" && data.message) ||
                (typeof data === "string" ? data : null) ||
                error?.message ||
                "刪除模組失敗，請稍後再試";
            submitError.value = msg;
            toast.add({ title: msg, color: "error" });
            console.error("delete error", error);
            return false;
        } finally {
            loading.value = false;
        }
    
    }
    return {
        form,
        errors,
        data,
        loading,
        submitError,
        clearError,
        fetchData,
        resetForm,
        loadFormData,
        addModule,
        editModule,
        deleteModule
    }
}