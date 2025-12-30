測試系統 Alpha 1.0
https://test-sys.srl.tw/admin/

超級管理員帳密
ucyadmin / ucyadmin

一般管理員帳密
admin / admin

# 現有功能:
## 系統相關
- 公司資本資料設定
- 管理員設定
- 模組設定
- 系統架構設定 (自動加入基本權限)
- 權限設定
- 角色設定

## 單元相關
- 關於我們
- 最新消息 (含預覽功能)
- 聯絡我們 (含寄信功能)
- 建案 (含預覽功能)
- 工程進度



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

# 專案遷移與初始化

## 遷移到其他專案
- [專案遷移指南](docs/migration-guide.md) - 完整的遷移步驟說明

## 資料庫初始化
- [乾淨初始化 SQL](docs/init/init-clean.sql) - 不含測試資料的初始化文件
- [包含預設管理員的初始化 SQL](docs/init/init-with-super-admin.sql) - 包含預設超級管理員帳號
- [預設管理員建議](docs/init/default-admin-recommendations.md) - 關於是否包含預設管理員的詳細說明