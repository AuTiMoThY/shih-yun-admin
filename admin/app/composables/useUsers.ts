export const useUsers = () => {
    const config = useRuntimeConfig();
    const { token } = useAuth();

    // API 基礎 URL
    const apiBase = config.public.apiBase || "http://localhost:8080/api";

    /**
     * 取得所有使用者列表
     */
    const getUsers = async () => {
        try {
            const response = await $fetch<{
                status: string;
                data: any[];
            }>(`${apiBase}/auth/users`, {
                headers: {
                    Authorization: `Bearer ${token.value}`,
                },
            });

            if (response.status === "success") {
                return {
                    success: true,
                    data: response.data,
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

