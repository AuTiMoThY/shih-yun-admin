# RBAC 資料表前綴更新說明

## 更新摘要

所有 RBAC 相關的資料表已加上 `sys_` 前綴，以保持與系統其他資料表命名的一致性。

## 資料表名稱變更

| 舊名稱 | 新名稱 |
|--------|--------|
| `roles` | `sys_roles` |
| `permissions` | `sys_permissions` |
| `role_permissions` | `sys_role_permissions` |
| `user_roles` | `sys_user_roles` |
| `user_permissions` | `sys_user_permissions` |

## 已更新的檔案

### 1. 資料庫架構檔案
- ✅ `rbac.sql` - 所有 CREATE TABLE、ALTER TABLE、外鍵約束、索引、預設資料

### 2. 後端 Models
- ✅ `api/app/Models/RoleModel.php`
  - `$table = 'sys_roles'`
  - 驗證規則：`is_unique[sys_roles.name,id,{id}]`

- ✅ `api/app/Models/PermissionModel.php`
  - `$table = 'sys_permissions'`
  - 驗證規則：`is_unique[sys_permissions.name,id,{id}]`

- ✅ `api/app/Models/RolePermissionModel.php`
  - `$table = 'sys_role_permissions'`

- ✅ `api/app/Models/UserRoleModel.php`
  - `$table = 'sys_user_roles'`

- ✅ `api/app/Models/UserPermissionModel.php`
  - `$table = 'sys_user_permissions'`

### 3. 後端 Controllers
- ✅ `api/app/Controllers/AuthController.php`
  - JOIN 語句更新：`sys_roles` 和 `sys_user_roles`

## 外鍵約束更新

所有外鍵約束已更新為指向新的表名：

```sql
-- sys_permissions 關聯到 sysmodule
ALTER TABLE `sys_permissions`
  ADD CONSTRAINT `fk_permissions_module` 
  FOREIGN KEY (`module_id`) REFERENCES `sysmodule` (`id`) 
  ON DELETE SET NULL ON UPDATE CASCADE;

-- sys_role_permissions 關聯到 sys_roles 和 sys_permissions
ALTER TABLE `sys_role_permissions`
  ADD CONSTRAINT `fk_role_permissions_role` 
  FOREIGN KEY (`role_id`) REFERENCES `sys_roles` (`id`) 
  ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_role_permissions_permission` 
  FOREIGN KEY (`permission_id`) REFERENCES `sys_permissions` (`id`) 
  ON DELETE CASCADE ON UPDATE CASCADE;

-- sys_user_roles 關聯到 sysadmin 和 sys_roles
ALTER TABLE `sys_user_roles`
  ADD CONSTRAINT `fk_user_roles_user` 
  FOREIGN KEY (`user_id`) REFERENCES `sysadmin` (`id`) 
  ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_roles_role` 
  FOREIGN KEY (`role_id`) REFERENCES `sys_roles` (`id`) 
  ON DELETE CASCADE ON UPDATE CASCADE;

-- sys_user_permissions 關聯到 sysadmin 和 sys_permissions
ALTER TABLE `sys_user_permissions`
  ADD CONSTRAINT `fk_user_permissions_user` 
  FOREIGN KEY (`user_id`) REFERENCES `sysadmin` (`id`) 
  ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_permissions_permission` 
  FOREIGN KEY (`permission_id`) REFERENCES `sys_permissions` (`id`) 
  ON DELETE CASCADE ON UPDATE CASCADE;
```

## 不需要修改的檔案

以下檔案中的 `roles` 和 `permissions` 是變數名稱或 API 回應欄位，不是資料表名稱，因此不需要修改：

- `api/app/Controllers/AuthController.php` - 變數名稱 `$roles` 和 `$permissions`
- 前端檔案 - 所有前端檔案都透過 API 存取，不直接使用資料表名稱

## 遷移步驟

### 如果資料庫中已有舊表名

1. **備份資料庫**
   ```sql
   -- 備份現有資料
   CREATE TABLE sys_roles_backup AS SELECT * FROM roles;
   CREATE TABLE sys_permissions_backup AS SELECT * FROM permissions;
   CREATE TABLE sys_role_permissions_backup AS SELECT * FROM role_permissions;
   CREATE TABLE sys_user_roles_backup AS SELECT * FROM user_roles;
   CREATE TABLE sys_user_permissions_backup AS SELECT * FROM user_permissions;
   ```

2. **重新命名資料表**
   ```sql
   RENAME TABLE roles TO sys_roles;
   RENAME TABLE permissions TO sys_permissions;
   RENAME TABLE role_permissions TO sys_role_permissions;
   RENAME TABLE user_roles TO sys_user_roles;
   RENAME TABLE user_permissions TO sys_user_permissions;
   ```

3. **重新建立外鍵約束**
   - 執行 `rbac.sql` 中的外鍵約束部分（如果外鍵已存在，需要先刪除再重新建立）

### 如果是全新安裝

直接執行更新後的 `rbac.sql` 檔案即可。

## 驗證

更新後，請確認：

1. ✅ 所有 Models 的 `$table` 屬性已更新
2. ✅ 所有驗證規則中的 `is_unique` 已更新
3. ✅ AuthController 中的 JOIN 語句已更新
4. ✅ 資料庫中的表名已更新
5. ✅ 外鍵約束正確指向新表名

## 注意事項

- 如果資料庫中已有資料，請務必先備份
- 重新命名表後，需要重新建立外鍵約束
- 前端代碼不需要修改（透過 API 存取）
- 日誌檔案中的舊表名是歷史記錄，不需要修改
