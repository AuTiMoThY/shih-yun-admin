export default defineNuxtRouteMiddleware(async (to, _from) => {
    const { isAuthenticated, initAuth } = useAuth();

    // 初始化認證狀態
    await initAuth();

    // 如果未登入且不是前往登入頁，則導向登入頁
    if (!isAuthenticated.value && to.path !== "/login") {
        return navigateTo("/login");
    }

    // 如果已登入且前往登入頁，則導向首頁
    if (isAuthenticated.value && to.path === "/login") {
        return navigateTo("/");
    }
});
