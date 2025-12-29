# 關於我們-單元查看權限實作說明

## 一、權限資訊

### 1.1 權限設計理念

**重要**：權限設定是基於**單元（structure）**而非模組。原因是不同單元可能使用同一種模組，但需要不同的權限控制。

#### 為什麼基於單元而非模組？

從 `sys_structure.sql` 可以看到：
- **ID 23**: '經營理念' (module_id=1, url='about')
- **ID 24**: '影音專區' (module_id=1, url='media')
- **ID 33**: 'test' (module_id=1, url='about-11')

這三個單元都使用同一個模組（module_id=1），但它們是不同的單元，應該有不同的權限控制。

#### 權限命名規則

權限名稱格式：`{url}.view`

- **格式**：`{單元的url}.view`
- **範例**：
  - 單元 URL='about' → 權限：`about.view`
  - 單元 URL='media' → 權限：`media.view`
  - 單元 URL='contact' → 權限：`contact.view`
  - 單元 URL='project-updates' → 權限：`project-updates.view`

### 1.2 權限資料範例

根據新的設計理念，需要為每個單元建立對應的權限：

```sql
-- 為「經營理念」單元（url='about'）建立權限
INSERT INTO `sys_permissions` (`id`, `name`, `label`, `description`, `module_id`, `category`, `action`, `status`, `created_at`, `updated_at`) VALUES
(11, 'about.view', '經營理念-單元查看', '查看經營理念單元', 1, '', '', 1, '2025-12-28 18:48:21', '2025-12-28 18:48:21');

-- 為「影音專區」單元（url='media'）建立權限
INSERT INTO `sys_permissions` (`id`, `name`, `label`, `description`, `module_id`, `category`, `action`, `status`, `created_at`, `updated_at`) VALUES
(12, 'media.view', '影音專區-單元查看', '查看影音專區單元', 1, '', '', 1, '2025-12-29 00:00:00', '2025-12-29 00:00:00');
```

### 1.3 權限用途

此權限用於控制使用者是否可以查看特定單元的內容。只有擁有該單元權限的使用者才能：

- 查看該單元的頁面
- 查看該單元的區塊和欄位內容
- 訪問該單元相關的 API 端點

---

## 二、前端實作

### 2.1 側邊欄選單權限過濾（自動實作）

系統已經在 `admin/app/layouts/default.vue` 中實作了側邊欄選單的權限過濾功能。**沒有權限的選單項目會自動不顯示**，無需額外設定。

#### 實作原理

1. **權限檢查邏輯**：

   - 在 `mapStructureToMenu` 函數中，會自動檢查每個選單項目的權限
   - 根據選單項目的 `url` 構建權限名稱（格式：`structure.{url}.view`）
   - 例如：單元 URL='about' 會檢查 `structure.about.view` 權限
   - 例如：單元 URL='media' 會檢查 `structure.media.view` 權限

2. **過濾規則**：

   ```typescript
   // 檢查項目是否有權限
   const hasItemPermission = (item: any): boolean => {
     // 超級管理員擁有所有權限
     if (isSuperAdmin()) {
       return true;
     }

     // 如果沒有關聯模組，則不需要檢查權限（例如父層級）
     if (!item?.module_id) {
       return true;
     }

     // 如果沒有 url，表示不是實際的單元（可能是父層級），不需要檢查權限
     if (!item?.url) {
       return true;
     }

     // 根據單元的 url 構建權限名稱（格式：{url}.view）
     // 例如：url='about' → 權限名稱='about.view'
     // 例如：url='media' → 權限名稱='media.view'
     const permissionName = `${item.url}.view`;

     // 檢查是否有權限
     return hasPermission(permissionName);
   };
   ```

3. **自動過濾**：
   - 在 `mapStructureToMenu` 函數中，如果項目沒有權限，會返回 `null`
   - 返回 `null` 的項目會被過濾掉，不會顯示在側邊欄中
   - 如果父層級的所有子項目都被過濾掉，父層級也不會顯示

#### 使用方式

**無需額外設定**，系統會自動根據以下規則過濾選單：

1. **有權限的使用者**：

   - 可以看到所有有權限的選單項目
   - 例如：擁有 `about.view` 權限的使用者可以看到「經營理念」選單
   - 例如：擁有 `media.view` 權限的使用者可以看到「影音專區」選單

2. **無權限的使用者**：

   - 看不到沒有權限的選單項目
   - 例如：沒有 `about.view` 權限的使用者不會看到「經營理念」選單
   - 例如：沒有 `media.view` 權限的使用者不會看到「影音專區」選單

3. **超級管理員**：
   - 可以看到所有選單項目（自動擁有所有權限）

#### 權限命名規則

選單項目的權限名稱遵循以下規則：

- **格式**：`{單元的url}.view`
- **範例**：
  - 單元 URL='about' → 權限：`about.view`
  - 單元 URL='media' → 權限：`media.view`
  - 單元 URL='contact' → 權限：`contact.view`
  - 單元 URL='project-updates' → 權限：`project-updates.view`

#### 注意事項

1. **父層級處理**：

   - 如果選單項目沒有 `module_id`（例如父層級），則不需要檢查權限，預設顯示
   - 父層級會根據子項目的權限自動顯示或隱藏

2. **子層級處理**：

   - 如果父層級的所有子項目都被過濾掉（沒有權限），父層級也不會顯示

3. **單元必須有 URL**：
   - 只有有 `url` 的單元才會進行權限檢查
   - 沒有 `url` 的項目（通常是父層級）不需要檢查權限

4. **權限必須存在**：
   - 確保在 `sys_permissions` 表中已經建立了對應的權限（例如：`about.view`）
   - 權限的 `status` 必須為 `1`（啟用）
   - 每個單元都需要建立對應的權限，即使它們使用同一個模組

5. **權限命名衝突說明**：
   - 單元查看權限格式：`{url}.view`（例如：`about.view`）
   - 模組操作權限格式：`{module}.{category}.{action}`（例如：`about.section.create`）
   - 兩者不會衝突，因為模組操作權限有明確的 category 和 action

### 2.2 頁面層級權限檢查

在動態路由頁面 `admin/app/pages/[...slug]/index.vue` 中，可以添加權限檢查來控制整個頁面的訪問：

```vue
<script setup lang="ts">
// ... 現有程式碼 ...

const { hasPermission, isSuperAdmin } = usePermission();

// 檢查是否有查看權限
const canView = computed(() => {
  // 根據單元的 url 構建權限名稱
  const url = structureInfo.value.url;
  if (!url) {
    return true; // 沒有 url，不需要檢查
  }
  const permissionName = `${url}.view`;
  return isSuperAdmin() || hasPermission(permissionName);
});

// 如果沒有權限，可以導向錯誤頁面或顯示無權限訊息
watchEffect(() => {
  if (structureInfo.value.url && !canView.value) {
    // 可以選擇導向錯誤頁面或顯示無權限訊息
    navigateTo("/unauthorized");
  }
});
</script>

<template>
  <UDashboardPanel>
    <!-- 如果沒有權限，顯示無權限訊息 -->
    <template v-if="structureInfo.url && !canView">
      <UAlert
        title="沒有權限"
        description="您沒有權限查看此內容"
        color="error"
        variant="soft"
        icon="i-lucide-shield-alert"
      />
    </template>

    <!-- 原有內容 -->
    <template v-else>
      <!-- ... 現有模板內容 ... -->
    </template>
  </UDashboardPanel>
</template>
```

### 2.3 組件層級權限檢查

在 `admin/app/components/App/About/AppAbout.vue` 組件中，可以使用 `PermissionGuard` 來控制內容顯示：

```vue
<script setup lang="ts">
// ... 現有程式碼 ...

const { hasPermission, isSuperAdmin } = usePermission();
const props = defineProps<{
  structureId?: number | null;
  url?: string | null;
}>();

// 檢查是否有查看權限
const canView = computed(() => {
  if (!props.url) {
    return true; // 沒有 url，不需要檢查
  }
  const permissionName = `${props.url}.view`;
  return isSuperAdmin() || hasPermission(permissionName);
});
</script>

<template>
  <div>
    <!-- 如果沒有權限，顯示無權限訊息 -->
    <PermissionGuard :permission="`${url}.view`" :fallback="true">
      <template #fallback>
        <UAlert
          title="沒有權限"
          description="您沒有權限查看關於我們的內容"
          color="error"
          variant="soft"
          icon="i-lucide-shield-alert"
        />
      </template>

      <!-- 原有內容 -->
      <div v-if="loading" class="flex items-center justify-center py-12">
        <UIcon name="i-lucide-loader-2" class="w-6 h-6 animate-spin" />
      </div>
      <template v-else>
        <!-- ... 現有模板內容 ... -->
      </template>
    </PermissionGuard>
  </div>
</template>
```

### 2.4 使用 PermissionGuard 組件

`PermissionGuard` 組件提供了簡單的方式來控制內容顯示：

```vue
<!-- 基本用法 -->
<PermissionGuard permission="about.view">
  <div>只有有權限的使用者才能看到這個內容</div>
</PermissionGuard>

<!-- 無權限時顯示替代內容 -->
<PermissionGuard permission="about.view" :fallback="true">
  <template #default>
    <div>有權限的內容</div>
  </template>
  <template #fallback>
    <UAlert
      title="沒有權限"
      description="您沒有權限查看此內容"
      color="error"
    />
  </template>
</PermissionGuard>
```

### 2.5 在 Composables 中使用權限檢查

在 `admin/app/composables/useAppAbout.ts` 中，可以在資料載入前檢查權限：

```typescript
export const useAppAbout = () => {
  // ... 現有程式碼 ...

  const { hasPermission, isSuperAdmin } = usePermission();

  const fetchData = async (structureId: number | null = null, url: string | null = null) => {
    // 檢查權限
    if (url) {
      const permissionName = `${url}.view`;
      if (!isSuperAdmin() && !hasPermission(permissionName)) {
        submitError.value = "您沒有權限查看此內容";
        return;
      }
    }

    // ... 原有的 fetchData 邏輯 ...
  };

  // ... 其他方法 ...
};
```

---

## 三、後端實作

### 3.1 在控制器中添加權限檢查

在 `api/app/Controllers/AppAboutController.php` 中，需要在 `get()` 方法中添加權限檢查：

```php
<?php
namespace App\Controllers;

use App\Models\AppAboutModel;
use CodeIgniter\HTTP\ResponseInterface;

class AppAboutController extends BaseController
{
    protected $appAboutModel;

    public function __construct()
    {
        // ... 現有程式碼 ...
    }

    /**
     * 取得關於頁設定
     */
    public function get()
    {
        try {
            // 檢查權限（根據單元的 url）
            $structureId = $this->request->getGet('structure_id');
            $url = $this->request->getGet('url'); // 需要從前端傳遞 url 參數
            
            if ($url && !$this->checkPermission("{$url}.view")) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_FORBIDDEN)
                    ->setJSON([
                        'success' => false,
                        'message' => '您沒有權限查看此內容',
                    ]);
            }

            // ... 原有的 get() 方法邏輯 ...
        } catch (\Throwable $e) {
            // ... 錯誤處理 ...
        }
    }

    /**
     * 檢查使用者權限
     * @param string $permissionName 權限名稱
     * @return bool
     */
    protected function checkPermission(string $permissionName): bool
    {
        $session = session();
        $user = $session->get('admin_user');

        if (!$user) {
            return false;
        }

        // 檢查是否為超級管理員
        if (isset($user['roles'])) {
            foreach ($user['roles'] as $role) {
                if (isset($role['name']) && $role['name'] === 'super_admin') {
                    return true;
                }
            }
        }

        // 檢查使用者權限
        $permissions = $user['permissions'] ?? [];
        return in_array($permissionName, $permissions, true);
    }
}
```

### 3.2 使用 Helper 方法（推薦）

為了避免重複程式碼，建議在 `BaseController` 中添加權限檢查方法：

```php
<?php
// api/app/Controllers/BaseController.php

abstract class BaseController extends Controller
{
    // ... 現有程式碼 ...

    /**
     * 檢查使用者權限
     * @param string $permissionName 權限名稱
     * @return bool
     */
    protected function checkPermission(string $permissionName): bool
    {
        $session = session();
        $user = $session->get('admin_user');

        if (!$user) {
            return false;
        }

        // 檢查是否為超級管理員
        if (isset($user['roles'])) {
            foreach ($user['roles'] as $role) {
                if (isset($role['name']) && $role['name'] === 'super_admin') {
                    return true;
                }
            }
        }

        // 檢查使用者權限
        $permissions = $user['permissions'] ?? [];
        return in_array($permissionName, $permissions, true);
    }

    /**
     * 檢查使用者是否有任一權限
     * @param array $permissionNames 權限名稱陣列
     * @return bool
     */
    protected function checkAnyPermission(array $permissionNames): bool
    {
        foreach ($permissionNames as $permission) {
            if ($this->checkPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 檢查使用者是否有所有權限
     * @param array $permissionNames 權限名稱陣列
     * @return bool
     */
    protected function checkAllPermissions(array $permissionNames): bool
    {
        foreach ($permissionNames as $permission) {
            if (!$this->checkPermission($permission)) {
                return false;
            }
        }
        return true;
    }
}
```

然後在 `AppAboutController` 中使用：

```php
public function get()
{
    try {
        // 檢查權限（根據單元的 url）
        $url = $this->request->getJSON(true)['url'] ?? $this->request->getPost('url');
        
        if ($url && !$this->checkPermission("{$url}.view")) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_FORBIDDEN)
                ->setJSON([
                    'success' => false,
                    'message' => '您沒有權限查看此內容',
                ]);
        }

        // ... 原有的邏輯 ...
    } catch (\Throwable $e) {
        // ... 錯誤處理 ...
    }
}
```

---

## 四、權限分配

### 4.1 將權限分配給角色

在系統中，可以將 `about.view` 權限分配給特定角色：

1. **透過資料庫直接分配**：

```sql
-- 假設角色 ID 為 2（例如：編輯者角色）
-- 為「經營理念」單元（about.view，權限 ID=11）分配權限
INSERT INTO `sys_role_permissions` (`role_id`, `permission_id`) VALUES
(2, 11);

-- 為「影音專區」單元（media.view，權限 ID=12）分配權限
INSERT INTO `sys_role_permissions` (`role_id`, `permission_id`) VALUES
(2, 12);
```

2. **透過後台管理介面分配**：
   - 進入「系統管理」→「角色管理」
   - 選擇要分配權限的角色
   - 在權限列表中勾選對應的單元權限（例如：「經營理念-單元查看」、「影音專區-單元查看」）
   - 儲存設定

### 4.2 直接分配給使用者

如果需要直接將權限分配給特定使用者：

```sql
-- 假設使用者 ID 為 3
-- 為使用者分配「經營理念」單元的查看權限
INSERT INTO `sys_user_permissions` (`user_id`, `permission_id`, `is_granted`) VALUES
(3, 11, 1);

-- 為使用者分配「影音專區」單元的查看權限
INSERT INTO `sys_user_permissions` (`user_id`, `permission_id`, `is_granted`) VALUES
(3, 12, 1);
```

---

## 五、測試步驟

### 5.1 側邊欄選單測試

1. **測試有權限的使用者**：

   - 使用擁有 `about.view` 權限的帳號登入
   - 檢查側邊欄選單
   - 確認可以看到「經營理念」選單項目
   - 使用擁有 `media.view` 權限的帳號登入
   - 確認可以看到「影音專區」選單項目

2. **測試無權限的使用者**：

   - 使用沒有 `about.view` 權限的帳號登入
   - 檢查側邊欄選單
   - 確認**看不到**「經營理念」選單項目（自動過濾）
   - 但可以看到其他有權限的單元（例如：`media.view`）

3. **測試超級管理員**：
   - 使用超級管理員帳號登入
   - 檢查側邊欄選單
   - 確認可以看到所有選單項目（包括「關於我們」）

### 5.2 頁面訪問測試

1. **測試有權限的使用者**：

   - 使用擁有 `about.view` 權限的帳號登入
   - 直接訪問「經營理念」單元頁面 URL（例如：`/about`）
   - 確認可以正常查看內容
   - 使用擁有 `media.view` 權限的帳號登入
   - 直接訪問「影音專區」單元頁面 URL（例如：`/media`）
   - 確認可以正常查看內容

2. **測試無權限的使用者**：

   - 使用沒有 `about.view` 權限的帳號登入
   - 直接訪問「經營理念」單元頁面 URL（例如：`/about`）
   - 確認顯示「沒有權限」訊息或無法訪問（需要實作頁面層級檢查）
   - 但可以訪問其他有權限的單元（例如：`/media`，如果有 `media.view` 權限）

3. **測試超級管理員**：
   - 使用超級管理員帳號登入
   - 訪問關於我們單元頁面
   - 確認可以正常查看內容（超級管理員擁有所有權限）

### 5.3 後端測試

1. **測試 API 端點**：

   ```bash
   # 使用有權限的使用者（有 about.view 權限）
   curl -X GET "http://your-api-domain/api/app-about/get?structure_id=23&url=about" \
     -H "Cookie: ci_session=your_session_cookie"

   # 應該返回 200 狀態碼和資料

   # 使用無權限的使用者（沒有 about.view 權限）
   # 應該返回 403 狀態碼和錯誤訊息

   # 使用有權限的使用者（有 media.view 權限）
   curl -X GET "http://your-api-domain/api/app-about/get?structure_id=24&url=media" \
     -H "Cookie: ci_session=your_session_cookie"

   # 應該返回 200 狀態碼和資料
   ```

2. **測試權限檢查邏輯**：
   - 確認超級管理員可以訪問
   - 確認有權限的使用者可以訪問
   - 確認無權限的使用者無法訪問

---

## 六、注意事項

### 6.1 安全性

⚠️ **重要提醒**：

1. **前端權限檢查僅用於 UI 控制**

   - 前端權限檢查只是為了改善使用者體驗（隱藏/顯示功能）
   - **不能**作為安全防護措施

2. **後端權限檢查是必須的**
   - 所有敏感操作都必須在後端進行權限驗證
   - 後端 API 應該實作獨立的權限檢查邏輯
   - 即使前端通過了權限檢查，後端也必須再次驗證

### 6.2 權限檢查優先順序

1. **超級管理員檢查**（最高優先級）

   - 如果使用者是超級管理員，直接通過所有權限檢查

2. **角色權限**

   - 從使用者擁有的角色中獲取權限

3. **直接授予的權限**

   - 直接授予使用者的權限（`is_granted = 1`）

4. **撤銷的權限**（最高優先級，但僅用於撤銷）
   - 即使角色有權限，如果被撤銷（`is_granted = 0`），則無權限

### 6.3 權限命名規範

#### 單元查看權限（基於單元）

- **格式**：`{單元的url}.view`
- **範例**：
  - `about.view` - 查看「經營理念」單元
  - `media.view` - 查看「影音專區」單元
  - `contact.view` - 查看「聯絡我們」單元

#### 模組操作權限（基於模組）

- **格式**：`{module}.{category}.{action}` 或 `{module}.{action}`
- **範例**：
  - `about.section.create` - 新增關於我們區塊（適用於所有使用 about 模組的單元）
  - `about.section.delete` - 刪除關於我們區塊
  - `about.section.sort` - 排序關於我們區塊
  - `about.field.create` - 新增關於我們欄位

---

## 七、相關檔案清單

### 7.1 前端檔案

| 檔案路徑                                      | 說明                                 |
| --------------------------------------------- | ------------------------------------ |
| `admin/app/layouts/default.vue`               | 側邊欄選單權限過濾（**已自動實作**） |
| `admin/app/composables/usePermission.ts`      | 核心權限檢查邏輯                     |
| `admin/app/components/PermissionGuard.vue`    | 權限守衛元件                         |
| `admin/app/components/App/About/AppAbout.vue` | 關於我們組件                         |
| `admin/app/pages/[...slug]/index.vue`         | 動態路由頁面                         |
| `admin/app/composables/useAppAbout.ts`        | 關於我們相關邏輯                     |

### 7.2 後端檔案

| 檔案路徑                                     | 說明                             |
| -------------------------------------------- | -------------------------------- |
| `api/app/Controllers/AppAboutController.php` | 關於我們控制器                   |
| `api/app/Controllers/BaseController.php`     | 基礎控制器（可添加權限檢查方法） |
| `api/app/Controllers/AuthController.php`     | 認證控制器（權限獲取邏輯）       |

### 7.3 資料庫檔案

| 檔案路徑                   | 說明             |
| -------------------------- | ---------------- |
| `sys_permissions.sql`      | 權限表結構和資料 |
| `sys_role_permissions.sql` | 角色權限關聯表   |
| `sys_user_permissions.sql` | 使用者權限關聯表 |

---

## 八、實作建議

### 8.1 建議的實作順序

1. ✅ **側邊欄選單權限過濾**（**已完成**）

   - 系統已自動實作，無需額外設定
   - 沒有權限的選單項目會自動不顯示

2. **後端權限檢查**（優先）

   - 在 `AppAboutController::get()` 方法中添加權限檢查
   - 確保 API 端點有適當的權限驗證

3. **前端頁面層級檢查**（可選）

   - 在動態路由頁面中添加權限檢查
   - 防止無權限使用者直接訪問頁面 URL

4. **前端組件層級檢查**（可選）
   - 在 `AppAbout.vue` 組件中使用 `PermissionGuard`
   - 提供更好的使用者體驗

### 8.2 錯誤處理

建議統一處理權限錯誤：

```typescript
// 前端：在 composable 中統一處理
const fetchData = async (structureId: number | null = null) => {
  try {
    // ... API 呼叫 ...
  } catch (error: any) {
    if (error.status === 403) {
      submitError.value = "您沒有權限查看此內容";
      // 可以選擇導向無權限頁面
      navigateTo("/unauthorized");
    } else {
      // 其他錯誤處理
    }
  }
};
```

---

## 九、總結

實作 `about.view` 權限功能需要：

1. ✅ **權限已建立**：權限已存在於 `sys_permissions` 表中
2. ✅ **側邊欄選單過濾**：已自動實作，沒有權限的選單項目會自動不顯示
3. ⚠️ **前端頁面層級檢查**：可選，建議實作以防止直接訪問 URL
4. ⚠️ **前端組件層級檢查**：可選，提供更好的使用者體驗
5. ⚠️ **後端實作**：**必須實作**，在 API 控制器中添加權限驗證
6. ⚠️ **權限分配**：需要將權限分配給角色或使用者
7. ⚠️ **測試驗證**：需要測試有權限和無權限的使用者場景

### 9.1 已完成的功能

- ✅ **側邊欄選單自動過濾**：系統已自動實作，無需額外設定
  - 沒有權限的選單項目會自動不顯示
  - 超級管理員可以看到所有選單項目
  - 父層級會根據子項目的權限自動顯示或隱藏

### 9.2 待實作的功能

- ⚠️ **後端權限檢查**：必須實作，確保 API 安全性
- ⚠️ **前端頁面層級檢查**：建議實作，防止直接訪問 URL
- ⚠️ **前端組件層級檢查**：可選實作，提供更好的使用者體驗

**安全性提醒**：

- 前端權限檢查僅用於 UI 控制，後端權限檢查是必須的安全措施
- 即使側邊欄選單已經過濾，使用者仍可能直接訪問 URL，因此後端權限檢查是必須的

---

**最後更新：** 2025-12-29
