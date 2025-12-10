<?php
namespace App\Controllers;

use App\Models\StructureModel;
use CodeIgniter\HTTP\ResponseInterface;

class StructureController extends BaseController
{
    protected $structureModel;

    public function __construct()
    {
        $this->structureModel = new StructureModel();
    }

    /**
     * 新增層級
     */
    public function addLevel()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();

        // 將布林/字串狀態轉成 '0'/'1'
        $truthy = ['1', 1, true, 'true', 'on', 'yes'];
        
        $isShowFrontend = $data['is_show_frontend'] ?? null;
        $isShowBackend = $data['is_show_backend'] ?? null;
        $status = $data['status'] ?? null;
        
        $data['is_show_frontend'] = in_array($isShowFrontend, $truthy, true) ? '1' : '0';
        $data['is_show_backend'] = in_array($isShowBackend, $truthy, true) ? '1' : '0';
        $data['status'] = in_array($status, $truthy, true) ? '1' : '0';

        // 如果是新增層級1，parent_id 應該為 null
        if (!isset($data['parent_id']) || $data['parent_id'] === '' || $data['parent_id'] === null) {
            $data['parent_id'] = null;
        }

        $rules = [
            'name' => 'required|min_length[1]|max_length[100]',
            'is_show_frontend' => 'required|in_list[0,1]',
            'is_show_backend' => 'required|in_list[0,1]',
            'status' => 'required|in_list[0,1]',
            'parent_id' => 'permit_empty|is_natural',
            'sort_order' => 'permit_empty|integer',
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
                'name' => $data['name'],
                'is_show_frontend' => (int) $data['is_show_frontend'],
                'is_show_backend' => (int) $data['is_show_backend'],
                'status' => (int) $data['status'],
                'sort_order' => isset($data['sort_order']) ? (int) $data['sort_order'] : 0,
            ];

            // 如果有 parent_id，驗證父層級是否存在
            if (isset($data['parent_id']) && $data['parent_id'] !== null && $data['parent_id'] !== '') {
                $parentId = (int) $data['parent_id'];
                $parent = $this->structureModel->find($parentId);
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

            $insertId = $this->structureModel->insert($insertData);

            if (!$insertId) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                    'success' => false,
                    'message' => '新增層級失敗，請稍後再試',
                ]);
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
    public function getLevels()
    {
        try {
            $onlyActive = $this->request->getGet('only_active') === '1' || $this->request->getGet('only_active') === 'true';
            $tree = $this->request->getGet('tree') === '1' || $this->request->getGet('tree') === 'true';

            if ($tree) {
                $levels = $this->structureModel->getAllLevels($onlyActive);
            } else {
                $levels = $this->structureModel->findAll();
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
    public function updateLevel()
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
        $level = $this->structureModel->find($id);
        if (!$level) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_NOT_FOUND)->setJSON([
                'success' => false,
                'message' => '層級不存在',
            ]);
        }

        // 將布林/字串狀態轉成 '0'/'1'
        $truthy = ['1', 1, true, 'true', 'on', 'yes'];
        
        if (isset($data['is_show_frontend'])) {
            $isShowFrontend = $data['is_show_frontend'];
            $data['is_show_frontend'] = in_array($isShowFrontend, $truthy, true) ? '1' : '0';
        }
        
        if (isset($data['is_show_backend'])) {
            $isShowBackend = $data['is_show_backend'];
            $data['is_show_backend'] = in_array($isShowBackend, $truthy, true) ? '1' : '0';
        }
        
        if (isset($data['status'])) {
            $status = $data['status'];
            $data['status'] = in_array($status, $truthy, true) ? '1' : '0';
        }

        $rules = [
            'name' => 'permit_empty|min_length[1]|max_length[100]',
            'is_show_frontend' => 'permit_empty|in_list[0,1]',
            'is_show_backend' => 'permit_empty|in_list[0,1]',
            'status' => 'permit_empty|in_list[0,1]',
            'parent_id' => 'permit_empty|is_natural',
            'sort_order' => 'permit_empty|integer',
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
                $updateData['name'] = $data['name'];
            }
            if (isset($data['is_show_frontend'])) {
                $updateData['is_show_frontend'] = (int) $data['is_show_frontend'];
            }
            if (isset($data['is_show_backend'])) {
                $updateData['is_show_backend'] = (int) $data['is_show_backend'];
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
                    $parent = $this->structureModel->find($parentId);
                    if (!$parent) {
                        return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                            'success' => false,
                            'message' => '父層級不存在',
                        ]);
                    }
                    $updateData['parent_id'] = $parentId;
                }
            }

            $updated = $this->structureModel->update($id, $updateData);

            if (!$updated) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                    'success' => false,
                    'message' => '更新層級失敗，請稍後再試',
                ]);
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
    public function deleteLevel()
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
            // 檢查是否有子層級
            $children = $this->structureModel->getChildren($id);
            if (!empty($children)) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                    'success' => false,
                    'message' => '此層級下還有子層級，無法刪除',
                ]);
            }

            $deleted = $this->structureModel->delete($id);
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
}

