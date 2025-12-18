import type { ContactForm } from "~/types";

export const useContact = () => {
    const { public: runtimePublic } = useRuntimeConfig();
    const apiBase = runtimePublic.apiBase;
    const toast = useToast();
    const data = useState<any[]>("contact-data", () => []);
    const loading = useState("contact-loading", () => false);

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
                data.value = response.data || [];
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

    return {
        data,
        loading,
        fetchData,
        submitContact,
        updateStatus,
    };
};