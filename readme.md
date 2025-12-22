測試系統
https://test-sys.srl.tw/admin/

超級管理員帳密
test / test

一般管理員帳密
test1 / test1

# 現有功能:
## 系統相關
- 管理員設定
- 系統架構設定
- 模組設定
- 權限設定
- 角色設定

## 單元相關
- 關於我們
- 最新消息
- 聯絡我們
- 公司資本資料設定


# RBAC相關
rbac.sql
api\app\Models\RoleModel.php
api\app\Models\PermissionModel.php
api\app\Models\RolePermissionModel.php
api\app\Models\UserRoleModel.php
api\app\Models\UserPermissionModel.php
api\app\Controllers\RoleController.php
api\app\Controllers\PermissionController.php
api\app\Controllers\AuthController.php
admin\app\composables\useRole.ts
admin\app\composables\usePermissionData.ts
admin\app\types\permission.ts
admin\app\types\index.ts
admin\app\pages\system\roles.vue
admin\app\pages\system\permissions.vue
admin\app\components\Role\FrmModal.vue
admin\app\components\Permission\FrmModal.vue
admin\app\plugins\permission-directive.client.ts
