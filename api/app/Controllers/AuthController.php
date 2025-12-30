<?php
namespace App\Controllers;

use App\Models\SysAdminModel;
use App\Models\UserRoleModel;
use App\Models\UserPermissionModel;
use App\Models\RoleModel;
use App\Models\PermissionModel;
use App\Models\RolePermissionModel;
use CodeIgniter\HTTP\ResponseInterface;

class AuthController extends BaseController
{
    protected $userModel;
    protected $userRoleModel;
    protected $userPermissionModel;
    protected $roleModel;
    protected $permissionModel;
    protected $rolePermissionModel;

    public function __construct()
    {
        $this->userModel = new SysAdminModel();
        $this->userRoleModel = new UserRoleModel();
        $this->userPermissionModel = new UserPermissionModel();
        $this->roleModel = new RoleModel();
        $this->permissionModel = new PermissionModel();
        $this->rolePermissionModel = new RolePermissionModel();
    }

    /**
     * 取得使用者的所有權限（從角色和直接授予的權限）
     */
    protected function getUserPermissions($userId)
    {
        $permissions = [];
        $permissionIds = [];

        // 從角色獲取權限
        $userRoles = $this->userRoleModel->where('user_id', $userId)->findAll();
        foreach ($userRoles as $userRole) {
            $rolePermissions = $this->rolePermissionModel
                ->where('role_id', $userRole['role_id'])
                ->findAll();
            foreach ($rolePermissions as $rp) {
                if (!in_array($rp['permission_id'], $permissionIds)) {
                    $permissionIds[] = $rp['permission_id'];
                }
            }
        }

        // 從直接授予的權限獲取（is_granted = 1）
        $directPermissions = $this->userPermissionModel
            ->where('user_id', $userId)
            ->where('is_granted', 1)
            ->findAll();
        foreach ($directPermissions as $dp) {
            if (!in_array($dp['permission_id'], $permissionIds)) {
                $permissionIds[] = $dp['permission_id'];
            }
        }

        // 移除被撤銷的權限（is_granted = 0）
        $revokedPermissions = $this->userPermissionModel
            ->where('user_id', $userId)
            ->where('is_granted', 0)
            ->findAll();
        $revokedIds = array_column($revokedPermissions, 'permission_id');
        $permissionIds = array_diff($permissionIds, $revokedIds);

        // 取得權限詳細資料
        if (!empty($permissionIds)) {
            $permissionList = $this->permissionModel
                ->whereIn('id', $permissionIds)
                ->where('status', 1)
                ->findAll();
            $permissions = array_column($permissionList, 'name');
        }

        return $permissions;
    }

    /**
     * 取得使用者的所有角色
     */
    protected function getUserRoles($userId)
    {
        $roles = [];
        $userRoles = $this->userRoleModel
            ->select('sys_roles.*')
            ->join('sys_roles', 'sys_roles.id = sys_user_roles.role_id', 'inner')
            ->where('sys_user_roles.user_id', $userId)
            ->where('sys_roles.status', 1)
            ->findAll();

        return $userRoles;
    }
    /**
     * 管理員登入：驗證帳號密碼並寫入 Session
     */
    public function login()
    {
        $payload = $this->request->getJSON(true) ?: $this->request->getPost();

        $rules = [
            'username' => 'required',
            'password' => 'required',
        ];

        if (!$this->validateData($payload, $rules)) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                'success' => false,
                'message' => '請提供帳號與密碼',
            ]);
        }

        $admin = $this->userModel->where('username', $payload['username'])->first();



        if (!$admin) {
            // HTTP_UNAUTHORIZED = 401 (未授權)
            return $this->response->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)->setJSON([
                'success' => false,
                'message' => '帳號不存在',
            ]);
        }

        if ((int) $admin['status'] !== 1) {
            // HTTP_FORBIDDEN = 403 (禁止存取)
            return $this->response->setStatusCode(ResponseInterface::HTTP_FORBIDDEN)->setJSON([
                'success' => false,
                'message' => '帳號已停用',
            ]);
        }

        if (!password_verify($payload['password'], $admin['password_hash'])) {
            // HTTP_UNAUTHORIZED = 401 (未授權)
            return $this->response->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)->setJSON([
                'success' => false,
                'message' => '密碼錯誤',
            ]);
        }

        // 建立 Session
        $session = session();
        $session->regenerate(true);

        // 取得使用者的角色和權限
        $roles = $this->getUserRoles($admin['id']);
        $permissions = $this->getUserPermissions($admin['id']);

        $user = [
            'id' => $admin['id'],
            'permission_name' => $admin['permission_name'],
            'status' => (int) $admin['status'],
            'username' => $admin['username'],
            'name' => $admin['name'],
            'phone' => $admin['phone'],
            'address' => $admin['address'],
            'created_at' => $admin['created_at'],
            'updated_at' => $admin['updated_at'],
            'roles' => $roles,
            'permissions' => $permissions,
        ];

        $session->set('admin_user', $user);

        return $this->response->setJSON([
            'success' => true,
            'message' => '登入成功',
            'data' => [
                'user' => $user,
                'token' => session_id(),
            ],
        ]);
    }
    /**
     * 取得目前登入的管理員資料
     */
    public function me()
    {
        $session = session();
        $user = $session->get('admin_user');

        if (!$user) {
            // HTTP_UNAUTHORIZED = 401 (未授權)
            return $this->response->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)->setJSON([
                'success' => false,
                'message' => '尚未登入',
            ]);
        }

        // 重新載入最新的角色和權限
        $roles = $this->getUserRoles($user['id']);
        $permissions = $this->getUserPermissions($user['id']);
        $user['roles'] = $roles;
        $user['permissions'] = $permissions;

        // 更新 Session
        $session->set('admin_user', $user);

        return $this->response->setJSON([
            'success' => true,
            'data' => $user,
        ]);
    }

    /**
     * 登出並清除 Session
     */
    public function logout()
    {
        $session = session();
        $session->remove('admin_user');
        $session->destroy();

        return $this->response->setJSON([
            'success' => true,
            'message' => '已登出',
        ]);
    }
}