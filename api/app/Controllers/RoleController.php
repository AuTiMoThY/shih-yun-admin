<?php
namespace App\Controllers;

use App\Models\RoleModel;
use App\Models\RolePermissionModel;
use App\Models\UserRoleModel;
use CodeIgniter\HTTP\ResponseInterface;

class RoleController extends BaseController
{
    protected $roleModel;
    protected $rolePermissionModel;
    protected $userRoleModel;

    public function __construct()
    {
        $this->roleModel = new RoleModel();
        $this->rolePermissionModel = new RolePermissionModel();
        $this->userRoleModel = new UserRoleModel();
    }

    /**
     * 取得所有角色
     */
    public function get()
    {
        try {
            $roles = $this->roleModel->orderBy('id', 'ASC')->findAll();

            return $this->response->setJSON([
                'success' => true,
                'data' => $roles,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'getRoles failed: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '取得角色失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
    }

    /**
     * 取得單一角色（含權限）
     */
    public function getById()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getGet();
        $id = $data['id'] ?? null;

        if (!$id) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                'success' => false,
                'message' => '缺少角色 ID',
            ]);
        }

        try {
            $role = $this->roleModel->find($id);
            if (!$role) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_NOT_FOUND)->setJSON([
                    'success' => false,
                    'message' => '角色不存在',
                ]);
            }

            // 取得角色的權限
            $permissions = $this->rolePermissionModel
                ->select('permission_id')
                ->where('role_id', $id)
                ->findAll();

            $role['permission_ids'] = array_column($permissions, 'permission_id');

            return $this->response->setJSON([
                'success' => true,
                'data' => $role,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'getRoleById failed: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '取得角色失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
    }

    /**
     * 新增角色
     */
    public function add()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();

        $rules = [
            'name' => 'required|min_length[1]|max_length[100]|regex_match[/^[a-zA-Z0-9_-]+$/]',
            'label' => 'required|min_length[1]|max_length[255]',
        ];

        if (!$this->validateData($data, $rules)) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_UNPROCESSABLE_ENTITY)->setJSON([
                'success' => false,
                'message' => '驗證失敗',
                'errors' => $this->validator->getErrors(),
            ]);
        }

        try {
            // 檢查角色名稱是否已存在
            $existingRole = $this->roleModel->where('name', $data['name'])->first();
            if ($existingRole) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_CONFLICT)->setJSON([
                    'success' => false,
                    'message' => '角色名稱已存在',
                    'errors' => [
                        'name' => '此角色名稱已被使用',
                    ],
                ]);
            }

            $insertData = [
                'name' => trim($data['name']),
                'label' => trim($data['label']),
                'description' => $data['description'] ?? null,
                'status' => isset($data['status']) ? (int) $data['status'] : 1,
            ];

            $insertId = $this->roleModel->insert($insertData);

            if (!$insertId) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                    'success' => false,
                    'message' => '新增角色失敗，請稍後再試',
                ]);
            }

            // 如果有權限 ID 列表，建立關聯
            if (isset($data['permission_ids']) && is_array($data['permission_ids'])) {
                foreach ($data['permission_ids'] as $permissionId) {
                    $this->rolePermissionModel->insert([
                        'role_id' => $insertId,
                        'permission_id' => $permissionId,
                    ]);
                }
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => '新增角色成功',
                'data' => [
                    'id' => $insertId,
                ],
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'addRole failed: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '新增角色失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
    }

    /**
     * 更新角色
     */
    public function update()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();

        $id = $data['id'] ?? null;
        if (!$id) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                'success' => false,
                'message' => '缺少角色 ID',
            ]);
        }

        // 檢查角色是否存在
        $role = $this->roleModel->find($id);
        if (!$role) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_NOT_FOUND)->setJSON([
                'success' => false,
                'message' => '角色不存在',
            ]);
        }

        $rules = [
            'name' => 'permit_empty|min_length[1]|max_length[100]|regex_match[/^[a-zA-Z0-9_-]+$/]',
            'label' => 'permit_empty|min_length[1]|max_length[255]',
        ];

        if (!$this->validateData($data, $rules)) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_UNPROCESSABLE_ENTITY)->setJSON([
                'success' => false,
                'message' => '驗證失敗',
                'errors' => $this->validator->getErrors(),
            ]);
        }

        try {
            $updateData = [];

            if (isset($data['name'])) {
                $newName = trim($data['name']);
                // 只有在值真的改變時才處理
                if ($role['name'] !== $newName) {
                    // 檢查唯一性
                    $existingRole = $this->roleModel->where('name', $newName)->where('id !=', $id)->first();
                    if ($existingRole) {
                        return $this->response->setStatusCode(ResponseInterface::HTTP_CONFLICT)->setJSON([
                            'success' => false,
                            'message' => '角色名稱已存在',
                            'errors' => [
                                'name' => '此角色名稱已被其他角色使用',
                            ],
                        ]);
                    }
                    $updateData['name'] = $newName;
                }
            }
            if (isset($data['label'])) {
                $newLabel = trim($data['label']);
                if (($role['label'] ?? null) !== $newLabel) {
                    $updateData['label'] = $newLabel;
                }
            }
            if (isset($data['description'])) {
                $newDescription = $data['description'];
                if (($role['description'] ?? null) !== $newDescription) {
                    $updateData['description'] = $newDescription;
                }
            }
            if (isset($data['status'])) {
                $newStatus = (int) $data['status'];
                if ((int) ($role['status'] ?? 1) !== $newStatus) {
                    $updateData['status'] = $newStatus;
                }
            }

            // 更新權限關聯
            if (isset($data['permission_ids']) && is_array($data['permission_ids'])) {
                try {
                    // 刪除舊的關聯
                    $this->rolePermissionModel->where('role_id', $id)->delete();

                    // 建立新的關聯
                    foreach ($data['permission_ids'] as $permissionId) {
                        // 確保 permission_id 是整數
                        $permissionId = (int) $permissionId;
                        if ($permissionId > 0) {
                            $this->rolePermissionModel->insert([
                                'role_id' => (int) $id,
                                'permission_id' => $permissionId,
                            ]);
                        }
                    }
                } catch (\Throwable $e) {
                    log_message('error', 'updateRole permissions failed: {message}', ['message' => $e->getMessage()]);
                    throw $e;
                }
            }

            // 如果有欄位需要更新，才執行 Model 的 update
            if (!empty($updateData)) {
                // 跳過 Model 驗證，因為我們已經在 Controller 中手動驗證了
                $updated = $this->roleModel->skipValidation(true)->update($id, $updateData);
                if (!$updated) {
                    $error = $this->roleModel->errors();
                    $response = [
                        'success' => false,
                        'message' => '更新角色失敗，請稍後再試',
                        'error' => 'Model update failed',
                        'model_errors' => $error,
                    ];
                    return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON($response);
                }
            }



            return $this->response->setJSON([
                'success' => true,
                'message' => '更新角色成功',
            ]);
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
            $errorTrace = $e->getTraceAsString();
            log_message('error', 'updateRole failed: {message}', ['message' => $errorMessage]);
            log_message('error', 'updateRole trace: {trace}', ['trace' => $errorTrace]);

            $response = [
                'success' => false,
                'message' => '更新角色失敗，請稍後再試',
            ];

            // 在非生產環境下回傳詳細錯誤資訊（暫時強制回傳以便除錯）
            $isDevelopment = (ENVIRONMENT !== 'production' && ENVIRONMENT !== 'testing');
            if ($isDevelopment || true) { // 暫時強制回傳詳細錯誤
                $response['error'] = $errorMessage;
                $response['file'] = $e->getFile();
                $response['line'] = $e->getLine();
                $response['trace'] = explode("\n", $errorTrace);
                $response['environment'] = ENVIRONMENT;
            }

            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON($response);
        }
    }

    /**
     * 刪除角色
     */
    public function delete()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();
        $id = $data['id'] ?? null;

        if (!$id) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                'success' => false,
                'message' => '缺少角色 ID',
            ]);
        }

        try {
            // 檢查角色是否存在
            $role = $this->roleModel->find($id);
            if (!$role) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_NOT_FOUND)->setJSON([
                    'success' => false,
                    'message' => '角色不存在',
                ]);
            }

            // 檢查是否為超級管理員角色（不允許刪除）
            if ($role['name'] === 'super_admin') {
                return $this->response->setStatusCode(ResponseInterface::HTTP_FORBIDDEN)->setJSON([
                    'success' => false,
                    'message' => '無法刪除超級管理員角色',
                ]);
            }

            // 檢查是否有使用者使用此角色
            $usersWithRole = $this->userRoleModel->where('role_id', $id)->countAllResults();
            if ($usersWithRole > 0) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_CONFLICT)->setJSON([
                    'success' => false,
                    'message' => '此角色仍有使用者在使用，無法刪除',
                ]);
            }

            // 刪除角色（會自動刪除關聯的 role_permissions，因為有外鍵約束）
            $deleted = $this->roleModel->delete($id);
            if (!$deleted) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                    'success' => false,
                    'message' => '刪除角色失敗，請稍後再試',
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'deleteRole failed: {message}', ['message' => $e->getMessage()]);
            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '刪除角色失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => '刪除角色成功',
        ]);
    }
}
