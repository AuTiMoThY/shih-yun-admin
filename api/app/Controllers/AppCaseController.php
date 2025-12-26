<?php
namespace App\Controllers;

use App\Models\AppCaseModel;
use CodeIgniter\HTTP\ResponseInterface;

class AppCaseController extends BaseController
{
    protected $appCaseModel;

    public function __construct()
    {
        $this->appCaseModel = new AppCaseModel();
    }

    /**
     * 取得所有建案
     */
    public function get()
    {
        try {
            $status = $this->request->getGet('status');
            $structureId = $this->request->getGet('structure_id');
            $query = $this->appCaseModel->orderBy('sort', 'ASC')->orderBy('year', 'DESC')->orderBy('id', 'DESC');
            
            // 如果提供了 structure_id，則過濾該單元的資料
            if ($structureId !== null) {
                $query->where('structure_id', (int)$structureId);
            }
            
            if ($status !== null) {
                $query->where('status', (int)$status);
            }

            $cases = $query->findAll();

            // 解析 JSON 欄位並確保 ID 為整數類型
            foreach ($cases as &$item) {
                // 確保 ID 相關欄位為整數
                if (isset($item['id'])) {
                    $item['id'] = (int)$item['id'];
                }
                if (isset($item['structure_id'])) {
                    $item['structure_id'] = $item['structure_id'] !== null ? (int)$item['structure_id'] : null;
                }
                if (isset($item['year'])) {
                    $item['year'] = $item['year'] !== null ? (int)$item['year'] : null;
                }
                if (isset($item['sort'])) {
                    $item['sort'] = (int)$item['sort'];
                }
                if (isset($item['status'])) {
                    $item['status'] = (int)$item['status'];
                }
                if (isset($item['ca_pop_type'])) {
                    $item['ca_pop_type'] = (int)$item['ca_pop_type'];
                }
                if (isset($item['is_sale'])) {
                    $item['is_sale'] = (int)$item['is_sale'];
                }
                if (isset($item['is_msg'])) {
                    $item['is_msg'] = (int)$item['is_msg'];
                }
                
                // 解析 JSON 欄位
                if (!empty($item['content'])) {
                    $item['content'] = json_decode($item['content'], true) ?? [];
                } else {
                    $item['content'] = [];
                }
                if (!empty($item['slide'])) {
                    $item['slide'] = json_decode($item['slide'], true) ?? [];
                } else {
                    $item['slide'] = [];
                }
            }

            return $this->response->setJSON([
                'success' => true,
                'data' => $cases,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'getCases failed: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '取得建案失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
    }

    /**
     * 取得單一建案
     */
    public function getById()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getGet();
        $id = $data['id'] ?? null;

        if (!$id) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                'success' => false,
                'message' => '缺少建案 ID',
            ]);
        }

        try {
            $case = $this->appCaseModel->find($id);
            if (!$case) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_NOT_FOUND)->setJSON([
                    'success' => false,
                    'message' => '建案不存在',
                ]);
            }

            // 確保 ID 相關欄位為整數
            if (isset($case['id'])) {
                $case['id'] = (int)$case['id'];
            }
            if (isset($case['structure_id'])) {
                $case['structure_id'] = $case['structure_id'] !== null ? (int)$case['structure_id'] : null;
            }
            if (isset($case['year'])) {
                $case['year'] = $case['year'] !== null ? (int)$case['year'] : null;
            }
            if (isset($case['sort'])) {
                $case['sort'] = (int)$case['sort'];
            }
            if (isset($case['status'])) {
                $case['status'] = (int)$case['status'];
            }
            if (isset($case['ca_pop_type'])) {
                $case['ca_pop_type'] = (int)$case['ca_pop_type'];
            }
            if (isset($case['is_sale'])) {
                $case['is_sale'] = (int)$case['is_sale'];
            }
            if (isset($case['is_msg'])) {
                $case['is_msg'] = (int)$case['is_msg'];
            }
            
            // 解析 JSON 欄位
            if (!empty($case['content'])) {
                $case['content'] = json_decode($case['content'], true) ?? [];
            } else {
                $case['content'] = [];
            }
            if (!empty($case['slide'])) {
                $case['slide'] = json_decode($case['slide'], true) ?? [];
            } else {
                $case['slide'] = [];
            }

            return $this->response->setJSON([
                'success' => true,
                'data' => $case,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'getCaseById failed: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '取得建案失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
    }

    /**
     * 新增建案
     */
    public function add()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();

        $rules = [
            'title' => 'permit_empty|max_length[255]',
            'year' => 'permit_empty|integer|greater_than_equal_to[1900]|less_than_equal_to[9999]',
            's_text' => 'permit_empty|max_length[500]',
            'cover' => 'permit_empty|max_length[500]',
            'content' => 'permit_empty',
            'slide' => 'permit_empty',
            'ca_type' => 'permit_empty|max_length[255]',
            'ca_area' => 'permit_empty|max_length[255]',
            'ca_square' => 'permit_empty|max_length[255]',
            'ca_phone' => 'permit_empty|max_length[50]',
            'ca_adds' => 'permit_empty|max_length[500]',
            'ca_map' => 'permit_empty',
            'ca_pop_type' => 'permit_empty|max_length[255]',
            'ca_pop' => 'permit_empty|max_length[500]',
            'is_sale' => 'permit_empty|in_list[0,1]',
            'is_msg' => 'permit_empty|in_list[0,1]',
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
            // 處理 JSON 欄位
            $contentJson = null;
            if (isset($data['content'])) {
                if (is_array($data['content'])) {
                    $contentJson = json_encode($data['content'], JSON_UNESCAPED_UNICODE);
                } else {
                    $contentJson = $data['content'];
                }
            }

            $slideJson = null;
            if (isset($data['slide'])) {
                if (is_array($data['slide'])) {
                    $slideJson = json_encode($data['slide'], JSON_UNESCAPED_UNICODE);
                } else {
                    $slideJson = $data['slide'];
                }
            }

            $insertData = [
                'structure_id' => isset($data['structure_id']) ? (int)$data['structure_id'] : null,
                'year' => isset($data['year']) ? (int)$data['year'] : null,
                'title' => isset($data['title']) ? trim($data['title']) : null,
                's_text' => isset($data['s_text']) ? trim($data['s_text']) : null,
                'cover' => $data['cover'] ?? null,
                'content' => $contentJson,
                'slide' => $slideJson,
                'ca_type' => isset($data['ca_type']) ? trim($data['ca_type']) : null,
                'ca_area' => isset($data['ca_area']) ? trim($data['ca_area']) : null,
                'ca_square' => isset($data['ca_square']) ? trim($data['ca_square']) : null,
                'ca_phone' => isset($data['ca_phone']) ? trim($data['ca_phone']) : null,
                'ca_adds' => isset($data['ca_adds']) ? trim($data['ca_adds']) : null,
                'ca_map' => $data['ca_map'] ?? null,
                'ca_pop_type' => isset($data['ca_pop_type']) ? trim($data['ca_pop_type']) : null,
                'ca_pop' => $data['ca_pop'] ?? null,
                'is_sale' => isset($data['is_sale']) ? (int)$data['is_sale'] : 0,
                'is_msg' => isset($data['is_msg']) ? (int)$data['is_msg'] : 0,
                'sort' => isset($data['sort']) ? (int)$data['sort'] : 0,
                'status' => isset($data['status']) ? (int)$data['status'] : 1,
            ];

            $insertId = $this->appCaseModel->insert($insertData);

            if (!$insertId) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                    'success' => false,
                    'message' => '新增建案失敗，請稍後再試',
                ]);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => '新增建案成功',
                'data' => [
                    'id' => $insertId,
                ],
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'addCase failed: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '新增建案失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
    }

    /**
     * 更新建案
     */
    public function update()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();

        $id = $data['id'] ?? null;
        if (!$id) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                'success' => false,
                'message' => '缺少建案 ID',
            ]);
        }

        // 檢查建案是否存在
        $case = $this->appCaseModel->find($id);
        if (!$case) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_NOT_FOUND)->setJSON([
                'success' => false,
                'message' => '建案不存在',
            ]);
        }

        $rules = [
            'title' => 'permit_empty|max_length[255]',
            'year' => 'permit_empty|integer|greater_than_equal_to[1900]|less_than_equal_to[9999]',
            's_text' => 'permit_empty|max_length[500]',
            'cover' => 'permit_empty|max_length[500]',
            'content' => 'permit_empty',
            'slide' => 'permit_empty',
            'ca_type' => 'permit_empty|max_length[255]',
            'ca_area' => 'permit_empty|max_length[255]',
            'ca_square' => 'permit_empty|max_length[255]',
            'ca_phone' => 'permit_empty|max_length[50]',
            'ca_adds' => 'permit_empty|max_length[500]',
            'ca_map' => 'permit_empty',
            'ca_pop_type' => 'permit_empty|max_length[255]',
            'ca_pop' => 'permit_empty|max_length[500]',
            'is_sale' => 'permit_empty|in_list[0,1]',
            'is_msg' => 'permit_empty|in_list[0,1]',
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

            // 處理各欄位更新
            if (isset($data['year'])) {
                $newYear = (int)$data['year'];
                if (($case['year'] ?? null) !== $newYear) {
                    $updateData['year'] = $newYear;
                }
            }
            if (isset($data['title'])) {
                $newTitle = trim($data['title']);
                if (($case['title'] ?? null) !== $newTitle) {
                    $updateData['title'] = $newTitle;
                }
            }
            if (isset($data['s_text'])) {
                $newSText = trim($data['s_text']);
                if (($case['s_text'] ?? null) !== $newSText) {
                    $updateData['s_text'] = $newSText;
                }
            }
            if (isset($data['cover'])) {
                $newCover = $data['cover'];
                if (($case['cover'] ?? null) !== $newCover) {
                    $updateData['cover'] = $newCover;
                }
            }
            if (isset($data['content'])) {
                $newContent = is_array($data['content']) 
                    ? json_encode($data['content'], JSON_UNESCAPED_UNICODE) 
                    : $data['content'];
                $currentContent = !empty($case['content']) 
                    ? json_encode(json_decode($case['content'], true), JSON_UNESCAPED_UNICODE) 
                    : null;
                
                if ($newContent !== $currentContent) {
                    $updateData['content'] = $newContent;
                }
            }
            if (isset($data['slide'])) {
                $newSlide = is_array($data['slide']) 
                    ? json_encode($data['slide'], JSON_UNESCAPED_UNICODE) 
                    : $data['slide'];
                $currentSlide = !empty($case['slide']) 
                    ? json_encode(json_decode($case['slide'], true), JSON_UNESCAPED_UNICODE) 
                    : null;
                
                if ($newSlide !== $currentSlide) {
                    $updateData['slide'] = $newSlide;
                }
            }
            if (isset($data['ca_type'])) {
                $newCaType = trim($data['ca_type']);
                if (($case['ca_type'] ?? null) !== $newCaType) {
                    $updateData['ca_type'] = $newCaType;
                }
            }
            if (isset($data['ca_area'])) {
                $newCaArea = trim($data['ca_area']);
                if (($case['ca_area'] ?? null) !== $newCaArea) {
                    $updateData['ca_area'] = $newCaArea;
                }
            }
            if (isset($data['ca_square'])) {
                $newCaSquare = trim($data['ca_square']);
                if (($case['ca_square'] ?? null) !== $newCaSquare) {
                    $updateData['ca_square'] = $newCaSquare;
                }
            }
            if (isset($data['ca_phone'])) {
                $newCaPhone = trim($data['ca_phone']);
                if (($case['ca_phone'] ?? null) !== $newCaPhone) {
                    $updateData['ca_phone'] = $newCaPhone;
                }
            }
            if (isset($data['ca_adds'])) {
                $newCaAdds = trim($data['ca_adds']);
                if (($case['ca_adds'] ?? null) !== $newCaAdds) {
                    $updateData['ca_adds'] = $newCaAdds;
                }
            }
            if (isset($data['ca_map'])) {
                $newCaMap = $data['ca_map'];
                if (($case['ca_map'] ?? null) !== $newCaMap) {
                    $updateData['ca_map'] = $newCaMap;
                }
            }
            if (isset($data['ca_pop_type'])) {
                $newCaPopType = trim($data['ca_pop_type']);
                if (($case['ca_pop_type'] ?? null) !== $newCaPopType) {
                    $updateData['ca_pop_type'] = $newCaPopType;
                }
            }
            if (isset($data['ca_pop'])) {
                $newCaPop = $data['ca_pop'];
                if (($case['ca_pop'] ?? null) !== $newCaPop) {
                    $updateData['ca_pop'] = $newCaPop;
                }
            }
            if (isset($data['is_sale'])) {
                $newIsSale = (int)$data['is_sale'];
                if ((int)($case['is_sale'] ?? 0) !== $newIsSale) {
                    $updateData['is_sale'] = $newIsSale;
                }
            }
            if (isset($data['is_msg'])) {
                $newIsMsg = (int)$data['is_msg'];
                if ((int)($case['is_msg'] ?? 0) !== $newIsMsg) {
                    $updateData['is_msg'] = $newIsMsg;
                }
            }
            if (isset($data['sort'])) {
                $newSort = (int)$data['sort'];
                if ((int)($case['sort'] ?? 0) !== $newSort) {
                    $updateData['sort'] = $newSort;
                }
            }
            if (isset($data['status'])) {
                $newStatus = (int)$data['status'];
                if ((int)($case['status'] ?? 1) !== $newStatus) {
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
            $updated = $this->appCaseModel->skipValidation(true)->update($id, $updateData);

            if (!$updated) {
                $error = $this->appCaseModel->errors();
                $response = [
                    'success' => false,
                    'message' => '更新建案失敗，請稍後再試',
                    'error' => 'Model update failed',
                    'model_errors' => $error,
                ];
                return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON($response);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => '更新建案成功',
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'updateCase failed: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '更新建案失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
    }

    /**
     * 刪除建案
     */
    public function delete()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();
        $id = $data['id'] ?? null;

        if (!$id) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                'success' => false,
                'message' => '缺少建案 ID',
            ]);
        }

        try {
            // 檢查建案是否存在
            $case = $this->appCaseModel->find($id);
            if (!$case) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_NOT_FOUND)->setJSON([
                    'success' => false,
                    'message' => '建案不存在',
                ]);
            }

            $deleted = $this->appCaseModel->delete($id);
            if (!$deleted) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                    'success' => false,
                    'message' => '刪除建案失敗，請稍後再試',
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'deleteCase failed: {message}', ['message' => $e->getMessage()]);
            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '刪除建案失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => '刪除建案成功',
        ]);
    }
}

