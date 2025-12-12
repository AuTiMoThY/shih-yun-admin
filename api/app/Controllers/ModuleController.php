<?php
namespace App\Controllers;

use App\Models\SysmoduleModel;
use CodeIgniter\HTTP\ResponseInterface;

class ModuleController extends BaseController
{
    protected $sysmoduleModel;

    public function __construct()
    {
        $this->sysmoduleModel = new SysmoduleModel();
    }

    /**
     * 取得所有模組
     */
    public function get()
    {
        try {
            $modules = $this->sysmoduleModel->orderBy('id', 'ASC')->findAll();

            return $this->response->setJSON([
                'success' => true,
                'data' => $modules,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'getModules failed: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '取得模組失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
    }

    /**
     * 新增模組
     */
    public function add()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();

        $rules = [
            'label' => 'required|min_length[1]|max_length[100]',
            'name' => 'required|min_length[1]|max_length[100]|regex_match[/^[a-zA-Z0-9_-]+$/]',
        ];

        if (!$this->validateData($data, $rules)) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_UNPROCESSABLE_ENTITY)->setJSON([
                'success' => false,
                'message' => '驗證失敗',
                'errors' => $this->validator->getErrors(),
            ]);
        }

        try {
            // 檢查模組代碼是否已存在
            $existingModule = $this->sysmoduleModel->where('name', $data['name'])->first();
            if ($existingModule) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_CONFLICT)->setJSON([
                    'success' => false,
                    'message' => '模組代碼已存在',
                    'errors' => [
                        'name' => '此模組代碼已被使用',
                    ],
                ]);
            }

            $insertData = [
                'label' => trim($data['label']),
                'name' => trim($data['name']),
            ];

            $insertId = $this->sysmoduleModel->insert($insertData);

            if (!$insertId) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                    'success' => false,
                    'message' => '新增模組失敗，請稍後再試',
                ]);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => '新增模組成功',
                'data' => [
                    'id' => $insertId,
                ],
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'addModule failed: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '新增模組失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
    }

    /**
     * 更新模組
     */
    public function update()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();

        $id = $data['id'] ?? null;
        if (!$id) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                'success' => false,
                'message' => '缺少模組 ID',
            ]);
        }

        // 檢查模組是否存在
        $module = $this->sysmoduleModel->find($id);
        if (!$module) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_NOT_FOUND)->setJSON([
                'success' => false,
                'message' => '模組不存在',
            ]);
        }

        $rules = [
            'label' => 'permit_empty|min_length[1]|max_length[100]',
            'name' => 'permit_empty|min_length[1]|max_length[100]|regex_match[/^[a-zA-Z0-9_-]+$/]',
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
                $updateData['label'] = trim($data['label']);
            }
            if (isset($data['name'])) {
                // 檢查模組代碼是否已被其他模組使用
                $existingModule = $this->sysmoduleModel->where('name', trim($data['name']))->where('id !=', $id)->first();
                if ($existingModule) {
                    return $this->response->setStatusCode(ResponseInterface::HTTP_CONFLICT)->setJSON([
                        'success' => false,
                        'message' => '模組代碼已存在',
                        'errors' => [
                            'name' => '此模組代碼已被其他模組使用',
                        ],
                    ]);
                }
                $updateData['name'] = trim($data['name']);
            }

            if (empty($updateData)) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                    'success' => false,
                    'message' => '沒有需要更新的資料',
                ]);
            }

            $updated = $this->sysmoduleModel->update($id, $updateData);

            if (!$updated) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                    'success' => false,
                    'message' => '更新模組失敗，請稍後再試',
                ]);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => '更新模組成功',
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'updateModule failed: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '更新模組失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
    }

    /**
     * 刪除模組
     */
    public function delete()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();
        $id = $data['id'] ?? null;

        if (!$id) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                'success' => false,
                'message' => '缺少模組 ID',
            ]);
        }

        try {
            // 檢查模組是否存在
            $module = $this->sysmoduleModel->find($id);
            if (!$module) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_NOT_FOUND)->setJSON([
                    'success' => false,
                    'message' => '模組不存在',
                ]);
            }

            $deleted = $this->sysmoduleModel->delete($id);
            if (!$deleted) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                    'success' => false,
                    'message' => '刪除模組失敗，請稍後再試',
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'deleteModule failed: {message}', ['message' => $e->getMessage()]);
            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '刪除模組失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => '刪除模組成功',
        ]);
    }
}

