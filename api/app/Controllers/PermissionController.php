<?php
namespace App\Controllers;

use App\Models\PermissionModel;
use App\Models\RolePermissionModel;
use App\Models\UserPermissionModel;
use CodeIgniter\HTTP\ResponseInterface;

class PermissionController extends BaseController
{
    protected $permissionModel;
    protected $rolePermissionModel;
    protected $userPermissionModel;

    public function __construct()
    {
        $this->permissionModel = new PermissionModel();
        $this->rolePermissionModel = new RolePermissionModel();
        $this->userPermissionModel = new UserPermissionModel();
    }

    /**
     * 取得所有權限
     */
    public function get()
    {
        try {
            $moduleId = $this->request->getGet('module_id');
            $query = $this->permissionModel->orderBy('sort_order', 'ASC');
            $query->orderBy('id', 'ASC');
            
            if ($moduleId) {
                $query->where('module_id', $moduleId);
            }

            $permissions = $query->findAll();

            return $this->response->setJSON([
                'success' => true,
                'data' => $permissions,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'getPermissions failed: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '取得權限失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
    }

    /**
     * 取得單一權限
     */
    public function getById()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getGet();
        $id = $data['id'] ?? null;

        if (!$id) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                'success' => false,
                'message' => '缺少權限 ID',
            ]);
        }

        try {
            $permission = $this->permissionModel->find($id);
            if (!$permission) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_NOT_FOUND)->setJSON([
                    'success' => false,
                    'message' => '權限不存在',
                ]);
            }

            return $this->response->setJSON([
                'success' => true,
                'data' => $permission,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'getPermissionById failed: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '取得權限失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
    }

    /**
     * 新增權限
     */
    public function add()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();

        $rules = [
            'name' => 'required|min_length[1]|max_length[255]',
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
            // 檢查權限名稱是否已存在
            $existingPermission = $this->permissionModel->where('name', $data['name'])->first();
            if ($existingPermission) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_CONFLICT)->setJSON([
                    'success' => false,
                    'message' => '權限名稱已存在',
                    'errors' => [
                        'name' => '此權限名稱已被使用',
                    ],
                ]);
            }

            $insertData = [
                'name' => trim($data['name']),
                'label' => trim($data['label']),
                'description' => $data['description'] ?? null,
                'module_id' => isset($data['module_id']) && $data['module_id'] ? (int)$data['module_id'] : null,
                'category' => $data['category'] ?? null,
                'action' => $data['action'] ?? null,
                'status' => isset($data['status']) ? (int)$data['status'] : 1,
            ];

            $insertId = $this->permissionModel->insert($insertData);

            if (!$insertId) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                    'success' => false,
                    'message' => '新增權限失敗，請稍後再試',
                ]);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => '新增權限成功',
                'data' => [
                    'id' => $insertId,
                ],
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'addPermission failed: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '新增權限失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
    }

    /**
     * 更新權限
     */
    public function update()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();

        $id = $data['id'] ?? null;
        if (!$id) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                'success' => false,
                'message' => '缺少權限 ID',
            ]);
        }

        // 檢查權限是否存在
        $permission = $this->permissionModel->find($id);
        if (!$permission) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_NOT_FOUND)->setJSON([
                'success' => false,
                'message' => '權限不存在',
            ]);
        }

        $rules = [
            'name' => 'permit_empty|min_length[1]|max_length[255]',
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
                // 檢查代碼是否真的改變了
                if ($permission['name'] !== $newName) {
                    // 只有代碼改變時才需要檢查唯一性
                    $existingPermission = $this->permissionModel->where('name', $newName)->where('id !=', $id)->first();
                    if ($existingPermission) {
                        return $this->response->setStatusCode(ResponseInterface::HTTP_CONFLICT)->setJSON([
                            'success' => false,
                            'message' => '權限代碼已存在~',
                            'errors' => [
                                'name' => '此權限代碼已被其他權限使用',
                            ],
                        ]);
                    }
                    $updateData['name'] = $newName;
                }
            }
            if (isset($data['label'])) {
                $newLabel = trim($data['label']);
                if (($permission['label'] ?? null) !== $newLabel) {
                    $updateData['label'] = $newLabel;
                }
            }
            if (isset($data['description'])) {
                $newDescription = $data['description'];
                if (($permission['description'] ?? null) !== $newDescription) {
                    $updateData['description'] = $newDescription;
                }
            }
            if (isset($data['module_id'])) {
                $newModuleId = $data['module_id'] ? (int)$data['module_id'] : null;
                if (($permission['module_id'] ?? null) !== $newModuleId) {
                    $updateData['module_id'] = $newModuleId;
                }
            }
            if (isset($data['category'])) {
                $newCategory = $data['category'];
                if (($permission['category'] ?? null) !== $newCategory) {
                    $updateData['category'] = $newCategory;
                }
            }
            if (isset($data['action'])) {
                $newAction = $data['action'];
                if (($permission['action'] ?? null) !== $newAction) {
                    $updateData['action'] = $newAction;
                }
            }
            if (isset($data['status'])) {
                $newStatus = (int)$data['status'];
                if ((int)($permission['status'] ?? 1) !== $newStatus) {
                    $updateData['status'] = $newStatus;
                }
            }

            if (empty($updateData)) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                    'success' => false,
                    'message' => '沒有需要更新的資料',
                ]);
            }

            // 跳過 Model 驗證，因為我們已經在 Controller 中手動驗證了
            $updated = $this->permissionModel->skipValidation(true)->update($id, $updateData);

            if (!$updated) {
                $error = $this->permissionModel->errors();
                $response = [
                    'success' => false,
                    'message' => '更新權限失敗，請稍後再試',
                    'error' => 'Model update failed',
                    'model_errors' => $error,
                ];
                return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON($response);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => '更新權限成功',
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'updatePermission failed: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '更新權限失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
    }

    /**
     * 刪除權限
     */
    public function delete()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();
        $id = $data['id'] ?? null;

        if (!$id) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                'success' => false,
                'message' => '缺少權限 ID',
            ]);
        }

        try {
            // 檢查權限是否存在
            $permission = $this->permissionModel->find($id);
            if (!$permission) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_NOT_FOUND)->setJSON([
                    'success' => false,
                    'message' => '權限不存在',
                ]);
            }

            // 刪除權限（會自動刪除關聯的 role_permissions 和 user_permissions，因為有外鍵約束）
            $deleted = $this->permissionModel->delete($id);
            if (!$deleted) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                    'success' => false,
                    'message' => '刪除權限失敗，請稍後再試',
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'deletePermission failed: {message}', ['message' => $e->getMessage()]);
            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '刪除權限失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => '刪除權限成功',
        ]);
    }

    /**
     * 更新排序順序
     */
    public function updateSortOrder()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();

        // 如果直接是數組，使用數組；否則從 list 鍵取得
        $list = is_array($data) && isset($data[0]) ? $data : ($data['list'] ?? []);

        if (empty($list) || !is_array($list)) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                'success' => false,
                'message' => '缺少排序資料',
            ]);
        }

        // 驗證資料格式
        foreach ($list as $item) {
            if (!isset($item['id']) || !isset($item['sort_order'])) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                    'success' => false,
                    'message' => '排序資料格式錯誤，缺少 id 或 sort_order',
                ]);
            }
        }

        try {
            $updated = $this->permissionModel->updateSortOrder($list);
            if (!$updated) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                    'success' => false,
                    'message' => '更新排序順序失敗，請稍後再試',
                ]);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => '更新排序順序成功',
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'updateSortOrder failed: {message}', ['message' => $e->getMessage()]);
            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '更新排序順序失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
    }
}
