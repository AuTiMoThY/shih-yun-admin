<?php
namespace App\Controllers;

use App\Models\SysStructureModel;
use CodeIgniter\HTTP\ResponseInterface;

class StructureController extends BaseController
{
    protected $SysStructureModel;

    public function __construct()
    {
        $this->SysStructureModel = new SysStructureModel();
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
            // 檢查是否有子層級
            $children = $this->SysStructureModel->getChildren($id);
            if (!empty($children)) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                    'success' => false,
                    'message' => '此層級下還有子層級，無法刪除',
                ]);
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
}

