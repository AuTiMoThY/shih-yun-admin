# 關於我們頁面權限控制範例

本文件說明如何在「關於我們」頁面中實作權限控制，提供兩個角色範例。

## 權限定義

### 需要的權限

| 權限名稱 | 說明 | 控制的功能 |
|---------|------|-----------|
| `about.section.create` | 新增區塊 | 新增區塊按鈕 |
| `about.section.delete` | 刪除區塊 | 刪除區塊按鈕 |
| `about.section.sort` | 區塊排序 | 區塊上移/下移按鈕 |
| `about.field.create` | 新增欄位 | 新增欄位按鈕 |
| `about.field.delete` | 刪除欄位 | 刪除欄位按鈕 |
| `about.field.sort` | 欄位排序 | 欄位上移/下移按鈕 |

## 角色權限配置

### 1. 超級管理員 (super_admin)

**權限：** 擁有所有權限（自動擁有，無需額外配置）

**可使用功能：**
- ✅ 新增區塊
- ✅ 區塊排序（上移/下移）
- ✅ 刪除區塊
- ✅ 新增欄位
- ✅ 欄位排序（上移/下移）
- ✅ 刪除欄位
- ✅ 儲存所有區塊

### 2. 一般管理員 (admin)

**權限：** 僅有查看和編輯權限，沒有管理權限

**可使用功能：**
- ✅ 儲存所有區塊
- ✅ 編輯區塊內容
- ✅ 編輯欄位內容

**不可使用功能：**
- ❌ 新增區塊
- ❌ 區塊排序（上移/下移）
- ❌ 刪除區塊
- ❌ 新增欄位
- ❌ 欄位排序（上移/下移）
- ❌ 刪除欄位

## 實作方式

### 方法 1：使用 v-permission 指令（推薦）

```vue
<template>
  <!-- 只有擁有 about.section.create 權限的使用者才能看到此按鈕 -->
  <UButton
    v-permission="'about.section.create'"
    label="新增區塊(卡)"
    @click="addCutSection" />
</template>
```

### 方法 2：使用 hasPermission() 方法

```vue
<script setup>
const { hasPermission, isSuperAdmin } = usePermission();

const canCreateSection = computed(() => 
  isSuperAdmin() || hasPermission('about.section.create')
);
</script>

<template>
  <UButton
    v-if="canCreateSection"
    label="新增區塊(卡)"
    @click="addCutSection" />
</template>
```

## 已實作的權限控制

### 1. `admin/app/pages/about/index.vue`

**控制的元素：**
- ✅ 「新增區塊(卡)」按鈕（使用 `v-permission="'about.section.create'"`）
- ✅ 區塊排序功能（透過 `canSortSection` 控制 `can-move-up` 和 `can-move-down` props）

### 2. `admin/app/components/About/CutSection.vue`

**控制的元素：**
- ✅ 「上移」按鈕（使用 `v-permission="'about.section.sort'"`）
- ✅ 「下移」按鈕（使用 `v-permission="'about.section.sort'"`）
- ✅ 「新增欄位」按鈕（使用 `v-permission="'about.field.create'"`）
- ✅ 「刪除」按鈕（使用 `v-permission="'about.section.delete'"`）

### 3. `admin/app/components/About/FieldItem.vue`

**控制的元素：**
- ✅ 「上移」按鈕（使用 `v-permission="'about.field.sort'"`）
- ✅ 「下移」按鈕（使用 `v-permission="'about.field.sort'"`）
- ✅ 「刪除」按鈕（使用 `v-permission="'about.field.delete'"`）

## 設定步驟

### 步驟 1：建立權限

在「系統設定」→「權限設定」中建立以下權限：

1. **about.section.create** - 新增區塊
2. **about.section.delete** - 刪除區塊
3. **about.section.sort** - 區塊排序
4. **about.field.create** - 新增欄位
5. **about.field.delete** - 刪除欄位
6. **about.field.sort** - 欄位排序

### 步驟 2：建立角色

#### 超級管理員角色
- 角色名稱：`super_admin`
- 說明：擁有所有權限（系統預設建立）

#### 一般管理員角色
- 角色名稱：`admin`
- 說明：一般管理員，僅有查看和編輯權限
- **不分配任何權限**（或只分配查看相關的權限）

### 步驟 3：為使用者分配角色

在資料庫中為使用者分配角色：

```sql
-- 為使用者 ID 1 分配超級管理員角色
INSERT INTO sys_user_roles (user_id, role_id) 
VALUES (1, (SELECT id FROM sys_roles WHERE name = 'super_admin'));

-- 為使用者 ID 2 分配一般管理員角色
INSERT INTO sys_user_roles (user_id, role_id) 
VALUES (2, (SELECT id FROM sys_roles WHERE name = 'admin'));
```

## 測試驗證

### 測試超級管理員

1. 使用超級管理員帳號登入
2. 進入「關於我們」頁面
3. 確認可以看到所有按鈕：
   - ✅ 新增區塊按鈕
   - ✅ 區塊上移/下移按鈕
   - ✅ 刪除區塊按鈕
   - ✅ 新增欄位按鈕
   - ✅ 欄位上移/下移按鈕
   - ✅ 刪除欄位按鈕

### 測試一般管理員

1. 使用一般管理員帳號登入
2. 進入「關於我們」頁面
3. 確認以下按鈕**不顯示**：
   - ❌ 新增區塊按鈕
   - ❌ 區塊上移/下移按鈕
   - ❌ 刪除區塊按鈕
   - ❌ 新增欄位按鈕
   - ❌ 欄位上移/下移按鈕
   - ❌ 刪除欄位按鈕
4. 確認仍可使用：
   - ✅ 儲存所有區塊按鈕
   - ✅ 編輯區塊和欄位內容

## 權限檢查邏輯

### 超級管理員自動擁有所有權限

```typescript
// 在 usePermission composable 中
const isSuperAdmin = (): boolean => {
  return hasRole('super_admin')
}

// 在權限檢查中
if (isSuperAdmin()) {
  return true; // 超級管理員自動通過所有權限檢查
}
```

### 一般管理員權限檢查

```typescript
// 檢查是否有特定權限
const canCreateSection = computed(() => 
  isSuperAdmin() || hasPermission('about.section.create')
);
```

## 注意事項

1. **前端權限檢查僅用於 UI 控制**
   - 隱藏按鈕只是為了使用者體驗
   - **不能**作為安全防護
   - 所有敏感操作都必須在後端進行權限驗證

2. **後端權限驗證（建議）**
   - 在 API 端點中檢查權限
   - 即使前端隱藏了按鈕，也要防止直接呼叫 API

3. **權限命名規範**
   - 使用階層式命名：`模組.資源.動作`
   - 例如：`about.section.create`、`about.field.delete`

4. **v-permission 指令的運作方式**
   - 沒有權限時：元素會被 comment node 替換，完全從 DOM 中移除
   - 有權限時：元素正常顯示在 DOM 中
   - 權限變更時：元素會自動恢復或移除（響應式）
   - 這與 `v-if` 的行為類似，但以指令的方式實作

## 擴展範例

如果需要更細粒度的權限控制，可以建立更多權限：

- `about.section.view` - 查看區塊
- `about.section.edit` - 編輯區塊
- `about.field.view` - 查看欄位
- `about.field.edit` - 編輯欄位

然後在對應的功能中使用這些權限進行控制。
