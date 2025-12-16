<?php
namespace App\Controllers;

use App\Models\SysAdminModel;
use App\Models\UserRoleModel;
use App\Models\UserPermissionModel;
use CodeIgniter\HTTP\ResponseInterface;

class AdminsController extends BaseController
{
    protected $userModel;
    protected $userRoleModel;
    protected $userPermissionModel;

    public function __construct()
    {
        $this->userModel = new SysAdminModel();
        $this->userRoleModel = new UserRoleModel();
        $this->userPermissionModel = new UserPermissionModel();
    }

    public function addAdmin()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();

        // 將布林/字串狀態轉成 '0'/'1'，避免驗證 in_list[0,1] 失敗
        $truthy = ['1', 1, true, 'true', 'on', 'yes'];
        $status = $data['status'] ?? null;
        $data['status'] = in_array($status, $truthy, true) ? '1' : '0';

        $rules = [
            'permission_name' => 'required',
            'status' => 'required|in_list[0,1]',
            'username' => 'required|min_length[3]|is_unique[sys_admin.username]',
            'password' => 'required',
            'password_confirmation' => 'required|matches[password]',
            'name' => 'required',
            'phone' => 'permit_empty',
            'address' => 'permit_empty',
        ];

        if (!$this->validateData($data, $rules)) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_UNPROCESSABLE_ENTITY)->setJSON([
                'success' => false,
                'message' => '驗證失敗',
                'errors' => $this->validator->getErrors(),
            ]);
        }

        try {
            $insertId = $this->userModel->insert([
                'permission_name' => $data['permission_name'] ?? 'admin', // 保留舊欄位以向後兼容
                'status' => (int) $data['status'],
                'username' => $data['username'],
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
            ]);

            if (!$insertId) {
                // 可能是 DB 拒絕（如 unique key），回傳 500
                return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                    'success' => false,
                    'message' => '新增管理員失敗，請稍後再試',
                ]);
            }

            // 處理角色分配
            if (isset($data['role_ids']) && is_array($data['role_ids'])) {
                foreach ($data['role_ids'] as $roleId) {
                    $this->userRoleModel->insert([
                        'user_id' => $insertId,
                        'role_id' => (int) $roleId,
                    ]);
                }
            }

            // 處理直接權限分配
            if (isset($data['permission_ids']) && is_array($data['permission_ids'])) {
                foreach ($data['permission_ids'] as $permissionId) {
                    $this->userPermissionModel->insert([
                        'user_id' => $insertId,
                        'permission_id' => (int) $permissionId,
                        'is_granted' => 1, // 授予權限
                    ]);
                }
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => '新增管理員成功',
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'addAdmin failed: {message}', ['message' => $e->getMessage()]);

            // 若有 SQL 重複鍵錯誤，可在這裡判斷並回 409
            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '新增管理員失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
    }

    public function getAdmins()
    {
        // 獲取當前登入用戶
        $session = session();
        $currentUser = $session->get('admin_user');
        $isCurrentUserSuperAdmin = false;

        // 檢查當前用戶是否為 super_admin（直接查詢資料庫以確保準確性）
        if ($currentUser && isset($currentUser['id'])) {
            $currentUserRoles = $this->userRoleModel
                ->select('sys_roles.name')
                ->join('sys_roles', 'sys_roles.id = sys_user_roles.role_id')
                ->where('sys_user_roles.user_id', $currentUser['id'])
                ->where('sys_roles.name', 'super_admin')
                ->findAll();
            
            $isCurrentUserSuperAdmin = !empty($currentUserRoles);
        }

        $admins = $this->userModel->findAll();

        // 為每個管理員載入角色資訊
        foreach ($admins as &$admin) {
            $userRoles = $this->userRoleModel
                ->select('sys_user_roles.*, sys_roles.name as role_name, sys_roles.label as role_label')
                ->join('sys_roles', 'sys_roles.id = sys_user_roles.role_id')
                ->where('sys_user_roles.user_id', $admin['id'])
                ->findAll();
            
            $admin['roles'] = array_map(function($ur) {
                return [
                    'id' => $ur['role_id'],
                    'name' => $ur['role_name'],
                    'label' => $ur['role_label'],
                ];
            }, $userRoles);
        }

        // 如果當前用戶不是 super_admin，過濾掉擁有 super_admin 角色的管理員
        if (!$isCurrentUserSuperAdmin) {
            $admins = array_filter($admins, function($admin) {
                // 檢查該管理員是否有 super_admin 角色
                foreach ($admin['roles'] as $role) {
                    if (isset($role['name']) && $role['name'] === 'super_admin') {
                        return false; // 過濾掉這個管理員
                    }
                }
                return true; // 保留這個管理員
            });
            // 重新索引陣列
            $admins = array_values($admins);
        }

        return $this->response->setJSON([
            'success' => true,
            'data' => $admins,
        ]);
    }

    public function getAdminById()
    {
        $id = $this->request->getGet('id');
        if (!$id) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                'success' => false,
                'message' => '缺少管理員 ID',
            ]);
        }

        $admin = $this->userModel->find($id);
        if (!$admin) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_NOT_FOUND)->setJSON([
                'success' => false,
                'message' => '管理員不存在',
            ]);
        }

        // 載入角色 ID 列表
        $userRoles = $this->userRoleModel
            ->where('user_id', $id)
            ->findAll();
        $admin['role_ids'] = array_column($userRoles, 'role_id');

        // 載入直接權限 ID 列表（只包含授予的權限）
        $userPermissions = $this->userPermissionModel
            ->where('user_id', $id)
            ->where('is_granted', 1)
            ->findAll();
        $admin['permission_ids'] = array_column($userPermissions, 'permission_id');

        return $this->response->setJSON([
            'success' => true,
            'data' => $admin,
        ]);
    }

    public function updateAdmin()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();

        $id = $data['id'] ?? null;
        if (!$id) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                'success' => false,
                'message' => '缺少管理員 ID',
            ]);
        }

        // 檢查管理員是否存在
        $admin = $this->userModel->find($id);
        if (!$admin) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_NOT_FOUND)->setJSON([
                'success' => false,
                'message' => '管理員不存在',
            ]);
        }

        // 將布林/字串狀態轉成 '0'/'1'
        $truthy = ['1', 1, true, 'true', 'on', 'yes'];
        $status = $data['status'] ?? null;
        $data['status'] = in_array($status, $truthy, true) ? '1' : '0';

        // 構建驗證規則
        $rules = [
            'permission_name' => 'required',
            'status' => 'in_list[0,1]',
            'username' => "required|min_length[3]|is_unique[sys_admin.username,id,{$id}]",
            'name' => 'required',
            'phone' => 'permit_empty',
            'address' => 'permit_empty',
        ];

        // 如果提供了密碼，則需要驗證
        if (isset($data['password']) && $data['password'] !== '') {
            $rules['password'] = 'required';
            $rules['password_confirmation'] = 'required|matches[password]';
        }

        if (!$this->validateData($data, $rules)) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_UNPROCESSABLE_ENTITY)->setJSON([
                'success' => false,
                'message' => '驗證失敗',
                'errors' => $this->validator->getErrors(),
            ]);
        }

        try {
            $updateData = [
                'permission_name' => $data['permission_name'] ?? 'admin', // 保留舊欄位以向後兼容
                'status' => (int) $data['status'],
                'username' => $data['username'],
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
            ];

            // 只有當密碼有提供時才更新
            if (isset($data['password']) && $data['password'] !== '') {
                $updateData['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            $updated = $this->userModel->update($id, $updateData);

            if (!$updated) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                    'success' => false,
                    'message' => '更新管理員失敗，請稍後再試',
                ]);
            }

            // 處理角色分配：先刪除舊的，再新增新的
            if (isset($data['role_ids']) && is_array($data['role_ids'])) {
                // 刪除舊的角色關聯
                $this->userRoleModel->where('user_id', $id)->delete();
                
                // 新增新的角色關聯
                foreach ($data['role_ids'] as $roleId) {
                    $this->userRoleModel->insert([
                        'user_id' => $id,
                        'role_id' => (int) $roleId,
                    ]);
                }
            }

            // 處理直接權限分配：先刪除舊的，再新增新的
            if (isset($data['permission_ids']) && is_array($data['permission_ids'])) {
                // 刪除舊的權限關聯（只刪除授予的，保留撤銷的）
                $this->userPermissionModel
                    ->where('user_id', $id)
                    ->where('is_granted', 1)
                    ->delete();
                
                // 新增新的權限關聯
                foreach ($data['permission_ids'] as $permissionId) {
                    $this->userPermissionModel->insert([
                        'user_id' => $id,
                        'permission_id' => (int) $permissionId,
                        'is_granted' => 1, // 授予權限
                    ]);
                }
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => '更新管理員成功',
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'updateAdmin failed: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '更新管理員失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
    }

    public function deleteAdmin()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();
        $id = $data['id'] ?? null;
        if (!$id) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                'success' => false,
                'message' => '缺少管理員 ID',
            ]);
        }
        try {
            // 檢查是否只剩下一個管理員帳號
            $totalAdmins = $this->userModel->countAllResults();
            if ($totalAdmins <= 1) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                    'success' => false,
                    'message' => '無法刪除，系統至少需要保留一個管理員帳號',
                ]);
            }
            
            $deleted = $this->userModel->delete($id);
            if (!$deleted) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                    'success' => false,
                    'message' => '刪除管理員失敗，請稍後再試',
                ]);
            }
        }
        catch (\Throwable $e) {
            log_message('error', 'deleteAdmin failed: {message}', ['message' => $e->getMessage()]);
            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '刪除管理員失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
        return $this->response->setJSON([
            'success' => true,
            'message' => '刪除管理員成功',
        ]);
    }
}