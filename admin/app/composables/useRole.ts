import type { RoleForm, RoleFormErrors } from "~/types";

export const useRole = () => {
    const { public: runtimePublic } = useRuntimeConfig();
    const apiBase = runtimePublic.apiBase;
    const toast = useToast();
    const data = useState<any[]>("role-data", () => []);
    const loading = useState("role-loading", () => false);
    const submitError = ref("");

    const form = reactive<RoleForm>({
        name: "",
        label: "",
        description: "",
        status: 1,
        permission_ids: [] as number[]
    });

    const errors = reactive<RoleFormErrors>({
        name: false,
        label: false
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
            }>(`${apiBase}/role/get`, {
                method: "GET"
            });
            if (res.success) {
                data.value = res.data || [];
            } else {
                toast.add({ title: "取得角色資料失敗", color: "error" });
                data.value = [];
            }
        } catch (error: any) {
            const msg =
                error?.data?.message ||
                error?.message ||
                "取得角色資料失敗，請稍後再試";
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
            }>(`${apiBase}/role/get-by-id`, {
                method: "GET",
                params: { id }
            });
            if (res.success) {
                return res.data;
            } else {
                toast.add({ title: "取得角色資料失敗", color: "error" });
                return null;
            }
        } catch (error: any) {
            const msg =
                error?.data?.message ||
                error?.message ||
                "取得角色資料失敗，請稍後再試";
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
            errors.name = "請輸入角色名稱";
            isValid = false;
        } else if (form.name.trim().length > 100) {
            errors.name = "角色名稱長度不能超過100個字元";
            isValid = false;
        } else {
            const namePattern = /^[a-zA-Z0-9_-]+$/;
            if (!namePattern.test(form.name.trim())) {
                errors.name = "角色名稱只能包含英文字母、數字、底線和連字號";
                isValid = false;
            }
        }

        if (!form.label || form.label.trim() === "") {
            errors.label = "請輸入角色顯示名稱";
            isValid = false;
        } else if (form.label.trim().length > 255) {
            errors.label = "角色顯示名稱長度不能超過255個字元";
            isValid = false;
        }

        return isValid;
    };

    const resetForm = () => {
        form.name = "";
        form.label = "";
        form.description = "";
        form.status = 1;
        form.permission_ids = [];

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
        // 確保 status 是數字
        form.status = data.status !== undefined ? Number(data.status) : 1;
        // 確保 permission_ids 是數字陣列
        form.permission_ids = Array.isArray(data.permission_ids)
            ? data.permission_ids
                  .map((id: any) => Number(id))
                  .filter((id: number) => !isNaN(id))
            : [];
    };

    const addRole = async (options?: {
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
            }>(`${apiBase}/role/add`, {
                method: "POST",
                body: form
            });
            if (res.success) {
                resetForm();
                if (targetModal) targetModal.value = false;
                options?.onSuccess?.();
                toast.add({
                    title: res.message || "新增角色成功",
                    color: "success"
                });
                return true;
            } else {
                submitError.value = res?.message;
                toast.add({
                    title: res?.message || "新增角色失敗",
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
                data?.model_errors?.name ||
                data?.message ||
                "新增角色失敗，請稍後再試";
            submitError.value = msg;
            toast.add({ title: msg, color: "error" });
            console.error("add error", error);
            return false;
        } finally {
            loading.value = false;
        }
    };

    const editRole = async (options?: {
        closeModalRef?: Ref<boolean>;
        onSuccess?: () => void;
        id?: number | string;
    }) => {
        if (!validateForm()) return false;

        loading.value = true;

        const targetModal = options?.closeModalRef;

        // 確保資料型別正確：將字串轉換為數字
        const requestBody = {
            id: options?.id ? Number(options.id) : undefined,
            name: form.name,
            label: form.label,
            description: form.description || null,
            status: Number(form.status),
            permission_ids: Array.isArray(form.permission_ids)
                ? form.permission_ids
                      .map((id: any) => Number(id))
                      .filter((id: number) => !isNaN(id))
                : []
        };

        // // 記錄請求資訊
        // console.group("🔵 [editRole] 請求資訊");
        // console.log("URL:", `${apiBase}/role/update`);
        // console.log("Method:", "POST");
        // console.log("Request Body:", JSON.stringify(requestBody, null, 2));
        // console.log("Form Data:", JSON.stringify(form, null, 2));
        // console.log("Role ID:", options?.id);
        // console.groupEnd();

        try {
            const res = await $fetch<{
                success: boolean;
                message: string;
            }>(`${apiBase}/role/update`, {
                method: "POST",
                body: requestBody
            });
            if (res.success) {
                resetForm();
                if (targetModal) targetModal.value = false;
                options?.onSuccess?.();

                // 重新載入用戶資料以更新權限
                const { fetchUser } = useAuth();
                await fetchUser();

                toast.add({
                    title: res.message || "更新角色成功",
                    color: "success"
                });
                return true;
            } else {
                submitError.value = res?.message;
                toast.add({
                    title: res?.message || "更新角色失敗",
                    color: "error"
                });
                console.warn("⚠️ [editRole] 回應失敗:", res);
                return false;
            }
        } catch (error: any) {
            const data = error?.data || error?.response?._data;

            // 處理欄位錯誤
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

            // 組合錯誤訊息
            const msg =
                data?.model_errors?.name ||
                data?.message ||
                "更新角色失敗，請稍後再試";
            submitError.value = msg;
            toast.add({
                title: msg,
                color: "error"
            });
            return false;
        } finally {
            loading.value = false;
        }
    };

    const deleteRole = async (
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
            }>(`${apiBase}/role/delete`, {
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
                "刪除角色失敗，請稍後再試";
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
        addRole,
        editRole,
        deleteRole
    };
};
