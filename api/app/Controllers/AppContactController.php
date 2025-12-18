<?php
namespace App\Controllers;

use App\Models\AppContactModel;
use CodeIgniter\HTTP\ResponseInterface;

class AppContactController extends BaseController
{
    protected $appContactModel;

    public function __construct()
    {
        $this->appContactModel = new AppContactModel();
    }

    /**
     * 前台提交聯絡表單
     */
    public function submit()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();

        $rules = [
            'name' => 'required|min_length[1]|max_length[255]',
            'phone' => 'required|min_length[1]|max_length[50]',
            'email' => 'required|valid_email|max_length[255]',
            'project' => 'permit_empty|max_length[255]',
            'message' => 'permit_empty',
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
                'name' => trim($data['name']),
                'phone' => trim($data['phone']),
                'email' => trim($data['email']),
                'project' => isset($data['project']) && !empty(trim($data['project'])) ? trim($data['project']) : null,
                'message' => isset($data['message']) && !empty(trim($data['message'])) ? trim($data['message']) : null,
                'status' => 0, // 預設為待處理
            ];

            $insertId = $this->appContactModel->insert($insertData);

            if (!$insertId) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                    'success' => false,
                    'message' => '提交表單失敗，請稍後再試',
                ]);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => '表單提交成功，我們將盡快與您聯繫',
                'data' => [
                    'id' => $insertId,
                ],
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'submitContact failed: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '提交表單失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
    }

    /**
     * 後台取得聯絡表單列表
     */
    public function get()
    {
        try {
            $status = $this->request->getGet('status');
            $query = $this->appContactModel->orderBy('created_at', 'DESC')->orderBy('id', 'DESC');

            if ($status !== null) {
                $query->where('status', (int)$status);
            }

            $contacts = $query->findAll();

            return $this->response->setJSON([
                'success' => true,
                'data' => $contacts,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'getContact failed: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '取得聯絡表單失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
    }

    /**
     * 後台取得單一聯絡表單
     */
    public function getById()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getGet();
        $id = $data['id'] ?? null;

        if (!$id) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                'success' => false,
                'message' => '缺少聯絡表單 ID',
            ]);
        }

        try {
            $contact = $this->appContactModel->find($id);
            if (!$contact) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_NOT_FOUND)->setJSON([
                    'success' => false,
                    'message' => '聯絡表單不存在',
                ]);
            }

            return $this->response->setJSON([
                'success' => true,
                'data' => $contact,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'getContactById failed: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '取得聯絡表單失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
    }

    /**
     * 後台更新處理狀態
     */
    public function updateStatus()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();

        $id = $data['id'] ?? null;
        if (!$id) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                'success' => false,
                'message' => '缺少聯絡表單 ID',
            ]);
        }

        // 檢查聯絡表單是否存在
        $contact = $this->appContactModel->find($id);
        if (!$contact) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_NOT_FOUND)->setJSON([
                'success' => false,
                'message' => '聯絡表單不存在',
            ]);
        }

        $rules = [
            'status' => 'required|in_list[0,1,2]',
        ];

        if (!$this->validateData($data, $rules)) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_UNPROCESSABLE_ENTITY)->setJSON([
                'success' => false,
                'message' => '驗證失敗',
                'errors' => $this->validator->getErrors(),
            ]);
        }

        try {
            $updateData = [
                'status' => (int)$data['status'],
            ];

            $updated = $this->appContactModel->skipValidation(true)->update($id, $updateData);

            if (!$updated) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                    'success' => false,
                    'message' => '更新狀態失敗，請稍後再試',
                ]);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => '更新狀態成功',
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'updateContactStatus failed: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '更新狀態失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
    }

    /**
     * 後台刪除聯絡表單
     */
    public function delete()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();
        $id = $data['id'] ?? null;

        if (!$id) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                'success' => false,
                'message' => '缺少聯絡表單 ID',
            ]);
        }

        try {
            // 檢查聯絡表單是否存在
            $contact = $this->appContactModel->find($id);
            if (!$contact) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_NOT_FOUND)->setJSON([
                    'success' => false,
                    'message' => '聯絡表單不存在',
                ]);
            }

            $deleted = $this->appContactModel->delete($id);
            if (!$deleted) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                    'success' => false,
                    'message' => '刪除聯絡表單失敗，請稍後再試',
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'deleteContact failed: {message}', ['message' => $e->getMessage()]);
            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '刪除聯絡表單失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => '刪除聯絡表單成功',
        ]);
    }
}
