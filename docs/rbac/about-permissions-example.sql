-- 1) 權限表：建立 6 個權限
INSERT INTO `sys_permissions` (`name`, `label`, `description`, `status`, `module_id`, `category`, `action`)
VALUES
('about.section.create', '關於我們-新增區塊', '新增區塊(卡)', 1, NULL, NULL, 'create'),
('about.section.delete', '關於我們-刪除區塊', '刪除區塊(卡)', 1, NULL, NULL, 'delete'),
('about.section.sort',   '關於我們-區塊排序', '區塊上移/下移', 1, NULL, NULL, 'sort'),
('about.field.create',   '關於我們-新增欄位', '新增欄位', 1, NULL, NULL, 'create'),
('about.field.delete',   '關於我們-刪除欄位', '刪除欄位', 1, NULL, NULL, 'delete'),
('about.field.sort',     '關於我們-欄位排序', '欄位上移/下移', 1, NULL, NULL, 'sort');

-- 2) 角色表：建立一般管理員角色（若尚未建立）
-- INSERT INTO `sys_roles` (`name`, `label`, `description`, `status`)
-- VALUES ('admin', '一般管理員', '僅可編輯，不可管理區塊/欄位', 1);

-- 3) 超級管理員 (super_admin) 不需手動綁定，預設擁有所有權限
-- 若已存在 super_admin 角色，可略過。若需要：
-- INSERT INTO `sys_roles` (`name`, `label`, `description`, `status`)
-- VALUES ('super_admin', '超級管理員', '擁有所有權限', 1);

-- 4) 角色權限關聯：將上述 6 個權限分配給一般管理員「可視需求選擇性分配」。
-- 此例示範「不分配」管理權限給 admin（僅查看/編輯），所以可以不插入。
-- 若要分配，請改用 SELECT 權限 id 填入：
INSERT INTO `sys_role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM sys_roles r, sys_permissions p
WHERE r.name = 'admin' AND p.name IN (
  'about.section.create', 'about.section.delete', 'about.section.sort', 'about.field.create', 'about.field.delete', 'about.field.sort'
);

-- 5) 使用者角色關聯：將 super_admin、admin 指派給使用者 (以 user_id 1/2 為例)
-- 將 user_id=1 設為 super_admin
INSERT INTO `sys_user_roles` (`user_id`, `role_id`)
SELECT 1, id FROM `sys_roles` WHERE `name` = 'super_admin';

-- 將 user_id=2 設為一般管理員 (無管理權限)
INSERT INTO `sys_user_roles` (`user_id`, `role_id`)
SELECT 2, id FROM `sys_roles` WHERE `name` = 'admin';