<?php
namespace App\Controllers;

use App\Models\SysStructureModel;
use App\Models\PermissionModel;
use CodeIgniter\HTTP\ResponseInterface;

class StructureController extends BaseController
{
    protected $SysStructureModel;
    protected $permissionModel;

    public function __construct()
    {
        $this->SysStructureModel = new SysStructureModel();
        $this->permissionModel = new PermissionModel();
    }

    /**
     * 新增層級
     */
    public function add()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();

        // 將布林/字串狀態轉成 '0'/'1'
        $truthy = ['1', 1, true, 'true', 'on', 'yes'];

        $status = $data['status'] ?? null;

        $data['status'] = in_array($status, $truthy, true) ? '1' : '0';

        // 如果是新增層級1，parent_id 應該為 null
        if (!isset($data['parent_id']) || $data['parent_id'] === '' || $data['parent_id'] === null) {
            $data['parent_id'] = null;
        }

        $rules = [
            'label' => 'required|min_length[1]|max_length[100]',
            'status' => 'required|in_list[0,1]',
            'parent_id' => 'permit_empty|is_natural',
            'sort_order' => 'permit_empty|integer',
            'url' => 'permit_empty|max_length[255]',
        ];

        if (!$this->validateData($data, $rules)) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_UNPROCESSABLE_ENTITY)->setJSON([
                'success' => false,
                'message' => '驗證失敗',
                'errors' => $this->validator->getErrors(),
            ]);
        }

        try {
            $insertData = [
                'label' => $data['label'],
                'module_id' => $data['module_id'],
                'url' => isset($data['url']) && !empty(trim($data['url'])) ? trim($data['url']) : null,
                'status' => (int) $data['status'],
                'sort_order' => isset($data['sort_order']) ? (int) $data['sort_order'] : 0,
            ];

            // 如果有 parent_id，驗證父層級是否存在
            if (isset($data['parent_id']) && $data['parent_id'] !== null && $data['parent_id'] !== '') {
                $parentId = (int) $data['parent_id'];
                $parent = $this->SysStructureModel->find($parentId);
                if (!$parent) {
                    return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                        'success' => false,
                        'message' => '父層級不存在',
                    ]);
                }
                $insertData['parent_id'] = $parentId;
            } else {
                $insertData['parent_id'] = null;
            }

            $insertId = $this->SysStructureModel->insert($insertData);

            if (!$insertId) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                    'success' => false,
                    'message' => '新增層級失敗，請稍後再試',
                ]);
            }

            // 如果有 URL，自動創建對應的權限（view 和 edit）
            if (!empty($insertData['url'])) {
                $this->createPermissionsForStructure($insertData['url'], $insertData['label'], $insertData['module_id']);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => '新增層級成功',
                'data' => [
                    'id' => $insertId,
                ],
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'addLevel failed: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '新增層級失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
    }

    /**
     * 取得所有層級
     */
    public function get()
    {
        try {
            $onlyActive = $this->request->getGet('only_active') === '1' || $this->request->getGet('only_active') === 'true';
            $tree = $this->request->getGet('tree') === '1' || $this->request->getGet('tree') === 'true';

            if ($tree) {
                $levels = $this->SysStructureModel->getAllLevels($onlyActive);
            } else {
                $levels = $this->SysStructureModel->findAll();
            }

            return $this->response->setJSON([
                'success' => true,
                'data' => $levels,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'getLevels failed: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '取得層級失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
    }

    /**
     * 更新層級
     */
    public function update()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();

        $id = $data['id'] ?? null;
        if (!$id) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                'success' => false,
                'message' => '缺少層級 ID',
            ]);
        }

        // 檢查層級是否存在
        $level = $this->SysStructureModel->find($id);
        if (!$level) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_NOT_FOUND)->setJSON([
                'success' => false,
                'message' => '層級不存在',
            ]);
        }

        // 將布林/字串狀態轉成 '0'/'1'
        $truthy = ['1', 1, true, 'true', 'on', 'yes'];

        if (isset($data['status'])) {
            $status = $data['status'];
            $data['status'] = in_array($status, $truthy, true) ? '1' : '0';
        }

        $rules = [
            'label' => 'permit_empty|min_length[1]|max_length[100]',
            'status' => 'permit_empty|in_list[0,1]',
            'parent_id' => 'permit_empty|is_natural',
            'sort_order' => 'permit_empty|integer',
            'url' => 'permit_empty|max_length[255]',
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

            if (isset($data['label'])) {
                $updateData['label'] = $data['label'];
            }
            // 使用 array_key_exists 以支援 null 值
            if (array_key_exists('module_id', $data)) {
                $updateData['module_id'] = $data['module_id'] === '' ? null : $data['module_id'];
            }
            if (array_key_exists('url', $data)) {
                $updateData['url'] = $data['url'] === '' ? null : (isset($data['url']) && !empty(trim($data['url'])) ? trim($data['url']) : null);
            }
            if (isset($data['status'])) {
                $updateData['status'] = (int) $data['status'];
            }
            if (isset($data['sort_order'])) {
                $updateData['sort_order'] = (int) $data['sort_order'];
            }

            // 處理 parent_id
            if (isset($data['parent_id'])) {
                if ($data['parent_id'] === '' || $data['parent_id'] === null) {
                    $updateData['parent_id'] = null;
                } else {
                    $parentId = (int) $data['parent_id'];
                    // 不能將自己設為父層級
                    if ($parentId === (int) $id) {
                        return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                            'success' => false,
                            'message' => '不能將自己設為父層級',
                        ]);
                    }
                    // 驗證父層級是否存在
                    $parent = $this->SysStructureModel->find($parentId);
                    if (!$parent) {
                        return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                            'success' => false,
                            'message' => '父層級不存在',
                        ]);
                    }
                    $updateData['parent_id'] = $parentId;
                }
            }

            $updated = $this->SysStructureModel->update($id, $updateData);

            if (!$updated) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                    'success' => false,
                    'message' => '更新層級失敗，請稍後再試',
                ]);
            }

            // 處理權限的同步
            $oldUrl = $level['url'] ?? null;
            $newUrl = array_key_exists('url', $updateData) ? $updateData['url'] : ($level['url'] ?? null);
            $label = array_key_exists('label', $updateData) ? $updateData['label'] : ($level['label'] ?? '');
            $moduleId = array_key_exists('module_id', $updateData) ? $updateData['module_id'] : ($level['module_id'] ?? null);

            // 如果 URL 有變更
            if ($oldUrl !== $newUrl) {
                // 如果舊 URL 存在，刪除舊權限
                if (!empty($oldUrl)) {
                    $this->deletePermissionsForStructure($oldUrl);
                }
                // 如果新 URL 存在，創建新權限
                if (!empty($newUrl) && !empty($label)) {
                    $this->createPermissionsForStructure($newUrl, $label, $moduleId);
                }
            } else {
                // 如果 URL 沒有變更，但 label 或其他資訊有更新，更新現有權限
                if (!empty($newUrl) && !empty($label)) {
                    $this->createPermissionsForStructure($newUrl, $label, $moduleId);
                }
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => '更新層級成功',
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'updateLevel failed: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '更新層級失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
    }

    /**
     * 刪除層級
     */
    public function delete()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();
        $id = $data['id'] ?? null;

        if (!$id) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                'success' => false,
                'message' => '缺少層級 ID',
            ]);
        }

        try {
            // 先獲取層級資料，以便刪除對應的權限
            $level = $this->SysStructureModel->find($id);
            if (!$level) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_NOT_FOUND)->setJSON([
                    'success' => false,
                    'message' => '層級不存在',
                ]);
            }

            // 檢查是否有子層級
            $children = $this->SysStructureModel->getChildren($id);
            if (!empty($children)) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                    'success' => false,
                    'message' => '此層級下還有子層級，無法刪除',
                ]);
            }

            // 如果有 URL，刪除對應的權限
            if (!empty($level['url'])) {
                $this->deletePermissionsForStructure($level['url']);
            }

            $deleted = $this->SysStructureModel->delete($id);
            if (!$deleted) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                    'success' => false,
                    'message' => '刪除層級失敗，請稍後再試',
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'deleteLevel failed: {message}', ['message' => $e->getMessage()]);
            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '刪除層級失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => '刪除層級成功',
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
            $updated = $this->SysStructureModel->updateSortOrder($list);
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

    /**
     * 為結構創建對應的權限（view 和 edit）
     * 
     * @param string $url 結構的 URL
     * @param string $label 結構的標籤名稱
     * @param int|null $moduleId 模組 ID
     * @return void
     */
    protected function createPermissionsForStructure($url, $label, $moduleId = null)
    {
        if (empty($url) || empty($label)) {
            return;
        }

        $permissions = [
            [
                'name' => $url . '.view',
                'label' => $label . '-查看',
                'description' => '查看' . $label . '單元',
                'module_id' => $moduleId,
                'category' => '',
                'action' => 'view',
                'status' => 1,
            ],
            [
                'name' => $url . '.edit',
                'label' => $label . '-編輯',
                'description' => '編輯' . $label . '單元',
                'module_id' => $moduleId,
                'category' => '',
                'action' => 'edit',
                'status' => 1,
            ],
        ];

        foreach ($permissions as $permissionData) {
            // 檢查權限是否已存在
            $existing = $this->permissionModel->where('name', $permissionData['name'])->first();
            
            if ($existing) {
                // 如果已存在，更新標籤和描述（保持其他欄位不變）
                $this->permissionModel->skipValidation(true)->update($existing['id'], [
                    'label' => $permissionData['label'],
                    'description' => $permissionData['description'],
                    'module_id' => $permissionData['module_id'],
                ]);
            } else {
                // 如果不存在，創建新權限
                try {
                    $this->permissionModel->skipValidation(true)->insert($permissionData);
                } catch (\Throwable $e) {
                    // 記錄錯誤但不中斷流程
                    log_message('error', 'createPermissionForStructure failed: {message}', [
                        'message' => $e->getMessage(),
                        'permission' => $permissionData['name']
                    ]);
                }
            }
        }
    }

    /**
     * 刪除結構對應的權限（view 和 edit）
     * 
     * @param string $url 結構的 URL
     * @return void
     */
    protected function deletePermissionsForStructure($url)
    {
        if (empty($url)) {
            return;
        }

        $permissionNames = [
            $url . '.view',
            $url . '.edit',
        ];

        foreach ($permissionNames as $permissionName) {
            try {
                $permission = $this->permissionModel->where('name', $permissionName)->first();
                if ($permission) {
                    $this->permissionModel->delete($permission['id']);
                }
            } catch (\Throwable $e) {
                // 記錄錯誤但不中斷流程
                log_message('error', 'deletePermissionForStructure failed: {message}', [
                    'message' => $e->getMessage(),
                    'permission' => $permissionName
                ]);
            }
        }
    }
}

