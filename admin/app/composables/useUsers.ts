import type { AdminForm, AdminFormErrors } from "~/types";

export const useUsers = () => {
    const config = useRuntimeConfig();
    const { token } = useAuth();
    const toast = useToast();

    // API 基礎 URL
    const { public: runtimePublic } = useRuntimeConfig();
    const apiBase = runtimePublic.apiBase;

    const router = useRouter();
    const submitError = ref("");
    const loading = ref(false);
    const data = ref<any[]>([]);

    const form = reactive<AdminForm>({
        permission_name: "admin",
        status: true,
        username: "",
        password: "",
        password_confirmation: "",
        name: "",
        phone: "",
        address: "",
        role_ids: [],
        permission_ids: []
    });
    const errors = reactive<AdminFormErrors>({
        permission_name: false,
        status: false,
        username: false,
        password: false,
        password_confirmation: false,
        name: false,
        phone: false,
        address: false,
        role_ids: false,
        permission_ids: false
    });

    const validateForm = (): boolean => {
        submitError.value = "";
        Object.keys(errors).forEach((key) => {
            // @ts-ignore
            errors[key] = false;
        });

        // permission_name 改為可選（保留以向後兼容）
        // if (!form.permission_name) {
        //     errors.permission_name = "請選擇權限名稱";
        // }

        if (!form.username || form.username.trim() === "") {
            errors.username = "請輸入帳號";
        } else if (form.username.trim().length < 3) {
            errors.username = "帳號長度至少需要3個字元";
        }

        if (!form.password || form.password === "") {
            errors.password = "請輸入密碼";
        }

        if (!form.password_confirmation || form.password_confirmation === "") {
            errors.password_confirmation = "請再次輸入密碼";
        } else if (form.password !== form.password_confirmation) {
            errors.password_confirmation = "兩次輸入的密碼不一致";
        }

        if (!form.name || form.name.trim() === "") {
            errors.name = "請輸入姓名";
        }

        if (form.phone && form.phone.trim() !== "") {
            const phoneRegex = /^[0-9-+()]+$/;
            if (!phoneRegex.test(form.phone.trim())) {
                errors.phone = "電話格式不正確";
            }
        }

        return !Object.values(errors).some((v) => v);
    };

    const clearError = (field: keyof typeof errors) => {
        errors[field] = false;
    };

    /**
     * 取得所有使用者列表
     */
    const fetchData = async () => {
        loading.value = true;

        try {
            const res = await $fetch<{
                success: boolean;
                data: any[];
                message?: string;
            }>(`${apiBase}/admins/get`, {
                method: "GET",
                headers: {
                    // Authorization: `Bearer ${token.value}`,
                    "Content-Type": "application/json"
                },
                credentials: "include"
            });

            if (res?.success) {
                data.value = res.data;
            } else {
                console.error(res.message);
                toast.add({
                    title: res.message || "取得使用者列表失敗",
                    color: "error"
                });
            }
        } catch (error: any) {
            console.error("取得使用者列表錯誤:", error);
            toast.add({
                title: error.data?.message || "取得使用者列表失敗",
                color: "error"
            });
        }
        loading.value = false;
    };

    const addAdmin = async (admin: any) => {
        loading.value = true;
        submitError.value = "";

        if (!validateForm()) {
            loading.value = false;
            return;
        }
        try {
            const response = await $fetch<{
                success: boolean;
                message: string;
            }>("/admins/add", {
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
                router.push("/system/admins");
            } else {
                toast.add({
                    title: response.message,
                    color: "error"
                });
                submitError.value = response.message;
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
                "新增管理員失敗，請稍後再試";

            submitError.value = msg;
            toast.add({ title: msg, color: "error" });
            console.error("addAdmin error", error);
        } finally {
            loading.value = false;
        }
    };

    const editAdmin = async (admin: any, adminId: number) => {
        loading.value = true;
        submitError.value = "";

        try {
            const body: any = {
                id: adminId,
                ...admin
            };

            // 只有當密碼有填寫時才加入
            if (!admin.password) {
                delete body.password;
                delete body.password_confirmation;
            }

            const response = await $fetch<{
                success: boolean;
                message: string;
            }>(`${apiBase}/admins/update`, {
                baseURL: apiBase,
                method: "POST",
                body,
                headers: { "Content-Type": "application/json" },
                credentials: "include"
            });

            if (response.success) {
                toast.add({
                    title: response.message,
                    color: "success"
                });
                router.push("/system/admins");
            } else {
                toast.add({
                    title: response.message,
                    color: "error"
                });
                submitError.value = response.message;
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
                "更新管理員失敗，請稍後再試";

            submitError.value = msg;
            toast.add({ title: msg, color: "error" });
            console.error("updateAdmin error", error);
        } finally {
            loading.value = false;
        }
    };

    const deleteAdmin = async (admin: any) => {
        console.log(admin);
        try {
            const response = await $fetch<{
                success: boolean;
                message: string;
            }>("/admins/delete", {
                baseURL: apiBase,
                method: "POST",
                body: { id: admin.id },
                headers: { "Content-Type": "application/json" },
                credentials: "include"
            });
            console.log(response);
            if (response.success) {
                toast.add({ title: response.message, color: "success" });
                fetchData();
            } else {
                toast.add({ title: response.message, color: "error" });
            }
        } catch (error: any) {
            console.error("deleteAdmin error", error);
            toast.add({
                title: error.message || "刪除管理員失敗，請稍後再試",
                color: "error"
            });
        }
    };

    const loadAdminData = async (adminId: number) => {
        loading.value = true;
        try {
            const response = await $fetch<{
                success: boolean;
                data: any;
            }>("/admins/get-by-id", {
                baseURL: apiBase,
                method: "GET",
                params: { id: adminId },
                headers: { "Content-Type": "application/json" },
                credentials: "include"
            });
            if (response.success && response.data) {
                data.value = response.data;
                // 將資料載入到 form
                loadDataToForm(response.data);
                return response.data;
            } else {
                toast.add({
                    title: "載入管理員資料失敗",
                    color: "error"
                });
                router.push("/system/admins");
                return null;
            }
        } catch (error: any) {
            console.error("loadAdminData error", error);
            toast.add({
                title: error.message || "載入管理員資料失敗，請稍後再試",
                color: "error"
            });
            router.push("/system/admins");
            return null;
        } finally {
            loading.value = false;
        }
    };

    // 將載入的資料填入表單
    const loadDataToForm = (adminData: any) => {
        form.permission_name = adminData.permission_name || "admin";
        form.status = adminData.status === 1 || adminData.status === "1" ? true : false;
        form.username = adminData.username || "";
        form.password = "";
        form.password_confirmation = "";
        form.name = adminData.name || "";
        form.phone = adminData.phone || "";
        form.address = adminData.address || "";
        form.role_ids = adminData.role_ids || [];
        form.permission_ids = adminData.permission_ids || [];
    };

    // 編輯時的驗證（密碼可選）
    const validateFormForEdit = (): boolean => {
        submitError.value = "";
        Object.keys(errors).forEach((key) => {
            // @ts-ignore
            errors[key] = false;
        });

        if (!form.username || form.username.trim() === "") {
            errors.username = "請輸入帳號";
        } else if (form.username.trim().length < 3) {
            errors.username = "帳號長度至少需要3個字元";
        }

        // 編輯時密碼為可選，但如果填了就要驗證
        if (form.password || form.password_confirmation) {
            if (!form.password || form.password === "") {
                errors.password = "請輸入密碼";
            }

            if (!form.password_confirmation || form.password_confirmation === "") {
                errors.password_confirmation = "請再次輸入密碼";
            } else if (form.password !== form.password_confirmation) {
                errors.password_confirmation = "兩次輸入的密碼不一致";
            }
        }

        if (!form.name || form.name.trim() === "") {
            errors.name = "請輸入姓名";
        }

        if (form.phone && form.phone.trim() !== "") {
            const phoneRegex = /^[0-9-+()]+$/;
            if (!phoneRegex.test(form.phone.trim())) {
                errors.phone = "電話格式不正確";
            }
        }

        return !Object.values(errors).some((v) => v);
    };

    return {
        data,
        loading,
        submitError,
        errors,
        form,
        clearError,
        fetchData,
        addAdmin,
        editAdmin,
        deleteAdmin,
        loadAdminData,
        loadDataToForm,
        validateForm,
        validateFormForEdit
    };
};
