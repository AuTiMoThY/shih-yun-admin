# 權限檢查詳細說明

本文檔詳細說明系統中權限檢查的實作方式，包括超級管理員和其他管理員的權限判斷邏輯。

---

## 一、超級管理員權限判斷

### 1.1 前端判斷邏輯

#### 核心檔案：`admin/app/composables/usePermission.ts`

**判斷函數：`isSuperAdmin()`**

```typescript
/**
 * 檢查是否為超級管理員
 */
const isSuperAdmin = (): boolean => {
  return hasRole('super_admin')
}
```

**判斷流程：**
1. 呼叫 `hasRole('super_admin')` 檢查使用者是否擁有 `super_admin` 角色
2. `hasRole()` 會從 `getUserRoles()` 獲取使用者的所有角色名稱
3. 檢查角色名稱陣列中是否包含 `'super_admin'`

**使用位置：**

1. **`hasModulePermission()`** - 檢查模組權限
   ```typescript
   const hasModulePermission = (module: string, action: string, category?: string): boolean => {
     // 超級管理員有所有權限
     if (isSuperAdmin()) return true
     // ... 其他檢查邏輯
   }
   ```
   - 檔案位置：`admin/app/composables/usePermission.ts:137-138`

2. **`hasRegionPermission()`** - 檢查區域權限
   ```typescript
   const hasRegionPermission = (module: string, region: 'tw' | 'sg' | 'mm', action: string = 'view'): boolean => {
     // 超級管理員有所有權限
     if (isSuperAdmin()) return true
     // ... 其他檢查邏輯
   }
   ```
   - 檔案位置：`admin/app/composables/usePermission.ts:177-178`

3. **`PermissionGuard.vue`** - 權限守衛元件
   ```typescript
   const hasAccess = computed(() => {
     // 超級管理員擁有所有權限
     if (permissionHelper.isSuperAdmin()) {
       return true
     }
     // ... 其他檢查邏輯
   })
   ```
   - 檔案位置：`admin/app/components/PermissionGuard.vue:50-53`

4. **`system.ts`** - 系統選單過濾
   ```typescript
   const filteredChildren = allMenuItems
     .filter((item) => {
       // 超級管理員可以看到所有選單
       if (isSuperAdmin()) {
         return true
       }
       // ... 其他檢查邏輯
     })
   ```
   - 檔案位置：`admin/app/constants/menu/system.ts:75-78`

5. **`permission-directive.client.ts`** - v-permission 指令
   ```typescript
   const checkPermission = (binding: any): boolean => {
     // 超級管理員有所有權限
     if (isSuperAdmin()) {
       return true
     }
     // ... 其他檢查邏輯
   }
   ```
   - 檔案位置：`admin/app/plugins/permission-directive.client.ts:13-16`

### 1.2 後端判斷邏輯

#### 核心檔案：`api/app/Controllers/AuthController.php`

**重要說明：**
後端的 `getUserPermissions()` 方法**並沒有特別處理超級管理員**，而是從資料庫查詢權限。這意味著：
- 超級管理員的權限是透過前端判斷的
- 後端會正常查詢超級管理員的角色和權限（即使可能沒有分配具體權限）

**權限獲取流程：**
```php
protected function getUserPermissions($userId)
{
    // 從角色獲取權限
    $userRoles = $this->userRoleModel->where('user_id', $userId)->findAll();
    foreach ($userRoles as $userRole) {
        $rolePermissions = $this->rolePermissionModel
            ->where('role_id', $userRole['role_id'])
            ->findAll();
        // ... 收集權限
    }
    
    // 從直接授予的權限獲取（is_granted = 1）
    $directPermissions = $this->userPermissionModel
        ->where('user_id', $userId)
        ->where('is_granted', 1)
        ->findAll();
    
    // 移除被撤銷的權限（is_granted = 0）
    $revokedPermissions = $this->userPermissionModel
        ->where('user_id', $userId)
        ->where('is_granted', 0)
        ->findAll();
    
    // ... 返回權限列表
}
```

**後端檢查超級管理員的位置：**

1. **`AdminsController.php`** - 管理員列表過濾
   ```php
   // 檢查當前用戶是否為 super_admin
   $currentUserRoles = $this->userRoleModel
       ->select('sys_roles.name')
       ->join('sys_roles', 'sys_roles.id = sys_user_roles.role_id')
       ->where('sys_user_roles.user_id', $currentUser['id'])
       ->where('sys_roles.name', 'super_admin')
       ->findAll();
   
   $isCurrentUserSuperAdmin = !empty($currentUserRoles);
   
   // 如果當前用戶不是 super_admin，過濾掉擁有 super_admin 角色的管理員
   if (!$isCurrentUserSuperAdmin) {
       // ... 過濾邏輯
   }
   ```
   - 檔案位置：`api/app/Controllers/AdminsController.php:113-157`

---

## 二、其他管理員的權限判斷

### 2.1 前端判斷邏輯

#### 核心檔案：`admin/app/composables/usePermission.ts`

**權限獲取函數：`getUserPermissions()`**

```typescript
const getUserPermissions = (): string[] => {
  if (!user.value) return []
  
  const permissions: string[] = []
  
  // 1. 從角色獲取權限
  if (user.value.roles) {
    user.value.roles.forEach((role: Role) => {
      if (role.permissions) {
        role.permissions.forEach((permission: Permission) => {
          if (!permissions.includes(permission.name)) {
            permissions.push(permission.name)
          }
        })
      }
    })
  }
  
  // 2. 加入直接授予的權限
  if (user.value.permissions) {
    user.value.permissions.forEach((permission: string | Permission) => {
      const permissionName = typeof permission === 'string' ? permission : permission.name
      if (permissionName && !permissions.includes(permissionName)) {
        permissions.push(permissionName)
      }
    })
  }
  
  return permissions
}
```

**權限檢查流程：**

1. **檢查單一權限：`hasPermission()`**
   ```typescript
   const hasPermission = (permission: string | string[]): boolean => {
     const userPermissions = getUserPermissions()
     
     if (Array.isArray(permission)) {
       return permission.every(p => userPermissions.includes(p))
     }
     
     return userPermissions.includes(permission)
   }
   ```

2. **檢查模組權限：`hasModulePermission()`**
   ```typescript
   const hasModulePermission = (module: string, action: string, category?: string): boolean => {
     // 超級管理員有所有權限
     if (isSuperAdmin()) return true
     
     // 構建權限名稱
     const permissionName = category
       ? `${module}.${category}.${action}`
       : `${module}.${action}`
     
     return hasPermission(permissionName)
   }
   ```

3. **檢查區域權限：`hasRegionPermission()`**
   ```typescript
   const hasRegionPermission = (module: string, region: 'tw' | 'sg' | 'mm', action: string = 'view'): boolean => {
     // 超級管理員有所有權限
     if (isSuperAdmin()) return true
     
     // 檢查特定區域權限
     const specificPermission = `${module}.${region}.${action}`
     if (hasPermission(specificPermission)) return true
     
     // 檢查通用管理權限
     const managePermission = `${module}.${region}.manage`
     return hasPermission(managePermission)
   }
   ```

### 2.2 後端判斷邏輯

#### 核心檔案：`api/app/Controllers/AuthController.php`

**權限獲取函數：`getUserPermissions($userId)`**

```php
protected function getUserPermissions($userId)
{
    $permissions = [];
    $permissionIds = [];

    // 1. 從角色獲取權限
    $userRoles = $this->userRoleModel->where('user_id', $userId)->findAll();
    foreach ($userRoles as $userRole) {
        $rolePermissions = $this->rolePermissionModel
            ->where('role_id', $userRole['role_id'])
            ->findAll();
        foreach ($rolePermissions as $rp) {
            if (!in_array($rp['permission_id'], $permissionIds)) {
                $permissionIds[] = $rp['permission_id'];
            }
        }
    }

    // 2. 從直接授予的權限獲取（is_granted = 1）
    $directPermissions = $this->userPermissionModel
        ->where('user_id', $userId)
        ->where('is_granted', 1)
        ->findAll();
    foreach ($directPermissions as $dp) {
        if (!in_array($dp['permission_id'], $permissionIds)) {
            $permissionIds[] = $dp['permission_id'];
        }
    }

    // 3. 移除被撤銷的權限（is_granted = 0）
    $revokedPermissions = $this->userPermissionModel
        ->where('user_id', $userId)
        ->where('is_granted', 0)
        ->findAll();
    $revokedIds = array_column($revokedPermissions, 'permission_id');
    $permissionIds = array_diff($permissionIds, $revokedIds);

    // 4. 取得權限詳細資料
    if (!empty($permissionIds)) {
        $permissionList = $this->permissionModel
            ->whereIn('id', $permissionIds)
            ->where('status', 1)
            ->findAll();
        $permissions = array_column($permissionList, 'name');
    }

    return $permissions;
}
```

**權限計算邏輯：**

1. **從角色獲取的權限**
   - 查詢 `sys_user_roles` 表獲取使用者的所有角色
   - 查詢 `sys_role_permissions` 表獲取每個角色的權限
   - 合併所有權限 ID

2. **直接授予的權限**
   - 查詢 `sys_user_permissions` 表，條件：`is_granted = 1`
   - 將權限 ID 加入列表

3. **撤銷的權限（優先級最高）**
   - 查詢 `sys_user_permissions` 表，條件：`is_granted = 0`
   - 從權限列表中移除這些權限 ID

4. **取得權限名稱**
   - 根據權限 ID 查詢 `sys_permissions` 表
   - 只返回狀態為「啟用」（`status = 1`）的權限
   - 返回權限名稱陣列

**登入時載入權限：**

在 `AuthController::login()` 方法中：
```php
// 取得使用者的角色和權限
$roles = $this->getUserRoles($admin['id']);
$permissions = $this->getUserPermissions($admin['id']);

$user = [
    // ... 其他欄位
    'roles'       => $roles,
    'permissions' => $permissions,
];

$session->set('admin_user', $user);
```

---

## 三、權限檢查流程圖

### 3.1 前端權限檢查流程

```
使用者請求檢查權限
    ↓
isSuperAdmin()?
    ├─ 是 → 返回 true（擁有所有權限）
    └─ 否 → 繼續檢查
        ↓
getUserPermissions()
    ├─ 從 user.value.roles 獲取角色權限
    ├─ 從 user.value.permissions 獲取直接權限
    └─ 合併並去重
        ↓
hasPermission(permissionName)
    ├─ 檢查權限是否存在於列表中
    └─ 返回 true/false
```

### 3.2 後端權限獲取流程

```
登入時或呼叫 /api/auth/me
    ↓
getUserPermissions($userId)
    ├─ 步驟 1：從 sys_user_roles → sys_role_permissions 獲取角色權限
    ├─ 步驟 2：從 sys_user_permissions (is_granted=1) 獲取直接權限
    ├─ 步驟 3：從 sys_user_permissions (is_granted=0) 移除撤銷權限
    └─ 步驟 4：查詢 sys_permissions 獲取權限名稱（只返回 status=1）
        ↓
返回權限名稱陣列
    ↓
存入 Session (admin_user.permissions)
```

---

## 四、資料庫表結構

### 4.1 相關資料表

1. **`sys_roles`** - 角色表
   - 儲存角色資訊（如：`super_admin`, `editor`, `viewer`）

2. **`sys_permissions`** - 權限表
   - 儲存權限資訊（如：`product.view`, `product.edit`）

3. **`sys_user_roles`** - 使用者角色關聯表
   - 關聯使用者和角色

4. **`sys_role_permissions`** - 角色權限關聯表
   - 關聯角色和權限

5. **`sys_user_permissions`** - 使用者權限關聯表
   - 直接授予或撤銷使用者的權限
   - `is_granted = 1`：授予權限
   - `is_granted = 0`：撤銷權限

### 4.2 權限計算邏輯

```
使用者最終權限 = 
    角色權限（從 sys_user_roles → sys_role_permissions）
    + 直接授予的權限（sys_user_permissions, is_granted=1）
    - 撤銷的權限（sys_user_permissions, is_granted=0）
```

---

## 五、重要檔案清單

### 5.1 前端檔案

| 檔案路徑 | 說明 |
|---------|------|
| `admin/app/composables/usePermission.ts` | 核心權限檢查邏輯 |
| `admin/app/components/PermissionGuard.vue` | 權限守衛元件 |
| `admin/app/constants/menu/system.ts` | 系統選單權限過濾 |
| `admin/app/plugins/permission-directive.client.ts` | v-permission 和 v-role 指令 |

### 5.2 後端檔案

| 檔案路徑 | 說明 |
|---------|------|
| `api/app/Controllers/AuthController.php` | 登入、權限獲取邏輯 |
| `api/app/Controllers/AdminsController.php` | 管理員管理（包含超級管理員檢查） |
| `api/app/Models/UserRoleModel.php` | 使用者角色模型 |
| `api/app/Models/UserPermissionModel.php` | 使用者權限模型 |
| `api/app/Models/RolePermissionModel.php` | 角色權限模型 |

---

## 六、注意事項

### 6.1 超級管理員的特殊處理

1. **前端優先判斷**
   - 前端在檢查權限時，會先判斷是否為超級管理員
   - 如果是超級管理員，直接返回 `true`，跳過具體權限檢查

2. **後端不特殊處理**
   - 後端的 `getUserPermissions()` 方法不會特別處理超級管理員
   - 後端會正常查詢超級管理員的角色和權限
   - 這意味著超級管理員可能沒有分配具體權限，但前端仍會給予所有權限

3. **建議**
   - 超級管理員應該擁有 `super_admin` 角色
   - 可以選擇性地為 `super_admin` 角色分配所有權限（方便後端檢查）
   - 或者保持 `super_admin` 角色沒有具體權限，完全依賴前端判斷

### 6.2 權限檢查的優先順序

1. **超級管理員檢查**（最高優先級）
   - 如果使用者是超級管理員，直接通過所有權限檢查

2. **角色權限**
   - 從使用者擁有的角色中獲取權限

3. **直接授予的權限**
   - 直接授予使用者的權限（`is_granted = 1`）

4. **撤銷的權限**（最高優先級，但僅用於撤銷）
   - 即使角色有權限，如果被撤銷（`is_granted = 0`），則無權限

### 6.3 安全性提醒

⚠️ **重要**：前端權限檢查僅用於 UI 控制，**不能**作為安全防護。

- 所有敏感操作都必須在後端進行權限驗證
- 前端權限檢查只是為了改善使用者體驗（隱藏/顯示功能）
- 後端 API 應該實作獨立的權限檢查邏輯

---

## 七、總結

### 7.1 超級管理員權限判斷

- **判斷檔案**：`admin/app/composables/usePermission.ts` 的 `isSuperAdmin()` 函數
- **判斷方式**：檢查使用者是否擁有 `super_admin` 角色
- **使用位置**：
  - `hasModulePermission()` - 模組權限檢查
  - `hasRegionPermission()` - 區域權限檢查
  - `PermissionGuard.vue` - 權限守衛元件
  - `system.ts` - 系統選單過濾
  - `permission-directive.client.ts` - v-permission 指令

### 7.2 其他管理員權限判斷

- **前端判斷**：`admin/app/composables/usePermission.ts` 的 `getUserPermissions()` 函數
  - 從 `user.value.roles` 獲取角色權限
  - 從 `user.value.permissions` 獲取直接權限
  - 合併並去重後進行檢查

- **後端判斷**：`api/app/Controllers/AuthController.php` 的 `getUserPermissions()` 方法
  - 從 `sys_user_roles` → `sys_role_permissions` 獲取角色權限
  - 從 `sys_user_permissions` (is_granted=1) 獲取直接權限
  - 從 `sys_user_permissions` (is_granted=0) 移除撤銷權限
  - 查詢 `sys_permissions` 獲取權限名稱（只返回 status=1）

---

**最後更新：** 2025-01-24
