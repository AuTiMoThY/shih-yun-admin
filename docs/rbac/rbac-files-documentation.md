# RBAC 權限管理系統 - 檔案功能說明文件

本文件詳細說明 RBAC 權限管理系統中每個檔案的功能、結構和作用。

---

## 目錄

### 後端檔案（PHP - CodeIgniter 4）
1. [Models](#models)
   - RoleModel.php
   - PermissionModel.php
   - RolePermissionModel.php
   - UserRoleModel.php
   - UserPermissionModel.php
2. [Controllers](#controllers)
   - RoleController.php
   - PermissionController.php
   - AuthController.php

### 前端檔案（TypeScript/Vue - Nuxt 3）
3. [Composables](#composables)
   - useRole.ts
   - usePermissionData.ts
4. [Types](#types)
   - permission.ts
   - index.ts
5. [Pages](#pages)
   - roles.vue
   - permissions.vue
6. [Components](#components)
   - Role/FrmModal.vue
   - Permission/FrmModal.vue
7. [Plugins](#plugins)
   - permission-directive.client.ts

---

## Models

### 1. `api/app/Models/RoleModel.php`

**功能說明：**
- 管理 `roles` 資料表的資料存取
- 提供角色的 CRUD 操作
- 定義角色資料的驗證規則

**主要屬性：**
- `$table = 'roles'` - 對應的資料表名稱
- `$primaryKey = 'id'` - 主鍵欄位
- `$allowedFields` - 允許操作的欄位：`name`, `label`, `description`, `status`
- `$useTimestamps = true` - 自動管理 `created_at` 和 `updated_at`

**驗證規則：**
- `name`: 必填、長度 1-100、唯一性、只允許英數字、底線、連字號
- `label`: 必填、長度 1-255
- `status`: 可選、必須是 0 或 1

**用途：**
- 在 `RoleController` 中使用，處理角色的資料庫操作
- 確保角色資料的完整性和一致性

---

### 2. `api/app/Models/PermissionModel.php`

**功能說明：**
- 管理 `permissions` 資料表的資料存取
- 提供權限的 CRUD 操作
- 定義權限資料的驗證規則

**主要屬性：**
- `$table = 'permissions'` - 對應的資料表名稱
- `$primaryKey = 'id'` - 主鍵欄位
- `$allowedFields` - 允許操作的欄位：`name`, `label`, `description`, `module_id`, `category`, `action`, `status`
- `$useTimestamps = true` - 自動管理時間戳記

**驗證規則：**
- `name`: 必填、長度 1-255、唯一性
- `label`: 必填、長度 1-255
- `status`: 可選、必須是 0 或 1

**特殊欄位：**
- `module_id`: 關聯到 `sysmodule` 表（可選）
- `category`: 分類（如：tw, sg, mm）
- `action`: 動作（如：view, create, edit, delete）

**用途：**
- 在 `PermissionController` 中使用
- 儲存系統中所有可用的權限定義

---

### 3. `api/app/Models/RolePermissionModel.php`

**功能說明：**
- 管理 `role_permissions` 關聯表的資料存取
- 建立角色與權限之間的多對多關係

**主要屬性：**
- `$table = 'role_permissions'` - 關聯表名稱
- `$allowedFields` - 允許操作的欄位：`role_id`, `permission_id`

**用途：**
- 當角色被分配權限時，在此表中建立關聯記錄
- 當角色被刪除時，相關聯的記錄會自動刪除（外鍵約束）

**關聯關係：**
- 一個角色可以有多個權限
- 一個權限可以分配給多個角色

---

### 4. `api/app/Models/UserRoleModel.php`

**功能說明：**
- 管理 `user_roles` 關聯表的資料存取
- 建立使用者與角色之間的多對多關係

**主要屬性：**
- `$table = 'user_roles'` - 關聯表名稱
- `$allowedFields` - 允許操作的欄位：`user_id`, `role_id`

**用途：**
- 當使用者被分配角色時，在此表中建立關聯記錄
- 在 `AuthController` 中使用，查詢使用者的角色

**關聯關係：**
- 一個使用者可以擁有多個角色
- 一個角色可以分配給多個使用者

---

### 5. `api/app/Models/UserPermissionModel.php`

**功能說明：**
- 管理 `user_permissions` 關聯表的資料存取
- 允許直接為使用者授予或撤銷特定權限（繞過角色）

**主要屬性：**
- `$table = 'user_permissions'` - 關聯表名稱
- `$allowedFields` - 允許操作的欄位：`user_id`, `permission_id`, `is_granted`

**特殊欄位：**
- `is_granted`: 
  - `1` = 授予權限（即使角色沒有此權限）
  - `0` = 撤銷權限（即使角色有此權限也會被撤銷）

**用途：**
- 在 `AuthController` 中使用，計算使用者的最終權限
- 提供細粒度的權限控制（可以覆蓋角色權限）

**權限計算邏輯：**
1. 先收集角色權限
2. 加入直接授予的權限（`is_granted = 1`）
3. 移除被撤銷的權限（`is_granted = 0`）

---

## Controllers

### 6. `api/app/Controllers/RoleController.php`

**功能說明：**
- 處理所有與角色相關的 HTTP 請求
- 提供角色的 CRUD API 端點

**主要方法：**

#### `get()`
- **路由：** `GET /api/role/get`
- **功能：** 取得所有角色列表
- **回應：** JSON 格式的角色陣列

#### `getById()`
- **路由：** `GET /api/role/get-by-id?id={id}`
- **功能：** 取得單一角色的詳細資料（包含分配的權限 ID 列表）
- **回應：** 角色資料 + `permission_ids` 陣列

#### `add()`
- **路由：** `POST /api/role/add`
- **功能：** 新增角色
- **請求體：**
  ```json
  {
    "name": "editor",
    "label": "編輯者",
    "description": "可以編輯內容",
    "status": 1,
    "permission_ids": [1, 2, 3]
  }
  ```
- **驗證：**
  - 檢查角色名稱是否已存在
  - 驗證格式（只允許英數字、底線、連字號）
- **處理：**
  - 建立角色記錄
  - 如果有 `permission_ids`，建立角色權限關聯

#### `update()`
- **路由：** `POST /api/role/update`
- **功能：** 更新角色資訊和權限分配
- **請求體：** 包含 `id` 和要更新的欄位
- **處理：**
  - 更新角色基本資訊
  - 如果提供 `permission_ids`，先刪除舊關聯，再建立新關聯

#### `delete()`
- **路由：** `POST /api/role/delete`
- **功能：** 刪除角色
- **保護機制：**
  - 不允許刪除 `super_admin` 角色
  - 檢查是否有使用者使用此角色（如有則不允許刪除）
- **處理：** 刪除角色（會自動刪除關聯的 `role_permissions` 記錄）

**錯誤處理：**
- 所有方法都包含 try-catch 錯誤處理
- 記錄錯誤日誌
- 在非生產環境中回傳詳細錯誤訊息

---

### 7. `api/app/Controllers/PermissionController.php`

**功能說明：**
- 處理所有與權限相關的 HTTP 請求
- 提供權限的 CRUD API 端點

**主要方法：**

#### `get()`
- **路由：** `GET /api/permission/get?module_id={id}`
- **功能：** 取得權限列表（可選：篩選特定模組）
- **查詢參數：** `module_id`（可選）
- **回應：** JSON 格式的權限陣列

#### `getById()`
- **路由：** `GET /api/permission/get-by-id?id={id}`
- **功能：** 取得單一權限的詳細資料

#### `add()`
- **路由：** `POST /api/permission/add`
- **功能：** 新增權限
- **請求體：**
  ```json
  {
    "name": "product.view",
    "label": "查看產品",
    "description": "可以查看產品列表",
    "module_id": 1,
    "category": "tw",
    "action": "view",
    "status": 1
  }
  ```
- **驗證：** 檢查權限名稱是否已存在

#### `update()`
- **路由：** `POST /api/permission/update`
- **功能：** 更新權限資訊
- **請求體：** 包含 `id` 和要更新的欄位

#### `delete()`
- **路由：** `POST /api/permission/delete`
- **功能：** 刪除權限
- **處理：** 刪除權限（會自動刪除關聯的 `role_permissions` 和 `user_permissions` 記錄）

**特色功能：**
- 支援按模組篩選權限
- 完整的資料驗證和錯誤處理

---

### 8. `api/app/Controllers/AuthController.php`

**功能說明：**
- 處理使用者認證相關的請求
- **擴充功能：** 在登入時載入使用者的角色和權限

**主要方法：**

#### `getUserPermissions($userId)` (protected)
- **功能：** 計算使用者的所有權限
- **邏輯流程：**
  1. 從使用者擁有的所有角色中收集權限
  2. 加入直接授予的權限（`user_permissions.is_granted = 1`）
  3. 移除被撤銷的權限（`user_permissions.is_granted = 0`）
  4. 只返回狀態為「啟用」的權限
  5. 返回權限名稱陣列

#### `getUserRoles($userId)` (protected)
- **功能：** 取得使用者的所有角色
- **邏輯：**
  - 使用 JOIN 查詢 `user_roles` 和 `roles` 表
  - 只返回狀態為「啟用」的角色
  - 返回完整的角色資料陣列

#### `login()` (已擴充)
- **路由：** `POST /api/admins/login`
- **原有功能：** 驗證帳號密碼
- **新增功能：**
  - 登入成功後，呼叫 `getUserRoles()` 和 `getUserPermissions()`
  - 將角色和權限資料加入 Session 中的 `admin_user` 物件
  - 前端可以立即使用這些資料進行權限檢查

#### `me()` (已擴充)
- **路由：** `GET /api/admins/me`
- **原有功能：** 取得目前登入的使用者資料
- **新增功能：**
  - 每次請求時重新載入最新的角色和權限
  - 確保權限資料是最新的（即使後台修改了角色或權限）

#### `logout()`
- **路由：** `POST /api/admins/logout`
- **功能：** 清除 Session 並登出

**重要特性：**
- 權限資料會自動載入到 Session 中
- 前端可以從 `user.roles` 和 `user.permissions` 取得權限資訊
- 支援權限的即時更新（每次呼叫 `me()` 都會重新載入）

---

## Composables

### 9. `admin/app/composables/useRole.ts`

**功能說明：**
- 提供角色管理的 Vue Composable
- 封裝所有與角色相關的前端邏輯和 API 呼叫

**主要狀態：**
- `data`: 角色列表（使用 `useState` 共享狀態）
- `loading`: 載入狀態
- `form`: 表單資料（reactive）
- `errors`: 表單驗證錯誤
- `submitError`: 提交錯誤訊息

**主要方法：**

#### `fetchData()`
- **功能：** 從 API 取得所有角色列表
- **API：** `GET /api/role/get`
- **處理：** 更新 `data` 狀態，顯示錯誤提示

#### `fetchById(id)`
- **功能：** 取得單一角色的詳細資料（包含權限 ID 列表）
- **API：** `GET /api/role/get-by-id?id={id}`
- **用途：** 編輯角色時載入現有資料

#### `validateForm()`
- **功能：** 驗證表單資料
- **驗證規則：**
  - `name`: 必填、長度限制、格式驗證（只允許英數字、底線、連字號）
  - `label`: 必填、長度限制
- **回傳：** `boolean`（是否通過驗證）

#### `addRole(options)`
- **功能：** 新增角色
- **API：** `POST /api/role/add`
- **參數：**
  - `closeModalRef`: 關閉模態框的 ref
  - `onSuccess`: 成功回調函數
- **處理：**
  - 驗證表單
  - 發送 API 請求
  - 處理錯誤（欄位錯誤、一般錯誤）
  - 顯示成功/失敗提示

#### `editRole(options)`
- **功能：** 更新角色
- **API：** `POST /api/role/update`
- **參數：** 包含 `id` 和上述選項

#### `deleteRole(options)`
- **功能：** 刪除角色
- **API：** `POST /api/role/delete`
- **參數：** `id` 和 `onSuccess` 回調

#### `resetForm()`
- **功能：** 重置表單為初始狀態

#### `loadFormData(data)`
- **功能：** 載入資料到表單（用於編輯模式）

**使用範例：**
```typescript
const { data, loading, fetchData, addRole } = useRole();

// 取得角色列表
await fetchData();

// 新增角色
await addRole({
  closeModalRef: modalOpen,
  onSuccess: () => fetchData()
});
```

---

### 10. `admin/app/composables/usePermissionData.ts`

**功能說明：**
- 提供權限管理的 Vue Composable
- 封裝所有與權限相關的前端邏輯和 API 呼叫

**主要狀態：**
- `data`: 權限列表（使用 `useState` 共享狀態）
- `loading`: 載入狀態
- `form`: 表單資料（包含 `module_id`, `category`, `action` 等欄位）
- `errors`: 表單驗證錯誤

**主要方法：**

#### `fetchData(moduleId?)`
- **功能：** 取得權限列表（可選：篩選特定模組）
- **API：** `GET /api/permission/get?module_id={id}`
- **參數：** `moduleId`（可選）

#### `fetchById(id)`
- **功能：** 取得單一權限的詳細資料
- **API：** `GET /api/permission/get-by-id?id={id}`

#### `validateForm()`
- **功能：** 驗證表單資料
- **驗證規則：**
  - `name`: 必填、長度限制
  - `label`: 必填、長度限制

#### `addPermission(options)`
- **功能：** 新增權限
- **API：** `POST /api/permission/add`

#### `editPermission(options)`
- **功能：** 更新權限
- **API：** `POST /api/permission/update`

#### `deletePermission(options)`
- **功能：** 刪除權限
- **API：** `POST /api/permission/delete`

**特色功能：**
- 支援按模組篩選權限
- 表單包含完整的權限欄位（模組、分類、動作等）

---

## Types

### 11. `admin/app/types/permission.ts`

**功能說明：**
- 定義 TypeScript 類型，用於權限和角色的資料結構

**定義的類型：**

#### `Permission`
```typescript
{
  id: number;
  name: string;              // 權限代碼（如：product.view）
  label: string;             // 權限顯示名稱
  description?: string;      // 描述（可選）
  module_id?: number | null; // 關聯的模組 ID（可選）
  category?: string | null;  // 分類（可選）
  action?: string | null;    // 動作（可選）
  status: number;            // 狀態：1=啟用, 0=停用
  created_at?: string;       // 建立時間
  updated_at?: string;       // 更新時間
}
```

#### `Role`
```typescript
{
  id: number;
  name: string;              // 角色代碼（如：editor）
  label: string;             // 角色顯示名稱
  description?: string;      // 描述（可選）
  status: number;            // 狀態：1=啟用, 0=停用
  created_at?: string;
  updated_at?: string;
  permissions?: Permission[]; // 關聯的權限陣列（可選）
}
```

**用途：**
- 提供類型安全
- 在 TypeScript 中確保資料結構正確
- IDE 自動完成和類型檢查

---

### 12. `admin/app/types/index.ts`

**功能說明：**
- 統一匯出所有類型定義
- 方便其他檔案匯入使用

**匯出的類型：**
- `Permission`, `Role` - 從 `./permission` 匯出
- `ModuleForm`, `ModuleFormErrors` - 從 `./module` 匯出
- `AdminForm`, `AdminFormErrors` - 從 `./admin` 匯出

**使用方式：**
```typescript
import type { Permission, Role } from '~/types';
```

---

## Pages

### 13. `admin/app/pages/system/roles.vue`

**功能說明：**
- 角色管理頁面
- 顯示角色列表，提供新增、編輯、刪除功能

**主要功能：**

#### 頁面結構
- **標題：** 「角色設定」
- **工具列：** 「新增角色」按鈕
- **表格：** 顯示角色列表（名稱、代碼、狀態、操作）

#### 表格欄位
- `label`: 角色名稱
- `name`: 角色代碼
- `status`: 狀態（啟用/停用）
- `操作`: 編輯、刪除按鈕

#### 主要邏輯
- 使用 `useRole()` composable 管理資料
- 使用 `usePermissionData()` 取得權限列表（用於分配權限）
- 點擊「新增角色」→ 開啟新增模態框
- 點擊「編輯」→ 載入角色資料並開啟編輯模態框
- 點擊「刪除」→ 確認後刪除角色

#### 模態框
- `RoleFrmModal` - 新增/編輯角色的表單模態框

**路由：** `/system/roles`

---

### 14. `admin/app/pages/system/permissions.vue`

**功能說明：**
- 權限管理頁面
- 顯示權限列表，提供新增、編輯、刪除功能
- 支援按模組篩選權限

**主要功能：**

#### 頁面結構
- **標題：** 「權限設定」
- **工具列：**
  - 左側：模組篩選下拉選單
  - 右側：「新增權限」按鈕
- **表格：** 顯示權限列表

#### 表格欄位
- `label`: 權限名稱
- `name`: 權限代碼
- `module_id`: 模組（顯示模組名稱）
- `status`: 狀態（啟用/停用）
- `操作`: 編輯、刪除按鈕

#### 主要邏輯
- 使用 `usePermissionData()` composable 管理資料
- 使用 `useModule()` 取得模組列表（用於篩選和表單）
- 模組篩選：選擇模組後重新載入對應的權限
- 點擊「新增權限」→ 開啟新增模態框
- 點擊「編輯」→ 載入權限資料並開啟編輯模態框
- 點擊「刪除」→ 確認後刪除權限

#### 模態框
- `PermissionFrmModal` - 新增/編輯權限的表單模態框

**路由：** `/system/permissions`

---

## Components

### 15. `admin/app/components/Role/FrmModal.vue`

**功能說明：**
- 角色表單模態框組件
- 支援新增和編輯兩種模式

**Props：**
- `mode`: `"add" | "edit"` - 模式
- `data`: 角色資料（編輯模式時使用）

**Events：**
- `added`: 新增成功時觸發
- `updated`: 更新成功時觸發

**表單欄位：**
1. **角色代碼** (`name`)
   - 必填
   - 格式：只允許英數字、底線、連字號
   - 提示：請輸入角色代碼（英數字、底線、連字號）

2. **角色名稱** (`label`)
   - 必填
   - 提示：請輸入角色名稱

3. **描述** (`description`)
   - 選填
   - 多行文字輸入

4. **狀態** (`status`)
   - 下拉選單：啟用(1) / 停用(0)

5. **權限** (`permission_ids`)
   - 多選 checkbox 列表
   - 顯示所有可用權限
   - 格式：`權限名稱 (權限代碼)`
   - 可滾動區域（最大高度 60）

**主要邏輯：**
- 使用 `useRole()` 管理表單和提交
- 使用 `usePermissionData()` 取得權限列表
- 使用 `useModule()` 取得模組列表（雖然目前未在表單中使用）
- 當模態框開啟時：
  - 如果是編輯模式：載入現有資料
  - 如果是新增模式：重置表單
- 提交時根據模式呼叫 `addRole()` 或 `editRole()`

**UI 特性：**
- 響應式設計
- 表單驗證和錯誤顯示
- 載入狀態指示
- 自動關閉模態框（成功後）

---

### 16. `admin/app/components/Permission/FrmModal.vue`

**功能說明：**
- 權限表單模態框組件
- 支援新增和編輯兩種模式

**Props：**
- `mode`: `"add" | "edit"` - 模式
- `data`: 權限資料（編輯模式時使用）

**Events：**
- `added`: 新增成功時觸發
- `updated`: 更新成功時觸發

**表單欄位：**
1. **權限代碼** (`name`)
   - 必填
   - 提示：例如 `module.view` 或 `module.tw.create`
   - 提示文字：格式：模組.動作 或 模組.分類.動作

2. **權限名稱** (`label`)
   - 必填
   - 提示：請輸入權限顯示名稱

3. **模組** (`module_id`)
   - 選填
   - 下拉選單：顯示所有模組（包含「無」選項）

4. **分類** (`category`)
   - 選填
   - 提示：例如 tw, sg, mm

5. **動作** (`action`)
   - 選填
   - 提示：例如 view, create, edit, delete, manage

6. **描述** (`description`)
   - 選填
   - 多行文字輸入

7. **狀態** (`status`)
   - 下拉選單：啟用(1) / 停用(0)

**主要邏輯：**
- 使用 `usePermissionData()` 管理表單和提交
- 使用 `useModule()` 取得模組列表
- 當模態框開啟時：
  - 載入模組列表
  - 如果是編輯模式：載入現有資料
  - 如果是新增模式：重置表單
- 提交時根據模式呼叫 `addPermission()` 或 `editPermission()`

**UI 特性：**
- 完整的權限欄位支援
- 表單驗證和錯誤顯示
- 載入狀態指示
- 自動關閉模態框（成功後）

---

## Plugins

### 17. `admin/app/plugins/permission-directive.client.ts`

**功能說明：**
- Vue 指令插件
- 註冊 `v-permission` 和 `v-role` 全域指令
- 用於在模板中根據權限/角色顯示或隱藏元素

**註冊的指令：**

#### `v-permission`
- **功能：** 根據權限顯示/隱藏元素
- **使用方式：**
  ```vue
  <UButton v-permission="'product.view'" label="查看產品" />
  <UButton v-permission="['product.view', 'product.edit']" label="編輯產品" />
  ```
- **邏輯：**
  - 如果沒有提供權限值 → 隱藏元素
  - 如果是超級管理員 → 顯示元素（擁有所有權限）
  - 檢查使用者是否有指定權限 → 有則顯示，無則隱藏
  - 支援字串或陣列（陣列時需要擁有所有權限）

#### `v-role`
- **功能：** 根據角色顯示/隱藏元素
- **使用方式：**
  ```vue
  <UButton v-role="'editor'" label="編輯內容" />
  <UButton v-role="['editor', 'admin']" label="管理功能" />
  ```
- **邏輯：**
  - 如果沒有提供角色值 → 隱藏元素
  - 檢查使用者是否有指定角色 → 有則顯示，無則隱藏
  - 支援字串或陣列（陣列時需要擁有所有角色）

**生命週期：**
- `mounted`: 元素掛載時檢查權限/角色
- `updated`: 元素更新時重新檢查（響應式）

**依賴：**
- 使用 `usePermission()` composable 的 `hasPermission()`, `hasRole()`, `isSuperAdmin()` 方法

**注意事項：**
- 這是客戶端插件（`.client.ts`），只在瀏覽器環境中執行
- 使用 `display: none` 隱藏元素（不是移除 DOM）
- 前端權限檢查僅用於 UI 控制，**不能**作為安全防護

**使用範例：**
```vue
<template>
  <!-- 只有擁有 product.view 權限的使用者才能看到 -->
  <UButton
    v-permission="'product.view'"
    label="查看產品"
    @click="viewProducts" />
  
  <!-- 只有擁有 editor 角色的使用者才能看到 -->
  <UButton
    v-role="'editor'"
    label="編輯內容"
    @click="editContent" />
  
  <!-- 需要同時擁有兩個權限 -->
  <UButton
    v-permission="['product.view', 'product.edit']"
    label="編輯產品"
    @click="editProduct" />
</template>
```

---

## 檔案之間的關係

### 資料流

```
前端頁面 (roles.vue / permissions.vue)
    ↓
前端組件 (FrmModal.vue)
    ↓
Composables (useRole.ts / usePermissionData.ts)
    ↓
API 請求 (HTTP)
    ↓
後端 Controllers (RoleController.php / PermissionController.php)
    ↓
後端 Models (RoleModel.php / PermissionModel.php)
    ↓
資料庫 (MySQL/MariaDB)
```

### 權限檢查流程

```
使用者登入
    ↓
AuthController.login()
    ↓
getUserRoles() + getUserPermissions()
    ↓
載入到 Session (user.roles, user.permissions)
    ↓
前端取得使用者資料
    ↓
usePermission() composable
    ↓
v-permission / v-role 指令
    ↓
顯示/隱藏 UI 元素
```

---

## 總結

本 RBAC 系統包含：

1. **後端（PHP）：**
   - 5 個 Model 處理資料庫操作
   - 3 個 Controller 處理 API 請求
   - 完整的 CRUD 功能和權限計算邏輯

2. **前端（TypeScript/Vue）：**
   - 2 個 Composable 封裝業務邏輯
   - 2 個類型定義檔案
   - 2 個管理頁面
   - 2 個表單組件
   - 1 個指令插件

3. **核心特性：**
   - 完整的角色和權限管理
   - 權限的即時計算和更新
   - 前端指令支援（`v-permission`, `v-role`）
   - 表單驗證和錯誤處理
   - 響應式 UI 設計

所有檔案都遵循單一職責原則，職責清晰，易於維護和擴展。
