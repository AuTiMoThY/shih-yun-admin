# 系統選單權限控制

本文件說明系統選單的權限控制實作方式。

## 權限定義

系統選單中的每個項目可以綁定一個權限名稱；只有擁有該權限的使用者才能看到該選單項目。若某項目未綁定權限（`permission` 為空），表示所有登入管理員皆可見。

| 選單項目 | 路由 | 權限名稱 |
|---------|------|---------|
| 管理系統架構 | `/system/structure` | `system.structure.view` |
| 模組設定 | `/system/module` | `system.module.view` |
| 管理員設定 | `/system/admins` | *(未設定，預設所有管理員可見)* |
| 權限設定 | `/system/permissions` | `system.permissions.view` |
| 角色設定 | `/system/roles` | `system.roles.view` |

## 權限邏輯

### 超級管理員 (super_admin)
- **自動擁有所有權限**（`usePermission().isSuperAdmin()` 會直接返回 `true`）
- 可以看到所有系統選單項目，無需額外配置

### 其他角色
- 需要逐項檢查 `permission`：只有具備對應權限才會看到該選單
- 若選單項目未設定 `permission`，則任何登入的管理員都能看到該項目
- 如果所有子項目都被過濾掉，整個「系統設定」區塊不會出現在側欄

## 實作方式

### 1. 選單過濾邏輯

在 `admin/app/constants/menu/system.ts` 中，先定義所有子選單，再透過 `isSuperAdmin` 及 `hasPermission` 進行過濾：

```typescript
// 過濾出有權限的選單項目
const filteredChildren = allMenuItems
    .filter((item) => {
        // super_admin 直接放行
        if (isSuperAdmin()) {
            return true;
        }
        // 其他角色：未設定 permission 的項目照樣顯示；
        // 有設定者必須通過 hasPermission
        return !item.permission || hasPermission(item.permission);
    })
    .map((item) => ({
        label: item.label,
        icon: item.icon,
        to: item.to,
        onSelect: item.onSelect,
    }));
```

### 2. 空選單處理

在 `admin/app/layouts/default.vue` 中，只有當系統選單仍有子項目時才會顯示「系統設定」這個群組：

```typescript
const systemMenuItem = systemMenu.value;
if (systemMenuItem.children && systemMenuItem.children.length > 0) {
    menuItems.push(systemMenuItem);
}
```

若目前登入者沒有任何符合條件的系統子選單，整塊「系統設定」會被排除，側欄看起來就像「沒有權限」。

## SQL 範例

### 建立系統選單權限

```sql
-- 建立系統選單相關權限
INSERT INTO `sys_permissions` (`name`, `label`, `description`, `status`, `module_id`, `category`, `action`)
VALUES
('system.structure.view', '系統架構-查看', '查看系統架構管理頁面', 1, NULL, NULL, 'view'),
('system.module.view', '模組設定-查看', '查看模組設定頁面', 1, NULL, NULL, 'view'),
('system.permissions.view', '權限設定-查看', '查看權限設定頁面', 1, NULL, NULL, 'view'),
('system.roles.view', '角色設定-查看', '查看角色設定頁面', 1, NULL, NULL, 'view');
```

### 為角色分配權限（可選）

如果希望某個角色可以看到部分系統選單，可以將權限分配給該角色：

```sql
-- 將「系統架構查看」和「模組設定查看」權限分配給 admin 角色
INSERT INTO `sys_role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM sys_roles r, sys_permissions p
WHERE r.name = 'admin' 
  AND p.name IN ('system.structure.view', 'system.module.view');
```

### 預設行為

根據目前的實作：
- **super_admin**：不檢查權限，所有系統選單皆顯示
- **其他角色**：僅顯示未設定 `permission` 的選單，或是使用者確實擁有的權限對應項目

如果希望「其他管理員完全看不到任何系統選單」，務必為每一個子項目都設定 `permission`，並且不要將這些權限分配給非 super_admin 的角色。

## 測試步驟

1. **測試超級管理員**
   - 使用 super_admin 角色登入
   - 應該可以看到所有系統選單項目

2. **測試一般管理員**
   - 使用沒有系統權限的角色登入
   - 應該看不到「系統設定」選單項目

3. **測試部分權限**
   - 為某個角色分配部分系統權限（例如：`system.structure.view`）
   - 登入後應該只能看到對應的選單項目

## 注意事項

1. **前端權限檢查僅用於 UI 控制**
   - 隱藏選單只是為了使用者體驗
   - **不能**作為安全防護
   - 所有路由都應該在後端進行權限驗證

2. **後端路由保護（建議）**
   - 在路由中間件中檢查權限
   - 即使前端隱藏了選單，也要防止直接訪問路由

3. **權限命名規範**
   - 使用階層式命名：`模組.資源.動作`
   - 例如：`system.structure.view`、`system.roles.view`
