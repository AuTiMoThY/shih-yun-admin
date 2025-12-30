# 頁面權限檢查實作方案

本文檔提供多種在每一頁加上權限檢查的方案，若無權限則顯示 toast 並導向首頁。

---

## 方案一：建立 Permission Middleware（推薦）⭐

### 優點
- ✅ 統一管理，易於維護
- ✅ 可以在 `definePageMeta` 中靈活指定權限
- ✅ 適用於所有頁面類型
- ✅ 邏輯清晰，易於測試

### 實作步驟

#### 1. 建立 Permission Middleware

建立檔案：`admin/app/middleware/permission.ts`

```typescript
export default defineNuxtRouteMiddleware(async (to, _from) => {
    const { hasPermission, isSuperAdmin } = usePermission();
    const toast = useToast();

    // 超級管理員有所有權限，直接通過
    if (isSuperAdmin()) {
        return;
    }

    // 從 route meta 中取得需要的權限
    const requiredPermission = to.meta.permission as string | undefined;

    // 如果沒有指定權限，允許通過
    if (!requiredPermission) {
        return;
    }

    // 檢查權限
    if (!hasPermission(requiredPermission)) {
        // 顯示 toast
        toast.add({
            title: '您沒有權限存取此頁面',
            color: 'error',
            timeout: 3000
        });

        // 導向首頁
        return navigateTo('/');
    }
});
```

#### 2. 使用方式

**對於固定路由頁面（如系統管理頁面）：**

```typescript
// admin/app/pages/system/roles/index.vue
definePageMeta({
    middleware: ['auth', 'permission'],
    permission: 'system.roles.view' // 指定需要的權限
});
```

**對於動態路由頁面（如 `[...slug]/index.vue`）：**

由於動態路由需要根據路徑解析權限，可以在 middleware 中處理：

```typescript
// admin/app/middleware/permission.ts（增強版）
export default defineNuxtRouteMiddleware(async (to, _from) => {
    const { hasPermission, isSuperAdmin } = usePermission();
    const toast = useToast();

    // 超級管理員有所有權限，直接通過
    if (isSuperAdmin()) {
        return;
    }

    // 方案 A：從 route meta 中取得需要的權限（固定路由）
    const requiredPermission = to.meta.permission as string | undefined;
    
    // 方案 B：對於動態路由，從路徑解析權限
    if (!requiredPermission && to.path.startsWith('/') && to.path !== '/') {
        // 解析路徑取得結構資訊
        const { resolvePath } = useStructureResolver();
        const pathInfo = resolvePath(to.path);
        
        if (pathInfo.structure?.url) {
            // 構建權限名稱：{url}.view
            const permissionName = `${pathInfo.structure.url}.view`;
            
            if (!hasPermission(permissionName)) {
                toast.add({
                    title: '您沒有權限存取此頁面',
                    color: 'error',
                    timeout: 3000
                });
                return navigateTo('/');
            }
        }
        return; // 如果沒有結構資訊，允許通過（可能是其他類型的頁面）
    }

    // 檢查指定的權限
    if (requiredPermission && !hasPermission(requiredPermission)) {
        toast.add({
            title: '您沒有權限存取此頁面',
            color: 'error',
            timeout: 3000
        });
        return navigateTo('/');
    }
});
```

#### 3. 在頁面中使用

```typescript
// 固定路由頁面
definePageMeta({
    middleware: ['auth', 'permission'],
    permission: 'system.roles.view'
});

// 動態路由頁面（自動從路徑解析）
definePageMeta({
    middleware: ['auth', 'permission']
    // 不需要指定 permission，會自動從路徑解析
});
```

---

## 方案二：在頁面中直接檢查（適合動態路由）

### 優點
- ✅ 適合動態路由頁面
- ✅ 可以根據不同情況做不同處理
- ✅ 不需要修改 middleware

### 缺點
- ❌ 需要在每個頁面中重複寫檢查邏輯
- ❌ 不如 middleware 統一

### 實作步驟

在 `[...slug]/index.vue` 中加入權限檢查：

```typescript
<script setup lang="ts">
definePageMeta({
    middleware: "auth"
});

const route = useRoute();
const router = useRouter();
const { resolvePath } = useStructureResolver();
const { hasPermission, isSuperAdmin } = usePermission();
const toast = useToast();

// 解析路徑並取得結構資訊
const pathInfo = computed(() => {
    return resolvePath(route.path);
});

// 權限檢查
const checkPermission = () => {
    // 超級管理員有所有權限
    if (isSuperAdmin()) {
        return true;
    }

    const { structure } = pathInfo.value;
    
    // 如果沒有結構資訊，允許通過
    if (!structure?.url) {
        return true;
    }

    // 構建權限名稱：{url}.view
    const permissionName = `${structure.url}.view`;

    // 檢查權限
    if (!hasPermission(permissionName)) {
        toast.add({
            title: '您沒有權限存取此頁面',
            color: 'error',
            timeout: 3000
        });
        router.push('/');
        return false;
    }

    return true;
};

// 在 onMounted 或 watch 中檢查權限
onMounted(() => {
    // 等待結構資料載入後檢查權限
    if (pathInfo.value.structure) {
        if (!checkPermission()) {
            return; // 無權限，已導向首頁
        }
    }
});

// 監聽路徑變化，重新檢查權限
watch(
    () => route.path,
    () => {
        nextTick(() => {
            if (pathInfo.value.structure) {
                checkPermission();
            }
        });
    }
);
</script>
```

---

## 方案三：擴展現有的 Auth Middleware

### 優點
- ✅ 統一在一個 middleware 中處理認證和權限
- ✅ 不需要額外的 middleware

### 缺點
- ❌ 邏輯混雜在一起
- ❌ 不夠靈活

### 實作步驟

修改 `admin/app/middleware/auth.ts`：

```typescript
export default defineNuxtRouteMiddleware(async (to, _from) => {
    console.log("[auth middleware] Starting for path:", to.path);
    const { isAuthenticated, initAuth } = useAuth();
    const { hasPermission, isSuperAdmin } = usePermission();
    const toast = useToast();

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

    // 權限檢查（僅在已登入時檢查）
    if (isAuthenticated.value && to.path !== "/login") {
        // 超級管理員有所有權限，直接通過
        if (isSuperAdmin()) {
            console.log("[auth middleware] Permission check passed (super admin)");
            return;
        }

        // 從 route meta 中取得需要的權限（固定路由）
        const requiredPermission = to.meta.permission as string | undefined;
        
        // 對於動態路由，從路徑解析權限
        if (!requiredPermission && to.path.startsWith('/') && to.path !== '/') {
            // 需要使用 composable，但 middleware 中可能無法直接使用
            // 這裡需要異步載入結構資料
            // 建議還是使用方案一
        }

        // 檢查指定的權限
        if (requiredPermission && !hasPermission(requiredPermission)) {
            console.log("[auth middleware] Permission check failed:", requiredPermission);
            toast.add({
                title: '您沒有權限存取此頁面',
                color: 'error',
                timeout: 3000
            });
            return navigateTo('/');
        }
    }

    console.log("[auth middleware] Completed successfully");
});
```

**注意**：在 middleware 中使用 `useStructureResolver` 可能會有時序問題，因為結構資料可能還沒載入。建議使用方案一。

---

## 方案四：使用 Plugin 或 Layout 統一處理

### 優點
- ✅ 在一個地方統一處理
- ✅ 不需要在每個頁面中指定

### 缺點
- ❌ 邏輯較複雜
- ❌ 可能影響性能（每次路由變化都要檢查）

### 實作步驟

在 `admin/app/layouts/default.vue` 中加入：

```typescript
<script setup lang="ts">
// ... 現有代碼 ...

const route = useRoute();
const router = useRouter();
const { hasPermission, isSuperAdmin } = usePermission();
const toast = useToast();
const { resolvePath } = useStructureResolver();

// 檢查當前頁面權限
const checkPagePermission = () => {
    // 超級管理員有所有權限
    if (isSuperAdmin()) {
        return true;
    }

    // 從 route meta 中取得需要的權限（固定路由）
    const requiredPermission = route.meta.permission as string | undefined;
    
    if (requiredPermission) {
        if (!hasPermission(requiredPermission)) {
            toast.add({
                title: '您沒有權限存取此頁面',
                color: 'error',
                timeout: 3000
            });
            router.push('/');
            return false;
        }
        return true;
    }

    // 對於動態路由，從路徑解析權限
    if (route.path.startsWith('/') && route.path !== '/' && route.path !== '/login') {
        const pathInfo = resolvePath(route.path);
        if (pathInfo.structure?.url) {
            const permissionName = `${pathInfo.structure.url}.view`;
            if (!hasPermission(permissionName)) {
                toast.add({
                    title: '您沒有權限存取此頁面',
                    color: 'error',
                    timeout: 3000
                });
                router.push('/');
                return false;
            }
        }
    }

    return true;
};

// 監聽路由變化
watch(
    () => route.path,
    () => {
        nextTick(() => {
            checkPagePermission();
        });
    },
    { immediate: true }
);
</script>
```

---

## 推薦方案：方案一（Permission Middleware）

### 完整實作

#### 1. 建立 Permission Middleware

檔案：`admin/app/middleware/permission.ts`

```typescript
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
                title: '您沒有權限存取此頁面',
                color: 'error',
                timeout: 3000
            });
            return navigateTo('/');
        }
        return; // 有權限，通過
    }

    // 對於動態路由（如 [...slug]），從路徑解析權限
    // 注意：這裡需要使用 composable，但需要等待結構資料載入
    if (to.path.startsWith('/') && to.path !== '/' && to.path !== '/login') {
        // 需要等待結構資料載入後才能解析
        // 這裡使用 nextTick 確保 composable 已初始化
        await nextTick();
        
        try {
            const { resolvePath } = useStructureResolver();
            const pathInfo = resolvePath(to.path);
            
            if (pathInfo.structure?.url) {
                // 構建權限名稱：{url}.view
                const permissionName = `${pathInfo.structure.url}.view`;
                
                if (!hasPermission(permissionName)) {
                    toast.add({
                        title: '您沒有權限存取此頁面',
                        color: 'error',
                        timeout: 3000
                    });
                    return navigateTo('/');
                }
            }
        } catch (error) {
            console.warn('[permission middleware] Error checking permission:', error);
            // 發生錯誤時，為了不阻塞導航，允許通過
        }
    }
});
```

#### 2. 在頁面中使用

**固定路由頁面：**

```typescript
// admin/app/pages/system/roles/index.vue
definePageMeta({
    middleware: ['auth', 'permission'],
    permission: 'system.roles.view'
});
```

**動態路由頁面：**

```typescript
// admin/app/pages/[...slug]/index.vue
definePageMeta({
    middleware: ['auth', 'permission']
    // 不需要指定 permission，會自動從路徑解析
});
```

**編輯頁面（需要 edit 權限）：**

```typescript
// admin/app/pages/[...slug]/edit/[id].vue
definePageMeta({
    middleware: ['auth', 'permission']
    // 需要在頁面中動態檢查 edit 權限
});
```

對於編輯頁面，由於需要根據動態路徑檢查不同的權限（如 `about.edit`、`news.edit`），建議在頁面中檢查：

```typescript
<script setup lang="ts">
definePageMeta({
    middleware: ['auth', 'permission'] // 先檢查 view 權限
});

const route = useRoute();
const router = useRouter();
const { resolvePath } = useStructureResolver();
const { hasPermission, isSuperAdmin } = usePermission();
const toast = useToast();

// 解析路徑取得結構資訊
const currentPath = computed(() => {
    const path = route.path || "";
    const match = path.match(/^(.+)\/edit\/\d+$/);
    return match && match[1] ? match[1] : path;
});

const pathInfo = computed(() => {
    return resolvePath(currentPath.value);
});

// 檢查編輯權限
onMounted(() => {
    if (isSuperAdmin()) {
        return; // 超級管理員有所有權限
    }

    const { structure } = pathInfo.value;
    if (structure?.url) {
        const permissionName = `${structure.url}.edit`;
        if (!hasPermission(permissionName)) {
            toast.add({
                title: '您沒有權限編輯此內容',
                color: 'error',
                timeout: 3000
            });
            router.push('/');
        }
    }
});
</script>
```

---

## 方案比較

| 方案 | 優點 | 缺點 | 適用場景 |
|------|------|------|----------|
| **方案一：Permission Middleware** | 統一管理、靈活、易維護 | 需要建立新檔案 | **推薦**：適合所有頁面 |
| 方案二：頁面中直接檢查 | 簡單直接 | 需要重複寫邏輯 | 適合少數特殊頁面 |
| 方案三：擴展 Auth Middleware | 統一在一個地方 | 邏輯混雜 | 不推薦 |
| 方案四：Layout 中處理 | 統一處理 | 可能影響性能 | 可以考慮 |

---

## 實作建議

1. **優先採用方案一**：建立獨立的 `permission` middleware
2. **固定路由**：在 `definePageMeta` 中指定 `permission`
3. **動態路由**：讓 middleware 自動從路徑解析權限
4. **特殊頁面**（如編輯頁）：在頁面中額外檢查特定權限（如 `edit`）

---

**最後更新：** 2025-01-24
