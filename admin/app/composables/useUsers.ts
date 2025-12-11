export const useUsers = () => {
    const config = useRuntimeConfig();
    const { token } = useAuth();

    // API 基礎 URL
    const { public: runtimePublic } = useRuntimeConfig();
    const apiBase = runtimePublic.apiBase;
    /**
     * 取得所有使用者列表
     */
    const getUsers = async () => {
        try {
            const response = await $fetch<{
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

            if (response.success) {
                return {
                    success: true,
                    data: response.data ?? [],
                };
            }

            return {
                success: false,
                data: [],
                message: "取得使用者列表失敗",
            };
        } catch (error: any) {
            console.error("取得使用者列表錯誤:", error);
            return {
                success: false,
                data: [],
                message: error.data?.message || "取得使用者列表失敗",
            };
        }
    };

    return {
        getUsers,
    };
};

