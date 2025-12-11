export const useAuth = () => {
    // const config = useRuntimeConfig();
    const router = useRouter();
    const { public: runtimePublic } = useRuntimeConfig();
    const apiBase = runtimePublic.apiBase;

    // 狀態管理
    const user = useState<any>("user", () => {
        if (import.meta.client) {
            const storedUser = localStorage.getItem("user");
            return storedUser ? JSON.parse(storedUser) : null;
        }
        return null;
    });
    const token = useState<string | null>("token", () => {
        if (import.meta.client) {
            return localStorage.getItem("auth_token");
        }
        return null;
    });
    const isAuthenticated = computed(() => !!token.value && !!user.value);

    /**
     * 登入（使用假資料模擬）
     */
    const login = async (username: string, password: string) => {
        console.log(apiBase);
        
        try {
            const response = await $fetch<{
                success: boolean;
                message: string;
                data?: {
                    user: any;
                    token: string;
                };
            }>(`${apiBase}/admins/login`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                credentials: "include",
                body: {
                    username,
                    password,
                },
            });

            console.log(response);

            if (response.success && response.data) {
                token.value = response.data.token;
                user.value = response.data.user;

                if (import.meta.client) {
                    localStorage.setItem("auth_token", response.data.token);
                    localStorage.setItem("user", JSON.stringify(response.data.user));
                }

                return {
                    success: true,
                    message: response.message,
                };
            }

            return {
                success: false,
                message: response.message || "登入失敗",
            };
        } catch (error: any) {
            console.error("登入錯誤:", error.data);
            // $fetch 在非 2xx 狀態碼時會拋出錯誤，錯誤資料可能在 error.data 或 error.response._data
            // fail() 方法回傳格式: { status, error, messages: { error: "..." } }
            // 其他錯誤回傳格式: { success: false, message: "..." }
            const errorMessage = 
                error?.data?.messages?.error ||  // fail() 方法的回傳格式
                error?.data?.message ||          // 其他錯誤回傳格式
                error?.response?._data?.messages?.error ||  // fail() 方法的備用路徑
                error?.response?._data?.message ||          // 其他錯誤的備用路徑
                error?.message || 
                "登入失敗，請稍後再試";
            return {
                success: false,
                message: errorMessage,
            };
        }
    };

    /**
     * 登出（使用假資料模擬）
     */
    const logout = async () => {
        try {
            await $fetch(`${apiBase}/admins/logout`, {
                method: "POST",
                credentials: "include",
            });
        } catch (error) {
            console.error("登出錯誤:", error);
        } finally {
            // 清除狀態
            token.value = null;
            user.value = null;

            // 清除 localStorage
            if (import.meta.client) {
                localStorage.removeItem("auth_token");
                localStorage.removeItem("user");
            }

            // 導向登入頁
            await router.push("/login");
        }
    };

    /**
     * 取得當前使用者資料（使用假資料模擬）
     */
    const fetchUser = async () => {
        if (!token.value) {
            return false;
        }

        try {
            const response = await $fetch<{
                success: boolean;
                data?: any;
                message?: string;
            }>(`${apiBase}/admins/me`, {
                method: "GET",
                credentials: "include",
            });

            if (response.success && response.data) {
                user.value = response.data;

                if (import.meta.client) {
                    localStorage.setItem("user", JSON.stringify(response.data));
                }

                return true;
            }

            return false;
        } catch (error) {
            console.error("取得使用者資料錯誤:", error);
            await logout();
            return false;
        }
    };

    /**
     * 註冊（使用假資料模擬）
     */
    const register = async (data: {
        username: string;
        email: string;
        password: string;
        full_name?: string;
    }) => {
        try {
            // 模擬 API 延遲
            await new Promise((resolve) => setTimeout(resolve, 500));

            // === 原 API 呼叫（已註解） ===
            // const response = await $fetch<{
            //     status: string;
            //     message: string;
            //     data: {
            //         user: any;
            //         token: string;
            //     };
            // }>(`${apiBase}/auth/register`, {
            //     method: "POST",
            //     body: data,
            // });

            // === 假資料模擬 ===
            const response = {
                status: "success",
                message: "註冊成功",
                data: {
                    user: {
                        id: Math.floor(Math.random() * 1000),
                        username: data.username,
                        email: data.email,
                        full_name: data.full_name || data.username,
                        role: "user",
                        created_at: new Date().toISOString(),
                    },
                    token: "mock_jwt_token_" + Date.now(),
                },
            };

            if (response.status === "success") {
                token.value = response.data.token;
                user.value = response.data.user;

                // 儲存到 localStorage
                if (import.meta.client) {
                    localStorage.setItem("auth_token", response.data.token);
                    localStorage.setItem(
                        "user",
                        JSON.stringify(response.data.user)
                    );
                }

                return {
                    success: true,
                    message: response.message,
                };
            }

            return {
                success: false,
                message: response.message || "註冊失敗",
            };
        } catch (error: any) {
            console.error("註冊錯誤:", error);
            return {
                success: false,
                message: error.data?.message || "註冊失敗，請稍後再試",
            };
        }
    };

    /**
     * 初始化認證狀態
     */
    const initAuth = async () => {
        // 如果有 token 則嘗試刷新使用者狀態
        if (token.value) {
            await fetchUser();
        }
    };

    return {
        user: readonly(user),
        token: readonly(token),
        isAuthenticated,
        login,
        logout,
        register,
        fetchUser,
        initAuth,
    };
};
