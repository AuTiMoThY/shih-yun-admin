export default defineNuxtRouteMiddleware(async (to, _from) => {
    console.log("[auth middleware] Starting for path:", to.path);
    const { isAuthenticated, initAuth } = useAuth();

    try {
        // 初始化認證狀態（帶超時保護）
        console.log("[auth middleware] Calling initAuth...");
        const initPromise = initAuth();
        const timeoutPromise = new Promise((_, reject) => {
            setTimeout(() => reject(new Error("initAuth timeout")), 3000);
        });
        
        await Promise.race([initPromise, timeoutPromise]);
        console.log("[auth middleware] initAuth completed");
    } catch (error) {
        console.warn("[auth middleware] initAuth error or timeout:", error);
        // 繼續執行，不阻塞導航
    }

    console.log("[auth middleware] isAuthenticated:", isAuthenticated.value);

    // 如果未登入且不是前往登入頁，則導向登入頁
    if (!isAuthenticated.value && to.path !== "/login") {
        console.log("[auth middleware] Redirecting to login");
        return navigateTo("/login");
    }

    // 如果已登入且前往登入頁，則導向首頁
    if (isAuthenticated.value && to.path === "/login") {
        console.log("[auth middleware] Redirecting to home");
        return navigateTo("/");
    }

    console.log("[auth middleware] Completed successfully");
});
