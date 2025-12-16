# RBAC (Role-Based Access Control) 權限管理系統說明文件

## 目錄

1. [系統概述](#系統概述)
2. [資料庫架構](#資料庫架構)
3. [權限命名規範](#權限命名規範)
4. [權限檢查機制](#權限檢查機制)
5. [使用方式](#使用方式)
6. [API 端點](#api-端點)
7. [前端使用範例](#前端使用範例)
8. [最佳實踐](#最佳實踐)

---

## 系統概述

本系統採用標準的 RBAC (Role-Based Access Control) 模型，實現了以下功能：

- **角色管理**：建立、編輯、刪除角色
- **權限管理**：建立、編輯、刪除權限
- **角色權限關聯**：為角色分配權限
- **使用者角色關聯**：為使用者分配角色
- **使用者權限關聯**：直接為使用者授予或撤銷特定權限
- **權限檢查**：前端和後端都可進行權限檢查

### 運作方式

**核心原則：若有權限就顯示該功能，若無權限就不顯示。**

系統會自動檢查使用者的權限，並根據權限決定是否顯示功能按鈕、選單項目等 UI 元素。

---

## 資料庫架構

### 資料表結構

#### 1. `roles` - 角色表

| 欄位 | 類型 | 說明 |
|------|------|------|
| id | bigint | 主鍵 |
| name | varchar(100) | 角色名稱（唯一，用於程式碼） |
| label | varchar(255) | 角色顯示名稱 |
| description | text | 角色描述 |
| status | tinyint(1) | 狀態：1=啟用, 0=停用 |
| created_at | timestamp | 建立時間 |
| updated_at | timestamp | 更新時間 |

#### 2. `permissions` - 權限表

| 欄位 | 類型 | 說明 |
|------|------|------|
| id | bigint | 主鍵 |
| name | varchar(255) | 權限名稱（唯一，格式：module.action 或 module.category.action） |
| label | varchar(255) | 權限顯示名稱 |
| description | text | 權限描述 |
| module_id | bigint | 關聯的模組 ID（可選，外鍵關聯 sysmodule） |
| category | varchar(50) | 分類（如：tw, sg, mm） |
| action | varchar(50) | 動作（如：view, create, edit, delete, manage） |
| status | tinyint(1) | 狀態：1=啟用, 0=停用 |
| created_at | timestamp | 建立時間 |
| updated_at | timestamp | 更新時間 |

#### 3. `role_permissions` - 角色權限關聯表

| 欄位 | 類型 | 說明 |
|------|------|------|
| id | bigint | 主鍵 |
| role_id | bigint | 角色 ID（外鍵） |
| permission_id | bigint | 權限 ID（外鍵） |
| created_at | timestamp | 建立時間 |
| updated_at | timestamp | 更新時間 |

**唯一約束**：`(role_id, permission_id)` 組合必須唯一

#### 4. `user_roles` - 使用者角色關聯表

| 欄位 | 類型 | 說明 |
|------|------|------|
| id | bigint | 主鍵 |
| user_id | bigint | 使用者 ID（外鍵，關聯 sys_admin.id） |
| role_id | bigint | 角色 ID（外鍵） |
| created_at | timestamp | 建立時間 |
| updated_at | timestamp | 更新時間 |

**唯一約束**：`(user_id, role_id)` 組合必須唯一

#### 5. `user_permissions` - 使用者權限關聯表（直接授予或撤銷）

| 欄位 | 類型 | 說明 |
|------|------|------|
| id | bigint | 主鍵 |
| user_id | bigint | 使用者 ID（外鍵，關聯 sys_admin.id） |
| permission_id | bigint | 權限 ID（外鍵） |
| is_granted | tinyint(1) | 是否授予：1=授予, 0=撤銷 |
| created_at | timestamp | 建立時間 |
| updated_at | timestamp | 更新時間 |

**唯一約束**：`(user_id, permission_id)` 組合必須唯一

### 關聯關係

```
sys_admin (使用者)
  ├── user_roles (使用者角色)
  │     └── roles (角色)
  │           └── role_permissions (角色權限)
  │                 └── permissions (權限)
  └── user_permissions (使用者權限)
        └── permissions (權限)

permissions (權限)
  └── module_id → sysmodule (模組)
```

### 外鍵約束

- `permissions.module_id` → `sysmodule.id` (ON DELETE SET NULL)
- `role_permissions.role_id` → `roles.id` (ON DELETE CASCADE)
- `role_permissions.permission_id` → `permissions.id` (ON DELETE CASCADE)
- `user_roles.user_id` → `sys_admin.id` (ON DELETE CASCADE)
- `user_roles.role_id` → `roles.id` (ON DELETE CASCADE)
- `user_permissions.user_id` → `sys_admin.id` (ON DELETE CASCADE)
- `user_permissions.permission_id` → `permissions.id` (ON DELETE CASCADE)

---

## 權限命名規範

### 基本格式

權限名稱採用階層式命名，格式如下：

```
{模組}.{動作}
或
{模組}.{分類}.{動作}
```

### 範例

#### 基本權限
- `module.view` - 查看模組
- `module.create` - 建立模組
- `module.edit` - 編輯模組
- `module.delete` - 刪除模組
- `module.manage` - 管理模組（包含所有操作）

#### 帶分類的權限
- `product.tw.view` - 查看台灣產品
- `product.tw.create` - 建立台灣產品
- `product.sg.view` - 查看新加坡產品
- `product.mm.manage` - 管理緬甸產品

### 常用動作名稱

| 動作 | 說明 |
|------|------|
| `view` | 查看 |
| `create` | 建立 |
| `edit` | 編輯 |
| `delete` | 刪除 |
| `manage` | 管理（通常包含所有操作） |

### 常用分類名稱

| 分類 | 說明 |
|------|------|
| `tw` | 台灣 |
| `sg` | 新加坡 |
| `mm` | 緬甸 |

---

## 權限檢查機制

### 權限計算邏輯

使用者的最終權限由以下來源組成：

1. **從角色獲取的權限**
   - 使用者擁有的所有角色
   - 每個角色關聯的所有權限

2. **直接授予的權限** (`user_permissions.is_granted = 1`)
   - 直接授予使用者的權限

3. **撤銷的權限** (`user_permissions.is_granted = 0`)
   - 從角色權限中撤銷的特定權限（優先級最高）

### 權限檢查流程

```
1. 檢查是否為超級管理員 (super_admin)
   └─ 是 → 返回 true（擁有所有權限）

2. 收集使用者所有角色的權限
   └─ 從 role_permissions 表獲取

3. 收集直接授予的權限
   └─ 從 user_permissions 表獲取 (is_granted = 1)

4. 移除被撤銷的權限
   └─ 從 user_permissions 表獲取 (is_granted = 0)

5. 合併並去重權限列表

6. 檢查目標權限是否存在於最終權限列表中
```

### 超級管理員

擁有 `super_admin` 角色的使用者會自動擁有所有權限，無需額外檢查。

---

## 使用方式

### 1. 初始化資料庫

執行 SQL 檔案建立資料表：

```sql
-- 執行 rbac.sql
source rbac.sql;
```

### 2. 建立角色

1. 進入「系統設定」→「角色設定」
2. 點擊「新增角色」
3. 填寫角色資訊：
   - 角色代碼：`editor`（英數字、底線、連字號）
   - 角色名稱：`編輯者`
   - 描述：`可以編輯內容的使用者`
   - 狀態：`啟用`
4. 選擇要分配的權限
5. 儲存

### 3. 建立權限

1. 進入「系統設定」→「權限設定」
2. 點擊「新增權限」
3. 填寫權限資訊：
   - 權限代碼：`product.view`
   - 權限名稱：`查看產品`
   - 模組：選擇對應模組（可選）
   - 分類：`tw`（可選）
   - 動作：`view`（可選）
   - 狀態：`啟用`
4. 儲存

### 4. 為使用者分配角色

（此功能需要在管理員管理頁面中實作）

---

## API 端點

### 角色相關

#### 取得所有角色
```
GET /api/role/get
```

**回應：**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "super_admin",
      "label": "超級管理員",
      "description": "擁有所有權限",
      "status": 1
    }
  ]
}
```

#### 取得單一角色（含權限）
```
GET /api/role/get-by-id?id=1
```

**回應：**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "editor",
    "label": "編輯者",
    "permission_ids": [1, 2, 3]
  }
}
```

#### 新增角色
```
POST /api/role/add
```

**請求體：**
```json
{
  "name": "editor",
  "label": "編輯者",
  "description": "可以編輯內容",
  "status": 1,
  "permission_ids": [1, 2, 3]
}
```

#### 更新角色
```
POST /api/role/update
```

**請求體：**
```json
{
  "id": 1,
  "name": "editor",
  "label": "編輯者",
  "description": "可以編輯內容",
  "status": 1,
  "permission_ids": [1, 2, 3, 4]
}
```

#### 刪除角色
```
POST /api/role/delete
```

**請求體：**
```json
{
  "id": 1
}
```

### 權限相關

#### 取得所有權限
```
GET /api/permission/get?module_id=1
```

**查詢參數：**
- `module_id`（可選）：篩選特定模組的權限

**回應：**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "product.view",
      "label": "查看產品",
      "module_id": 1,
      "category": "tw",
      "action": "view",
      "status": 1
    }
  ]
}
```

#### 取得單一權限
```
GET /api/permission/get-by-id?id=1
```

#### 新增權限
```
POST /api/permission/add
```

**請求體：**
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

#### 更新權限
```
POST /api/permission/update
```

**請求體：**
```json
{
  "id": 1,
  "name": "product.view",
  "label": "查看產品",
  "status": 1
}
```

#### 刪除權限
```
POST /api/permission/delete
```

**請求體：**
```json
{
  "id": 1
}
```

---

## 前端使用範例

### 1. 使用 Composables

#### 檢查權限

```vue
<script setup>
const { hasPermission, hasRole, isSuperAdmin } = usePermission();

// 檢查單一權限
if (hasPermission('product.view')) {
  // 有權限
}

// 檢查多個權限（全部需要）
if (hasPermission(['product.view', 'product.edit'])) {
  // 同時擁有兩個權限
}

// 檢查任一權限
if (hasAnyPermission(['product.view', 'product.edit'])) {
  // 擁有其中一個權限即可
}

// 檢查角色
if (hasRole('editor')) {
  // 擁有編輯者角色
}

// 檢查是否為超級管理員
if (isSuperAdmin()) {
  // 是超級管理員
}
</script>
```

### 2. 使用指令（Directives）

#### v-permission 指令

```vue
<template>
  <!-- 只有擁有 product.view 權限的使用者才能看到此按鈕 -->
  <UButton
    v-permission="'product.view'"
    label="查看產品"
    @click="viewProducts" />

  <!-- 檢查多個權限（全部需要） -->
  <UButton
    v-permission="['product.view', 'product.edit']"
    label="編輯產品"
    @click="editProducts" />
</template>
```

#### v-role 指令

```vue
<template>
  <!-- 只有擁有 editor 角色的使用者才能看到此按鈕 -->
  <UButton
    v-role="'editor'"
    label="編輯內容"
    @click="editContent" />
</template>
```

### 3. 條件渲染

```vue
<template>
  <div>
    <UButton
      v-if="hasPermission('product.create')"
      label="新增產品"
      @click="createProduct" />
    
    <UButton
      v-if="hasPermission('product.edit')"
      label="編輯產品"
      @click="editProduct" />
    
    <UButton
      v-if="hasPermission('product.delete')"
      label="刪除產品"
      @click="deleteProduct" />
  </div>
</template>

<script setup>
const { hasPermission } = usePermission();
</script>
```

### 4. 在選單中使用

```vue
<template>
  <nav>
    <NuxtLink
      v-permission="'product.view'"
      to="/products">
      產品管理
    </NuxtLink>
    
    <NuxtLink
      v-permission="'order.view'"
      to="/orders">
      訂單管理
    </NuxtLink>
  </nav>
</template>
```

---

## 最佳實踐

### 1. 權限命名

- ✅ **好的命名**：`product.view`, `product.tw.create`, `order.manage`
- ❌ **不好的命名**：`view_product`, `createTWProduct`, `order123`

### 2. 角色設計

- 建立通用角色：`admin`（管理員）、`editor`（編輯者）、`viewer`（查看者）
- 避免為每個使用者建立專屬角色
- 使用角色來管理一組相關權限

### 3. 權限粒度

- 建議使用細粒度權限（view, create, edit, delete）
- 避免過於寬泛的權限（如只有 `manage`）
- 根據業務需求調整權限粒度

### 4. 權限檢查時機

- **前端檢查**：用於 UI 顯示/隱藏（使用者體驗）
- **後端檢查**：用於實際操作驗證（安全性）

⚠️ **重要**：前端權限檢查僅用於 UI 控制，**不能**作為安全防護。所有敏感操作都必須在後端進行權限驗證。

### 5. 超級管理員

- 預設建立 `super_admin` 角色
- 超級管理員擁有所有權限，無需額外配置
- 建議僅將此角色分配給系統管理員

### 6. 權限撤銷

- 使用 `user_permissions` 表的 `is_granted = 0` 來撤銷特定使用者的權限
- 這對於需要從角色權限中排除特定使用者的情況很有用

### 7. 測試建議

- 建立測試角色和測試使用者
- 測試各種權限組合
- 驗證前端和後端的權限檢查是否一致

---

## 常見問題

### Q: 如何為使用者分配角色？

A: 目前需要在資料庫中手動操作，或透過管理員管理頁面實作此功能。

### Q: 權限檢查不生效？

A: 請確認：
1. 使用者已登入
2. 使用者的角色和權限已正確載入
3. 權限名稱拼寫正確
4. 權限狀態為「啟用」

### Q: 如何撤銷使用者的特定權限？

A: 在 `user_permissions` 表中新增記錄，設定 `is_granted = 0`。

### Q: 可以刪除超級管理員角色嗎？

A: 不可以。系統會阻止刪除 `super_admin` 角色。

---

## 更新日誌

- **2025-12-10**：初始版本，建立完整的 RBAC 系統

---

## 相關檔案

- 資料庫架構：`rbac.sql`
- 後端 Models：`api/app/Models/RoleModel.php`, `PermissionModel.php` 等
- 後端 Controllers：`api/app/Controllers/RoleController.php`, `PermissionController.php`
- 前端 Composables：`admin/app/composables/useRole.ts`, `usePermissionData.ts`, `usePermission.ts`
- 前端頁面：`admin/app/pages/system/roles.vue`, `permissions.vue`
- 前端指令：`admin/app/plugins/permission-directive.client.ts`
