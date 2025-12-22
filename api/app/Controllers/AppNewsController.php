<?php
namespace App\Controllers;

use App\Models\AppNewsModel;
use CodeIgniter\HTTP\ResponseInterface;

class AppNewsController extends BaseController
{
    protected $appNewsModel;

    public function __construct()
    {
        $this->appNewsModel = new AppNewsModel();
    }

    /**
     * 取得所有最新消息
     */
    public function get()
    {
        try {
            $status = $this->request->getGet('status');
            $structureId = $this->request->getGet('structure_id');
            $query = $this->appNewsModel->orderBy('show_date', 'DESC')->orderBy('id', 'DESC');
            
            // 如果提供了 structure_id，則過濾該單元的資料
            if ($structureId !== null) {
                $query->where('structure_id', (int)$structureId);
            }
            
            if ($status !== null) {
                $query->where('status', (int)$status);
            }

            $news = $query->findAll();

            // 解析 JSON 欄位
            foreach ($news as &$item) {
                if (!empty($item['slide'])) {
                    $item['slide'] = json_decode($item['slide'], true) ?? [];
                } else {
                    $item['slide'] = [];
                }
            }

            return $this->response->setJSON([
                'success' => true,
                'data' => $news,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'getNews failed: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '取得最新消息失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
    }

    /**
     * 取得單一最新消息
     */
    public function getById()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getGet();
        $id = $data['id'] ?? null;

        if (!$id) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                'success' => false,
                'message' => '缺少最新消息 ID',
            ]);
        }

        try {
            $news = $this->appNewsModel->find($id);
            if (!$news) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_NOT_FOUND)->setJSON([
                    'success' => false,
                    'message' => '最新消息不存在',
                ]);
            }

            // 解析 JSON 欄位
            if (!empty($news['slide'])) {
                $news['slide'] = json_decode($news['slide'], true) ?? [];
            } else {
                $news['slide'] = [];
            }

            return $this->response->setJSON([
                'success' => true,
                'data' => $news,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'getNewsById failed: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '取得最新消息失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
    }

    /**
     * 新增最新消息
     */
    public function add()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();

        $rules = [
            'title' => 'required|min_length[1]|max_length[255]',
            'content' => 'permit_empty',
            'cover' => 'permit_empty|max_length[500]',
            'slide' => 'permit_empty',
            'show_date' => 'permit_empty|valid_date',
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
                'structure_id' => isset($data['structure_id']) ? (int)$data['structure_id'] : null,
                'title' => trim($data['title']),
                'content' => $data['content'] ?? null,
                'cover' => $data['cover'] ?? null,
                'slide' => isset($data['slide']) && is_array($data['slide']) 
                    ? json_encode($data['slide'], JSON_UNESCAPED_UNICODE) 
                    : null,
                'show_date' => $data['show_date'] ?? null,
                'status' => isset($data['status']) ? (int)$data['status'] : 1,
            ];

            $insertId = $this->appNewsModel->insert($insertData);

            if (!$insertId) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                    'success' => false,
                    'message' => '新增最新消息失敗，請稍後再試',
                ]);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => '新增最新消息成功',
                'data' => [
                    'id' => $insertId,
                ],
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'addNews failed: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '新增最新消息失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
    }

    /**
     * 更新最新消息
     */
    public function update()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();

        $id = $data['id'] ?? null;
        if (!$id) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                'success' => false,
                'message' => '缺少最新消息 ID',
            ]);
        }

        // 檢查最新消息是否存在
        $news = $this->appNewsModel->find($id);
        if (!$news) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_NOT_FOUND)->setJSON([
                'success' => false,
                'message' => '最新消息不存在',
            ]);
        }

        $rules = [
            'title' => 'permit_empty|min_length[1]|max_length[255]',
            'content' => 'permit_empty',
            'cover' => 'permit_empty|max_length[500]',
            'slide' => 'permit_empty',
            'show_date' => 'permit_empty|valid_date',
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

            if (isset($data['title'])) {
                $newTitle = trim($data['title']);
                if (($news['title'] ?? null) !== $newTitle) {
                    $updateData['title'] = $newTitle;
                }
            }
            if (isset($data['content'])) {
                $newContent = $data['content'];
                if (($news['content'] ?? null) !== $newContent) {
                    $updateData['content'] = $newContent;
                }
            }
            if (isset($data['cover'])) {
                $newCover = $data['cover'];
                if (($news['cover'] ?? null) !== $newCover) {
                    $updateData['cover'] = $newCover;
                }
            }
            if (isset($data['slide'])) {
                $newSlide = is_array($data['slide']) 
                    ? json_encode($data['slide'], JSON_UNESCAPED_UNICODE) 
                    : null;
                $currentSlide = !empty($news['slide']) 
                    ? json_encode(json_decode($news['slide'], true), JSON_UNESCAPED_UNICODE) 
                    : null;
                
                if ($newSlide !== $currentSlide) {
                    $updateData['slide'] = $newSlide;
                }
            }
            if (isset($data['show_date'])) {
                $newShowDate = $data['show_date'];
                if (($news['show_date'] ?? null) !== $newShowDate) {
                    $updateData['show_date'] = $newShowDate;
                }
            }
            if (isset($data['status'])) {
                $newStatus = (int)$data['status'];
                if ((int)($news['status'] ?? 1) !== $newStatus) {
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
            $updated = $this->appNewsModel->skipValidation(true)->update($id, $updateData);

            if (!$updated) {
                $error = $this->appNewsModel->errors();
                $response = [
                    'success' => false,
                    'message' => '更新最新消息失敗，請稍後再試',
                    'error' => 'Model update failed',
                    'model_errors' => $error,
                ];
                return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON($response);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => '更新最新消息成功',
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'updateNews failed: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '更新最新消息失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
    }

    /**
     * 刪除最新消息
     */
    public function delete()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();
        $id = $data['id'] ?? null;

        if (!$id) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                'success' => false,
                'message' => '缺少最新消息 ID',
            ]);
        }

        try {
            // 檢查最新消息是否存在
            $news = $this->appNewsModel->find($id);
            if (!$news) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_NOT_FOUND)->setJSON([
                    'success' => false,
                    'message' => '最新消息不存在',
                ]);
            }

            $deleted = $this->appNewsModel->delete($id);
            if (!$deleted) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                    'success' => false,
                    'message' => '刪除最新消息失敗，請稍後再試',
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'deleteNews failed: {message}', ['message' => $e->getMessage()]);
            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '刪除最新消息失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => '刪除最新消息成功',
        ]);
    }
}
