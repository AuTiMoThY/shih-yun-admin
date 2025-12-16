# RBAC 權限管理系統 - 快速開始指南

## 安裝步驟

### 1. 執行資料庫遷移

在資料庫中執行 `rbac.sql` 檔案：

```sql
source rbac.sql;
```

或使用 phpMyAdmin 匯入 `rbac.sql` 檔案。

### 2. 驗證安裝

登入後台系統，確認以下頁面可以正常訪問：
- 系統設定 → 角色設定 (`/system/roles`)
- 系統設定 → 權限設定 (`/system/permissions`)

## 基本使用流程

### 步驟 1：建立權限

1. 進入「系統設定」→「權限設定」
2. 點擊「新增權限」
3. 填寫權限資訊：
   - **權限代碼**：例如 `product.view`
   - **權限名稱**：例如 `查看產品`
   - **模組**：選擇對應模組（可選）
   - **狀態**：啟用
4. 儲存

### 步驟 2：建立角色

1. 進入「系統設定」→「角色設定」
2. 點擊「新增角色」
3. 填寫角色資訊：
   - **角色代碼**：例如 `editor`
   - **角色名稱**：例如 `編輯者`
   - **狀態**：啟用
4. 在「權限」區塊中，勾選要分配給此角色的權限
5. 儲存

### 步驟 3：為使用者分配角色

目前需要在資料庫中手動操作：

```sql
-- 為使用者 ID 1 分配角色 ID 2
INSERT INTO user_roles (user_id, role_id) VALUES (1, 2);
```

或透過管理員管理頁面實作此功能。

## 在前端使用權限檢查

### 方法 1：使用指令（推薦）

```vue
<template>
  <!-- 只有擁有 product.view 權限的使用者才能看到此按鈕 -->
  <UButton
    v-permission="'product.view'"
    label="查看產品"
    @click="viewProducts" />
</template>
```

### 方法 2：使用 Composables

```vue
<script setup>
const { hasPermission } = usePermission();

// 在邏輯中使用
if (hasPermission('product.view')) {
  // 有權限的邏輯
}
</script>

<template>
  <UButton
    v-if="hasPermission('product.view')"
    label="查看產品"
    @click="viewProducts" />
</template>
```

## 權限命名範例

### 基本權限
- `product.view` - 查看產品
- `product.create` - 建立產品
- `product.edit` - 編輯產品
- `product.delete` - 刪除產品

### 帶分類的權限
- `product.tw.view` - 查看台灣產品
- `product.sg.create` - 建立新加坡產品
- `order.mm.manage` - 管理緬甸訂單

## 常見問題

### Q: 為什麼我的按鈕不顯示？

A: 請確認：
1. 使用者已登入
2. 使用者有對應的角色
3. 角色有分配對應的權限
4. 權限名稱拼寫正確

### Q: 如何測試權限功能？

A: 
1. 建立測試角色和測試權限
2. 為測試使用者分配角色
3. 使用不同權限的使用者登入測試

### Q: 超級管理員需要設定權限嗎？

A: 不需要。擁有 `super_admin` 角色的使用者自動擁有所有權限。

## 相關文件

詳細說明請參考：[RBAC 完整說明文件](./rbac-guide.md)
