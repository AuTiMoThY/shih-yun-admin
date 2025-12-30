export default defineNuxtRouteMiddleware(async (to, _from) => {
    const { hasPermission, isSuperAdmin } = usePermission();
    const toast = useToast();

    // 超級管理員有所有權限，直接通過
    if (isSuperAdmin()) {
        return;
    }

    // 從 route meta 中取得需要的權限（固定路由）
    const requiredPermission = to.meta.permission as string | undefined;

    // 如果指定了權限，直接檢查
    if (requiredPermission) {
        if (!hasPermission(requiredPermission)) {
            toast.add({
                title: "您沒有權限存取此頁面",
                color: "error"
            });
            return navigateTo("/");
        }
        return; // 有權限，通過
    }

    // 對於動態路由（如 [...slug]），從路徑解析權限
    // 注意：需要等待結構資料載入後才能解析
    if (to.path.startsWith("/") && to.path !== "/" && to.path !== "/login") {
        // 使用 nextTick 確保 composable 已初始化
        await nextTick();

        try {
            const { resolvePath } = useStructureResolver();
            const pathInfo = resolvePath(to.path);

            // 如果有結構資訊且有 url，檢查權限
            if (pathInfo.structure?.url) {
                // 構建權限名稱：{url}.view
                const permissionName = `${pathInfo.structure.url}.view`;

                if (!hasPermission(permissionName)) {
                    toast.add({
                        title: "您沒有權限存取此頁面",
                        color: "error"
                    });
                    return navigateTo("/");
                }
            }
        } catch (error) {
            console.warn(
                "[permission middleware] Error checking permission:",
                error
            );
            // 發生錯誤時，為了不阻塞導航，允許通過
            // 這樣可以避免結構資料未載入時造成的問題
        }
    }
});
