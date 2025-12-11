import type { Ref } from "vue";
import type { AddLevelForm, AddLevelFormErrors } from "~/types/level";
export const useStructure = () => {
    const { public: runtimePublic } = useRuntimeConfig();
    const apiBase = runtimePublic.apiBase;
    const data = useState<any[]>("structure-data", () => []);
    const toast = useToast();

    const loading = useState("structure-loading", () => false);
    const submitError = ref("");

    const modalOpen = ref(false);

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
                ids: data.value.map((x) => x?.id)
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

    const form = reactive<AddLevelForm>({
        label: "",
        is_show_frontend: true,
        is_show_backend: true,
        status: true,
        parent_id: null
    });

    const errors = reactive<AddLevelFormErrors>({
        label: false,
        is_show_frontend: false,
        is_show_backend: false,
        status: false
    });

    const resetForm = (parentId: AddLevelForm["parent_id"] = null) => {
        form.label = "";
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
        Object.keys(errors).forEach((key) => {
            // @ts-ignore
            errors[key] = false;
        });

        if (!form.label || form.label.trim() === "") {
            errors.label = "請輸入層級名稱";
            return false;
        }

        if (form.label.trim().length > 100) {
            errors.label = "層級名稱長度不能超過100個字元";
            return false;
        }

        return !Object.values(errors).some((v) => v);
    };

    const addLevel = async (
        event?: Event,
        options?: {
            parentId?: AddLevelForm["parent_id"];
            closeModalRef?: Ref<boolean>;
            onSuccess?: () => void;
        }
    ) => {
        if (event) event.preventDefault();
        if (!validateForm()) return false;

        loading.value = true;
        const targetModal = options?.closeModalRef ?? modalOpen;
        const parentId = options?.parentId ?? form.parent_id ?? null;
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
        validateForm,
        resetForm,
        addLevel,
        modalOpen
    };
};
