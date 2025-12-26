<?php
namespace App\Controllers;

use App\Models\AppProgressModel;
use CodeIgniter\HTTP\ResponseInterface;

class AppProgressController extends BaseController
{
    protected $appProgressModel;

    public function __construct()
    {
        $this->appProgressModel = new AppProgressModel();
    }

    /**
     * 取得所有工程進度
     */
    public function get()
    {
        try {
            $status = $this->request->getGet('status');
            $caseId = $this->request->getGet('case_id');
            $query = $this->appProgressModel->orderBy('sort', 'ASC')->orderBy('progress_date', 'DESC')->orderBy('id', 'DESC');
            
            // 如果提供了 case_id，則過濾該建案的資料
            if ($caseId !== null) {
                $query->where('case_id', (int)$caseId);
            }
            
            if ($status !== null) {
                $query->where('status', (int)$status);
            }

            $progresses = $query->findAll();

            // 解析 JSON 欄位並確保 ID 為整數類型
            foreach ($progresses as &$item) {
                // 確保 ID 相關欄位為整數
                if (isset($item['id'])) {
                    $item['id'] = (int)$item['id'];
                }
                if (isset($item['case_id'])) {
                    $item['case_id'] = $item['case_id'] !== null ? (int)$item['case_id'] : null;
                }
                if (isset($item['sort'])) {
                    $item['sort'] = (int)$item['sort'];
                }
                if (isset($item['status'])) {
                    $item['status'] = (int)$item['status'];
                }
                
                // 解析 JSON 欄位
                if (!empty($item['images'])) {
                    $item['images'] = json_decode($item['images'], true) ?? [];
                } else {
                    $item['images'] = [];
                }
            }

            return $this->response->setJSON([
                'success' => true,
                'data' => $progresses,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'getProgresses failed: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '取得工程進度失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
    }

    /**
     * 取得單一工程進度
     */
    public function getById()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getGet();
        $id = $data['id'] ?? null;

        if (!$id) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                'success' => false,
                'message' => '缺少工程進度 ID',
            ]);
        }

        try {
            $progress = $this->appProgressModel->find($id);
            if (!$progress) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_NOT_FOUND)->setJSON([
                    'success' => false,
                    'message' => '工程進度不存在',
                ]);
            }

            // 確保 ID 相關欄位為整數
            if (isset($progress['id'])) {
                $progress['id'] = (int)$progress['id'];
            }
            if (isset($progress['case_id'])) {
                $progress['case_id'] = $progress['case_id'] !== null ? (int)$progress['case_id'] : null;
            }
            if (isset($progress['sort'])) {
                $progress['sort'] = (int)$progress['sort'];
            }
            if (isset($progress['status'])) {
                $progress['status'] = (int)$progress['status'];
            }
            
            // 解析 JSON 欄位
            if (!empty($progress['images'])) {
                $progress['images'] = json_decode($progress['images'], true) ?? [];
            } else {
                $progress['images'] = [];
            }

            return $this->response->setJSON([
                'success' => true,
                'data' => $progress,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'getProgressById failed: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '取得工程進度失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
    }

    /**
     * 新增工程進度
     */
    public function add()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();

        $rules = [
            'title' => 'permit_empty|max_length[255]',
            'case_id' => 'permit_empty|integer',
            'progress_date' => 'permit_empty|valid_date',
            'images' => 'permit_empty',
            'sort' => 'permit_empty|integer',
            'status' => 'permit_empty|in_list[0,1]',
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
                'case_id' => isset($data['case_id']) ? (int)$data['case_id'] : null,
                'title' => isset($data['title']) ? trim($data['title']) : null,
                'progress_date' => $data['progress_date'] ?? null,
                'images' => isset($data['images']) && is_array($data['images']) 
                    ? json_encode($data['images'], JSON_UNESCAPED_UNICODE) 
                    : null,
                'sort' => isset($data['sort']) ? (int)$data['sort'] : 0,
                'status' => isset($data['status']) ? (int)$data['status'] : 1,
            ];

            $insertId = $this->appProgressModel->insert($insertData);

            if (!$insertId) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                    'success' => false,
                    'message' => '新增工程進度失敗，請稍後再試',
                ]);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => '新增工程進度成功',
                'data' => [
                    'id' => $insertId,
                ],
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'addProgress failed: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '新增工程進度失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
    }

    /**
     * 更新工程進度
     */
    public function update()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();

        $id = $data['id'] ?? null;
        if (!$id) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                'success' => false,
                'message' => '缺少工程進度 ID',
            ]);
        }

        // 檢查工程進度是否存在
        $progress = $this->appProgressModel->find($id);
        if (!$progress) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_NOT_FOUND)->setJSON([
                'success' => false,
                'message' => '工程進度不存在',
            ]);
        }

        $rules = [
            'title' => 'permit_empty|max_length[255]',
            'case_id' => 'permit_empty|integer',
            'progress_date' => 'permit_empty|valid_date',
            'images' => 'permit_empty',
            'sort' => 'permit_empty|integer',
            'status' => 'permit_empty|in_list[0,1]',
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

            if (isset($data['case_id'])) {
                $newCaseId = (int)$data['case_id'];
                if (($progress['case_id'] ?? null) !== $newCaseId) {
                    $updateData['case_id'] = $newCaseId;
                }
            }
            if (isset($data['title'])) {
                $newTitle = trim($data['title']);
                if (($progress['title'] ?? null) !== $newTitle) {
                    $updateData['title'] = $newTitle;
                }
            }
            if (isset($data['progress_date'])) {
                $newProgressDate = $data['progress_date'];
                if (($progress['progress_date'] ?? null) !== $newProgressDate) {
                    $updateData['progress_date'] = $newProgressDate;
                }
            }
            if (isset($data['images'])) {
                $newImages = is_array($data['images']) 
                    ? json_encode($data['images'], JSON_UNESCAPED_UNICODE) 
                    : null;
                $currentImages = !empty($progress['images']) 
                    ? json_encode(json_decode($progress['images'], true), JSON_UNESCAPED_UNICODE) 
                    : null;
                
                if ($newImages !== $currentImages) {
                    $updateData['images'] = $newImages;
                }
            }
            if (isset($data['sort'])) {
                $newSort = (int)$data['sort'];
                if ((int)($progress['sort'] ?? 0) !== $newSort) {
                    $updateData['sort'] = $newSort;
                }
            }
            if (isset($data['status'])) {
                $newStatus = (int)$data['status'];
                if ((int)($progress['status'] ?? 1) !== $newStatus) {
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
            $updated = $this->appProgressModel->skipValidation(true)->update($id, $updateData);

            if (!$updated) {
                $error = $this->appProgressModel->errors();
                $response = [
                    'success' => false,
                    'message' => '更新工程進度失敗，請稍後再試',
                    'error' => 'Model update failed',
                    'model_errors' => $error,
                ];
                return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON($response);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => '更新工程進度成功',
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'updateProgress failed: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '更新工程進度失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
    }

    /**
     * 刪除工程進度
     */
    public function delete()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();
        $id = $data['id'] ?? null;

        if (!$id) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                'success' => false,
                'message' => '缺少工程進度 ID',
            ]);
        }

        try {
            // 檢查工程進度是否存在
            $progress = $this->appProgressModel->find($id);
            if (!$progress) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_NOT_FOUND)->setJSON([
                    'success' => false,
                    'message' => '工程進度不存在',
                ]);
            }

            $deleted = $this->appProgressModel->delete($id);
            if (!$deleted) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                    'success' => false,
                    'message' => '刪除工程進度失敗，請稍後再試',
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'deleteProgress failed: {message}', ['message' => $e->getMessage()]);
            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '刪除工程進度失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => '刪除工程進度成功',
        ]);
    }
}

