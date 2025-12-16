# sysadmin 資料表遷移指南

## 現況分析

### 目前 `sysadmin` 表的結構
- `permission_name` 欄位：`varchar(50) NOT NULL` - 舊的簡單權限系統
- 新 RBAC 系統使用 `user_roles` 和 `user_permissions` 表

### `permission_name` 欄位的使用情況
1. ✅ **仍在使用中：**
   - `AdminsController.php` - 新增/更新管理員時需要
   - `Addadmin.vue` / `Editadmin.vue` - 前端表單
   - `admins.vue` - 顯示管理員列表
   - `AuthController.php` - 登入時加入 Session

2. ✅ **新 RBAC 系統：**
   - 使用 `user_roles` 和 `user_permissions` 表
   - 權限檢查使用 `usePermission()` composable

## 建議方案

### 方案 1：保留 `permission_name` 作為備用欄位（推薦）

**優點：**
- ✅ 不破壞現有功能
- ✅ 向後相容
- ✅ 可以逐步遷移
- ✅ 新舊系統可以並存

**缺點：**
- ⚠️ 有兩個權限系統並存（可能造成混淆）

**實作方式：**
- 保持 `permission_name` 欄位不變
- 新使用者可以同時使用 `permission_name` 和 RBAC 系統
- 權限檢查優先使用 RBAC 系統，`permission_name` 作為備用

### 方案 2：將 `permission_name` 改為可選（允許 NULL）

**優點：**
- ✅ 允許新使用者只使用 RBAC 系統
- ✅ 保持向後相容（現有資料不受影響）
- ✅ 可以逐步遷移

**缺點：**
- ⚠️ 需要修改驗證規則和前端表單

**實作方式：**
- 修改資料表結構：`permission_name` 改為 `NULL`
- 修改驗證規則：`permission_name` 改為可選
- 修改前端表單：`permission_name` 改為可選欄位

### 方案 3：完全移除 `permission_name`（不推薦）

**優點：**
- ✅ 統一使用新的 RBAC 系統
- ✅ 避免混淆

**缺點：**
- ❌ 需要大量修改現有代碼
- ❌ 可能破壞現有功能
- ❌ 需要遷移現有資料

## 推薦實作：方案 1（保留作為備用）

### 理由
1. **最小風險**：不影響現有功能
2. **向後相容**：現有代碼繼續運作
3. **靈活性**：可以同時使用兩個系統
4. **逐步遷移**：可以慢慢將舊系統遷移到新系統

### 不需要修改 `sysadmin` 表

**結論：`sysadmin` 表不需要修改！**

現有的 `sysadmin` 表結構已經可以與新的 RBAC 系統完美配合：
- ✅ `id` 欄位作為外鍵關聯到 `user_roles` 和 `user_permissions`
- ✅ `permission_name` 欄位保留作為備用或向後相容
- ✅ 其他欄位（`username`, `password_hash`, `status` 等）都正常運作

## 如果選擇方案 2（改為可選）

### SQL 遷移腳本

```sql
-- 將 permission_name 改為可選（允許 NULL）
ALTER TABLE `sysadmin` 
MODIFY COLUMN `permission_name` varchar(50) NULL COMMENT '權限名稱（已棄用，請使用 RBAC 系統）';

-- 更新現有資料（可選：將空字串改為 NULL）
UPDATE `sysadmin` 
SET `permission_name` = NULL 
WHERE `permission_name` = '' OR `permission_name` IS NULL;
```

### 需要修改的檔案

#### 1. 後端驗證規則

**檔案：** `api/app/Controllers/AdminsController.php`

```php
// 修改前
'permission_name' => 'required',

// 修改後
'permission_name' => 'permit_empty',
```

#### 2. 前端表單

**檔案：** `admin/app/components/Admins/Addadmin.vue` 和 `Editadmin.vue`

```vue
<!-- 修改前 -->
<UFormField
    label="權限名稱"
    name="permission_name"
    required>

<!-- 修改後 -->
<UFormField
    label="權限名稱（選填，建議使用角色管理）"
    name="permission_name">
```

#### 3. 預設值處理

```typescript
// 修改前
permission_name: "admin",

// 修改後
permission_name: null, // 或 ""
```

## 權限檢查邏輯建議

### 建議的權限檢查優先順序

```php
// 在 AuthController 或權限檢查邏輯中
function checkUserPermission($userId, $permission) {
    // 1. 優先檢查 RBAC 系統
    if (hasRBACPermission($userId, $permission)) {
        return true;
    }
    
    // 2. 備用：檢查舊的 permission_name（如果需要）
    // 這部分可以保留作為向後相容
    // if (hasLegacyPermission($userId, $permission)) {
    //     return true;
    // }
    
    return false;
}
```

## 遷移步驟（如果選擇方案 2）

### 步驟 1：執行 SQL 遷移
```sql
ALTER TABLE `sysadmin` 
MODIFY COLUMN `permission_name` varchar(50) NULL;
```

### 步驟 2：修改後端驗證規則
- 修改 `AdminsController.php` 中的驗證規則

### 步驟 3：修改前端表單
- 修改 `Addadmin.vue` 和 `Editadmin.vue`
- 將 `permission_name` 改為可選欄位

### 步驟 4：測試
- 測試新增管理員（不填寫 `permission_name`）
- 測試編輯管理員
- 測試登入功能
- 測試權限檢查

### 步驟 5：資料遷移（可選）
- 為現有使用者建立角色和權限關聯
- 可以寫一個遷移腳本自動處理

## 最終建議

**建議採用方案 1：保留 `permission_name` 欄位不變**

理由：
1. ✅ **零風險**：不需要修改任何代碼
2. ✅ **向後相容**：現有功能完全正常
3. ✅ **靈活性**：可以同時使用兩個系統
4. ✅ **新系統優先**：新的 RBAC 系統已經可以正常運作
5. ✅ **逐步遷移**：未來可以慢慢將舊系統遷移到新系統

**結論：`sysadmin` 表不需要任何修改！**

新的 RBAC 系統已經可以正常運作，`permission_name` 欄位可以保留作為備用或向後相容使用。
