import type { ContactForm, ContactFormErrors } from "~/types";

export const useContact = () => {
    const { public: runtimePublic } = useRuntimeConfig();
    const apiBase = runtimePublic.apiBase;
    const toast = useToast();
    const data = useState<any[]>("contact-data", () => []);
    const loading = useState("contact-loading", () => false);

    const form = reactive<ContactForm>({
        status: 0,
        reply: "",
    });

    const errors = reactive<ContactFormErrors>({
        status: false,
        reply: false
    });

    const clearError = (field: keyof typeof errors) => {
        errors[field] = false;
    };

    const validateForm = (): boolean => {
        // 清除所有錯誤
        Object.keys(errors).forEach((key) => {
            errors[key as keyof ContactFormErrors] = false;
        });
    };

    /**
     * 前台提交聯絡表單
     */
    const submitContact = async (formData: ContactForm) => {
        loading.value = true;
        try {
            const response = await $fetch<{
                success: boolean;
                message: string;
                data?: { id: number };
                errors?: Record<string, string>;
            }>(`${apiBase}/app-contact/submit`, {
                method: "POST",
                body: formData,
            });

            if (response.success) {
                toast.add({
                    title: "成功",
                    description: response.message || "表單提交成功",
                    color: "success",
                });
                return { success: true, data: response.data };
            } else {
                toast.add({
                    title: "錯誤",
                    description: response.message || "提交失敗",
                    color: "error",
                });
                return { success: false, errors: response.errors };
            }
        } catch (error: any) {
            const errorMessage =
                error.data?.message || error.message || "提交失敗，請稍後再試";
            toast.add({
                title: "錯誤",
                description: errorMessage,
                color: "error",
            });
            return {
                success: false,
                errors: error.data?.errors,
            };
        } finally {
            loading.value = false;
        }
    };

    /**
     * 後台取得聯絡表單列表
     */
    const fetchData = async (options?: { status?: 0 | 1 | 2 }) => {
        loading.value = true;
        try {
            const queryParams = new URLSearchParams();
            if (options?.status !== undefined) {
                queryParams.append("status", String(options.status));
            }

            const response = await $fetch<{
                success: boolean;
                data: any[];
                message?: string;
            }>(`${apiBase}/app-contact/get${queryParams.toString() ? `?${queryParams.toString()}` : ""}`);

            if (response.success) {
                data.value = response.data.map((item: any) => ({
                    ...item,
                    status: Number(item.status)
                }));
                return { success: true, data: response.data };
            } else {
                toast.add({
                    title: "錯誤",
                    description: response.message || "取得資料失敗",
                    color: "error",
                });
                return { success: false };
            }
        } catch (error: any) {
            const errorMessage =
                error.data?.message || error.message || "取得資料失敗，請稍後再試";
            toast.add({
                title: "錯誤",
                description: errorMessage,
                color: "error",
            });
            return { success: false };
        } finally {
            loading.value = false;
        }
    };

    /**
     * 後台更新處理狀態
     */
    const updateStatus = async (id: number, status: 0 | 1 | 2) => {
        loading.value = true;
        try {
            const response = await $fetch<{
                success: boolean;
                message: string;
            }>(`${apiBase}/app-contact/update-status`, {
                method: "POST",
                body: {
                    id,
                    status,
                },
            });

            if (response.success) {
                toast.add({
                    title: "成功",
                    description: response.message || "更新狀態成功",
                    color: "success",
                });
                // 更新本地數據
                const item = data.value.find((item) => item.id === id);
                if (item) {
                    item.status = status;
                }
                return { success: true };
            } else {
                toast.add({
                    title: "錯誤",
                    description: response.message || "更新狀態失敗",
                    color: "error",
                });
                return { success: false };
            }
        } catch (error: any) {
            const errorMessage =
                error.data?.message || error.message || "更新狀態失敗，請稍後再試";
            toast.add({
                title: "錯誤",
                description: errorMessage,
                color: "error",
            });
            return { success: false };
        } finally {
            loading.value = false;
        }
    };

    /**
     * 後台刪除聯絡表單
     */
    const deleteContact = async (id: number, options?: { onSuccess?: () => void }) => {
        if (!id) return false;
        loading.value = true;
        try {
            const response = await $fetch<{
                success: boolean;
                message: string;
            }>(`${apiBase}/app-contact/delete`, {
                method: "POST",
                body: { id },
            });
            if (response.success) {
                toast.add({
                    title: "成功",
                    description: response.message || "刪除成功",
                    color: "success",
                });
                options?.onSuccess?.();
                return { success: true };
            } else {
                toast.add({
                    title: "錯誤",
                    description: response.message || "刪除失敗",
                    color: "error",
                });
                return { success: false };
            }
        } catch (error: any) {
            const errorMessage =
                error.data?.message || error.message || "刪除失敗，請稍後再試";
            toast.add({
                title: "錯誤",
                description: errorMessage,
                color: "error",
            });
            return { success: false };
        } finally {
            loading.value = false;
        }
    };

    /**
     * 後台載入聯絡表單資料
     */
    const loadContactData = async (id: number) => {
        loading.value = true;
        try {
            const response = await $fetch<{
                success: boolean;
                data: any;
                message?: string;
            }>(`${apiBase}/app-contact/get-by-id?id=${id}`);
            if (response.success) {
                return response.data;
            } else {
                toast.add({
                    title: "錯誤",
                    description: response.message || "載入資料失敗",
                    color: "error",
                });
                return null;
            }
        } catch (error: any) {
            const errorMessage =
                error.data?.message || error.message || "載入資料失敗，請稍後再試";
            toast.add({
                title: "錯誤",
                description: errorMessage,
                color: "error",
            });
            return null;
        } finally {
            loading.value = false;
        }
    };

    /**
     * 後台更新回信內容
     */
    const updateReply = async (id: number, reply: string) => {
        loading.value = true;
        try {
            const response = await $fetch<{
                success: boolean;
                message: string;
            }>(`${apiBase}/app-contact/update-reply`, {
                method: "POST",
                body: {
                    id,
                    reply,
                },
            });

            if (response.success) {
                toast.add({
                    title: "成功",
                    description: response.message || "更新回信成功",
                    color: "success",
                });
                return { success: true };
            } else {
                toast.add({
                    title: "錯誤",
                    description: response.message || "更新回信失敗",
                    color: "error",
                });
                return { success: false };
            }
        } catch (error: any) {
            const errorMessage =
                error.data?.message || error.message || "更新回信失敗，請稍後再試";
            toast.add({
                title: "錯誤",
                description: errorMessage,
                color: "error",
            });
            return { success: false };
        } finally {
            loading.value = false;
        }
    };
    return {
        form,
        errors,
        clearError,
        validateForm,
        data,
        loading,
        fetchData,
        submitContact,
        updateStatus,
        deleteContact,
        loadContactData,
        updateReply,
    };
};