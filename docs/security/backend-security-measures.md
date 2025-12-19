# 後端安全防護機制說明

本文檔詳細說明目前後端實作的安全防護機制，包括已實作和未實作的項目。

---

## 一、已實作的安全機制

### 1.1 認證機制（Authentication）

#### Session 管理
- **位置**：`api/app/Config/Session.php`
- **實作方式**：
  - 使用 CodeIgniter 的 Session 機制
  - Session 過期時間：7200 秒（2 小時）
  - Session ID 自動更新：每 300 秒
  - Session 儲存路徑：`WRITEPATH . 'session'`（檔案儲存）

- **登入流程**（`AuthController::login()`）：
  ```php
  // 1. 驗證帳號密碼
  if (!password_verify($payload['password'], $admin['password_hash'])) {
      return 401; // 未授權
  }
  
  // 2. 檢查帳號狀態
  if ((int) $admin['status'] !== 1) {
      return 403; // 禁止存取
  }
  
  // 3. Session ID 重新生成（防止 Session Fixation 攻擊）
  $session->regenerate(true);
  
  // 4. 將使用者資訊存入 Session
  $session->set('admin_user', $user);
  ```

- **登出流程**（`AuthController::logout()`）：
  ```php
  $session->remove('admin_user');
  $session->destroy();
  ```

- **檢查登入狀態**（`AuthController::me()`）：
  ```php
  $user = $session->get('admin_user');
  if (!$user) {
      return 401; // 尚未登入
  }
  ```

#### 密碼安全
- **位置**：`api/app/Controllers/AuthController.php`, `AdminsController.php`
- **實作方式**：
  - 使用 PHP 內建的 `password_hash()` 函數（PASSWORD_DEFAULT）
  - 使用 `password_verify()` 進行密碼驗證
  - 密碼不會以明文儲存

```php
// 建立密碼雜湊
'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT)

// 驗證密碼
if (!password_verify($payload['password'], $admin['password_hash'])) {
    return 401;
}
```

---

### 1.2 輸入驗證（Input Validation）

#### CodeIgniter 驗證機制
- **位置**：所有 Controller 的 `add()` 和 `update()` 方法
- **實作方式**：使用 CodeIgniter 的 `validateData()` 方法

**驗證規則範例**：

1. **角色驗證**（`RoleController.php`）：
   ```php
   $rules = [
       'name' => 'required|min_length[1]|max_length[100]|regex_match[/^[a-zA-Z0-9_-]+$/]',
       'label' => 'required|min_length[1]|max_length[255]',
       'description' => 'permit_empty',
       'status' => 'required|in_list[0,1]',
   ];
   ```

2. **權限驗證**（`PermissionController.php`）：
   ```php
   $rules = [
       'name' => 'required|min_length[1]|max_length[255]',
       'label' => 'required|min_length[1]|max_length[255]',
       'module_id' => 'permit_empty|integer',
   ];
   ```

3. **聯絡表單驗證**（`AppContactController.php`）：
   ```php
   $rules = [
       'name' => 'required|min_length[1]|max_length[255]',
       'phone' => 'required|min_length[1]|max_length[50]',
       'email' => 'required|valid_email|max_length[255]',
       'message' => 'permit_empty',
   ];
   ```

**驗證錯誤處理**：
```php
if (!$this->validateData($data, $rules)) {
    return $this->response->setStatusCode(ResponseInterface::HTTP_UNPROCESSABLE_ENTITY)->setJSON([
        'success' => false,
        'message' => '驗證失敗',
        'errors' => $this->validator->getErrors(),
    ]);
}
```

---

### 1.3 SQL 注入防護

#### CodeIgniter Query Builder
- **實作方式**：使用 CodeIgniter 的 Query Builder 進行資料庫查詢
- **保護機制**：
  - Query Builder 自動進行參數綁定（Prepared Statements）
  - 所有使用者輸入都會被自動轉義

**範例**：
```php
// 安全的查詢方式
$admin = $this->userModel->where('username', $payload['username'])->first();

// 使用 whereIn 也是安全的
$permissionList = $this->permissionModel
    ->whereIn('id', $permissionIds)
    ->where('status', 1)
    ->findAll();
```

---

### 1.4 CORS 設定

#### Cross-Origin Resource Sharing
- **位置**：`api/app/Config/Cors.php`
- **實作方式**：
  - 在 `Filters.php` 中全域啟用 CORS filter
  - 限制允許的來源域名

**設定內容**：
```php
'allowedOrigins' => [
    'http://localhost:3000',
    'http://127.0.0.1:3000',
    'https://test-sys.srl.tw',
],
'supportsCredentials' => true, // 允許攜帶 Cookie（Session）
'allowedMethods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
'allowedHeaders' => [
    'Content-Type',
    'Authorization',
    'Accept',
    'X-Requested-With',
    'X-CSRF-TOKEN',
],
```

---

### 1.5 HTTPS 強制（生產環境）

- **位置**：`api/app/Config/Filters.php`
- **實作方式**：
  - 在 `required` filters 中啟用 `forcehttps`
  - 強制所有請求使用 HTTPS

```php
public array $required = [
    'before' => [
        'forcehttps', // Force Global Secure Requests
        'pagecache',
    ],
    // ...
];
```

---

### 1.6 錯誤處理

#### 錯誤訊息保護
- **實作方式**：
  - 生產環境隱藏詳細錯誤訊息
  - 只在開發環境顯示錯誤詳情

```php
return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
    'success' => false,
    'message' => '操作失敗，請稍後再試',
    'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
]);
```

#### 日誌記錄
- **實作方式**：使用 `log_message()` 記錄錯誤

```php
log_message('error', 'getRoles failed: {message}', ['message' => $e->getMessage()]);
```

---

### 1.7 權限檢查（部分實作）

#### 管理員列表過濾
- **位置**：`api/app/Controllers/AdminsController.php`
- **實作方式**：
  - 非超級管理員無法查看其他超級管理員

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
    $admins = array_filter($admins, function($admin) {
        foreach ($admin['roles'] as $role) {
            if (isset($role['name']) && $role['name'] === 'super_admin') {
                return false;
            }
        }
        return true;
    });
}
```

---

## 二、未實作或需要改進的安全機制

### 2.1 ❌ 統一的認證中間件/Filter

**現況**：
- 目前**沒有統一的全域認證 Filter**
- 各個 Controller 沒有強制檢查使用者是否已登入
- 除了 `AuthController::me()` 和 `AdminsController::getAdmins()` 外，其他 API 端點都沒有檢查 Session

**風險**：
- 未登入的使用者可能可以直接存取 API
- 需要手動在每個 Controller 方法中加入 Session 檢查（容易遺漏）

**建議實作**：
1. 建立自訂的 `AuthFilter` 類別
2. 在 `Filters.php` 中為需要認證的路由套用此 Filter
3. 或者建立 `BaseController` 的 `checkAuth()` 方法供子類別呼叫

**範例實作**（建議）：

```php
// api/app/Filters/AuthFilter.php
<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $user = $session->get('admin_user');
        
        if (!$user) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'success' => false,
                    'message' => '尚未登入，請先登入',
                ]);
        }
        
        // 可以選擇性地檢查帳號狀態
        if ((int) $user['status'] !== 1) {
            $session->remove('admin_user');
            return service('response')
                ->setStatusCode(403)
                ->setJSON([
                    'success' => false,
                    'message' => '帳號已停用',
                ]);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // 不需要後處理
    }
}
```

```php
// api/app/Config/Filters.php
public array $aliases = [
    // ... 現有的 aliases
    'auth' => \App\Filters\AuthFilter::class,
];

public array $filters = [
    'auth' => ['before' => ['api/*']], // 為所有 API 路由套用認證
];
```

---

### 2.2 ❌ CSRF 防護

**現況**：
- CSRF filter 已定義但**未啟用**（在 `Filters.php` 中被註解）

```php
public array $globals = [
    'before' => [
        'cors',
        // 'csrf',  // 目前未啟用
    ],
];
```

**風險**：
- 可能遭受 CSRF（Cross-Site Request Forgery）攻擊
- 惡意網站可能誘導已登入的使用者執行未預期的操作

**建議**：
- 如果前端是 SPA（Single Page Application）且使用 Session Cookie，可以考慮啟用 CSRF 防護
- 或者使用 Token-based 認證（JWT）代替 Session

**啟用方式**：
```php
public array $globals = [
    'before' => [
        'cors',
        'csrf',  // 啟用 CSRF 防護
    ],
];
```

**前端配合**（需要在前端發送請求時包含 CSRF Token）：
```typescript
// 從 Cookie 讀取 CSRF Token 並在 Header 中發送
const csrfToken = getCookie('csrf_cookie_name');
headers['X-CSRF-TOKEN'] = csrfToken;
```

---

### 2.3 ❌ 統一的權限檢查機制

**現況**：
- 權限檢查主要在**前端**進行
- 後端除了 `AdminsController::getAdmins()` 外，**沒有實作權限檢查**
- 後端會查詢使用者的權限，但不會驗證使用者是否有權限執行特定操作

**風險**：
- 前端權限檢查可以被繞過（例如直接呼叫 API）
- 惡意使用者可能直接存取無權限的 API 端點

**建議實作**：
1. 建立 `PermissionFilter` 或 `PermissionHelper`
2. 在需要權限檢查的 Controller 方法中加入檢查
3. 檢查使用者是否擁有執行該操作所需的權限

**範例實作**（建議）：

```php
// api/app/Filters/PermissionFilter.php
<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\UserRoleModel;
use App\Models\UserPermissionModel;
use App\Models\RolePermissionModel;
use App\Models\PermissionModel;

class PermissionFilter implements FilterInterface
{
    protected $requiredPermission;
    
    public function before(RequestInterface $request, $arguments = null)
    {
        // 從參數中取得需要的權限名稱
        $this->requiredPermission = $arguments[0] ?? null;
        
        if (!$this->requiredPermission) {
            return; // 沒有指定權限，允許通過
        }
        
        $session = session();
        $user = $session->get('admin_user');
        
        if (!$user) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'success' => false,
                    'message' => '尚未登入',
                ]);
        }
        
        // 檢查是否為超級管理員
        $userRoleModel = new UserRoleModel();
        $isSuperAdmin = $userRoleModel
            ->select('sys_roles.name')
            ->join('sys_roles', 'sys_roles.id = sys_user_roles.role_id')
            ->where('sys_user_roles.user_id', $user['id'])
            ->where('sys_roles.name', 'super_admin')
            ->first();
        
        if ($isSuperAdmin) {
            return; // 超級管理員有所有權限
        }
        
        // 檢查使用者是否有權限
        if (!in_array($this->requiredPermission, $user['permissions'] ?? [])) {
            return service('response')
                ->setStatusCode(403)
                ->setJSON([
                    'success' => false,
                    'message' => '沒有權限執行此操作',
                ]);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // 不需要後處理
    }
}
```

**使用方式**：
```php
// 在 Routes.php 中
$routes->post('/api/role/add', 'RoleController::add', ['filter' => 'auth,permission:system.roles.create']);

// 或者在 Controller 中
public function add()
{
    // 檢查權限
    $this->checkPermission('system.roles.create');
    // ... 其他邏輯
}
```

---

### 2.4 ⚠️ Session 安全性設定

**現況**：
- Session Match IP：`false`（不匹配 IP）
- Session Regenerate Destroy：`false`（不刪除舊 Session 資料）

**建議改進**：

1. **考慮啟用 IP 匹配**（如果不會有 IP 變動的情況）：
   ```php
   public bool $matchIP = true; // 更安全，但可能影響使用者在不同網路間切換
   ```

2. **啟用 Session Regenerate Destroy**：
   ```php
   public bool $regenerateDestroy = true; // 重新生成 Session ID 時刪除舊資料
   ```

---

### 2.5 ❌ Rate Limiting（請求頻率限制）

**現況**：
- 沒有實作 Rate Limiting
- 可能遭受暴力破解攻擊（Brute Force Attack）

**建議實作**：
- 對登入 API 實作 Rate Limiting（例如：每 IP 每 15 分鐘最多 5 次登入嘗試）
- 使用 CodeIgniter 的 Rate Limiting Filter 或自訂實作

---

### 2.6 ❌ 輸入清理（Input Sanitization）

**現況**：
- 有輸入驗證，但**沒有明確的輸入清理**
- CodeIgniter 的 Query Builder 會自動轉義 SQL，但可能需要額外的 XSS 防護

**建議**：
- 對於需要儲存 HTML 內容的欄位（如 `description`、`message`），考慮使用 HTML 清理函式庫
- 對於輸出到前端的資料，確保適當的轉義（CodeIgniter 的 View 會自動轉義）

---

### 2.7 ⚠️ 敏感資料保護

**現況**：
- 密碼使用雜湊儲存 ✓
- 但可能需要在回應中過濾敏感資料

**建議**：
- 確保 API 回應中不包含密碼雜湊
- 檢查是否有其他敏感資料（如 API Key、Token）被意外暴露

---

## 三、安全檢查清單

### 3.1 認證相關

- [x] Session 管理
- [x] 密碼雜湊（password_hash）
- [x] 密碼驗證（password_verify）
- [ ] 統一的認證中間件/Filter
- [ ] 帳號鎖定機制（防止暴力破解）
- [ ] 密碼強度要求
- [ ] 密碼重置功能的安全性

### 3.2 授權相關

- [x] 角色和權限系統（RBAC）
- [ ] 後端權限檢查
- [ ] API 端點權限驗證
- [x] 超級管理員特殊處理（部分實作）

### 3.3 輸入處理

- [x] 輸入驗證（CodeIgniter Validator）
- [x] SQL 注入防護（Query Builder）
- [ ] 輸入清理（XSS 防護）
- [ ] 檔案上傳驗證（如果有）

### 3.4 網路安全

- [x] CORS 設定
- [x] HTTPS 強制（生產環境）
- [ ] CSRF 防護（未啟用）
- [ ] Rate Limiting

### 3.5 資料保護

- [x] 密碼雜湊
- [x] 錯誤訊息保護（生產環境）
- [ ] 敏感資料過濾
- [ ] 資料加密（如需要）

### 3.6 日誌與監控

- [x] 錯誤日誌記錄
- [ ] 安全事件日誌（登入失敗、權限拒絕等）
- [ ] 審計日誌（重要操作記錄）

---

## 四、優先改進建議

### 高優先級 🔴

1. **實作統一的認證 Filter**
   - 確保所有需要認證的 API 端點都有檢查 Session
   - 防止未授權存取

2. **實作後端權限檢查**
   - 在 Controller 方法中檢查使用者權限
   - 防止權限繞過攻擊

### 中優先級 🟡

3. **啟用 CSRF 防護**
   - 如果使用 Session Cookie，應該啟用 CSRF 防護
   - 需要前端配合傳送 CSRF Token

4. **實作 Rate Limiting**
   - 特別是對登入 API
   - 防止暴力破解攻擊

### 低優先級 🟢

5. **改善 Session 安全性設定**
   - 考慮啟用 IP 匹配（如果適用）
   - 啟用 regenerateDestroy

6. **輸入清理**
   - 對於 HTML 內容進行清理
   - 防止 XSS 攻擊

---

## 五、相關檔案清單

### 配置檔案

| 檔案路徑 | 說明 |
|---------|------|
| `api/app/Config/Filters.php` | Filter 配置 |
| `api/app/Config/Session.php` | Session 配置 |
| `api/app/Config/Security.php` | 安全配置（CSRF 等） |
| `api/app/Config/Cors.php` | CORS 配置 |

### Controller 檔案

| 檔案路徑 | 說明 |
|---------|------|
| `api/app/Controllers/AuthController.php` | 認證邏輯 |
| `api/app/Controllers/AdminsController.php` | 管理員管理（有部分權限檢查） |
| `api/app/Controllers/BaseController.php` | 基礎 Controller |

### Model 檔案

| 檔案路徑 | 說明 |
|---------|------|
| `api/app/Models/SysAdminModel.php` | 管理員模型 |
| `api/app/Models/UserRoleModel.php` | 使用者角色模型 |
| `api/app/Models/UserPermissionModel.php` | 使用者權限模型 |

---

## 六、總結

### 已實作的安全機制

✅ Session 管理與認證  
✅ 密碼雜湊與驗證  
✅ 輸入驗證  
✅ SQL 注入防護（Query Builder）  
✅ CORS 設定  
✅ HTTPS 強制  
✅ 錯誤處理與日誌記錄  

### 需要改進的部分

❌ 統一的認證中間件/Filter  
❌ 後端權限檢查  
❌ CSRF 防護（已定義但未啟用）  
❌ Rate Limiting  
❌ 輸入清理（XSS 防護）  

### 風險評估

**目前風險等級：中等到高**

- **未授權存取風險**：由於沒有統一的認證 Filter，未登入使用者可能存取 API
- **權限繞過風險**：由於沒有後端權限檢查，使用者可能繞過前端限制直接呼叫 API
- **CSRF 攻擊風險**：CSRF 防護未啟用，可能遭受 CSRF 攻擊

**建議優先實作認證 Filter 和權限檢查機制。**

優先改進建議
高優先級：
實作統一的認證 Filter（防止未授權存取）
實作後端權限檢查（防止權限繞過）
中優先級：
啟用 CSRF 防護
實作 Rate Limiting（特別是登入 API）
低優先級：
改善 Session 安全性設定
加強輸入清理

---

**最後更新：** 2025-01-24
