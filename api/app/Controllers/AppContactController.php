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
                'structure_id' => isset($data['structure_id']) ? (int)$data['structure_id'] : null,
                'name' => trim($data['name']),
                'phone' => trim($data['phone']),
                'email' => trim($data['email']),
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
            $structureId = $this->request->getGet('structure_id');
            $query = $this->appContactModel->orderBy('created_at', 'DESC')->orderBy('id', 'DESC');

            // 如果提供了 structure_id，則過濾該單元的資料
            if ($structureId !== null) {
                $query->where('structure_id', (int)$structureId);
            }

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
     * 後台更新回信內容
     */
    public function updateReply()
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
            'reply' => 'permit_empty',
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
                'reply' => isset($data['reply']) ? trim($data['reply']) : null,
            ];

            $updated = $this->appContactModel->skipValidation(true)->update($id, $updateData);

            if (!$updated) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                    'success' => false,
                    'message' => '更新回信失敗，請稍後再試',
                ]);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => '更新回信成功',
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'updateContactReply failed: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '更新回信失敗，請稍後再試',
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

    /**
     * 後台發送回信郵件
     */
    public function sendEmail()
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

        // 檢查是否有回信內容
        if (empty($contact['reply'])) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                'success' => false,
                'message' => '回信內容為空，請先填寫回信內容',
            ]);
        }

        // 檢查收件人信箱是否有效
        if (empty($contact['email']) || !filter_var($contact['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                'success' => false,
                'message' => '收件人信箱無效',
            ]);
        }

        try {
            $email = \Config\Services::email();

            // 設定郵件配置（從環境變數或配置檔讀取）
            $emailConfig = config('Email');
            
            // 設定發件人（如果配置檔中有設定）
            if (!empty($emailConfig->fromEmail)) {
                $email->setFrom($emailConfig->fromEmail, $emailConfig->fromName ?? '');
            }

            // 設定收件人
            $email->setTo($contact['email']);

            // 設定郵件主題
            $email->setSubject('回覆您的聯絡表單 - ' . ($contact['name'] ?? '客戶'));

            // 設定郵件內容（HTML 格式）
            $email->setMailType('html');
            
            // 建立郵件內容
            $emailBody = $this->buildEmailBody($contact);
            $email->setMessage($emailBody);

            // 發送郵件
            if (!$email->send()) {
                log_message('error', '發送郵件失敗: {error}', ['error' => $email->printDebugger(['headers'])]);
                
                return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                    'success' => false,
                    'message' => '發送郵件失敗：' . ($email->printDebugger(['headers']) ?? '未知錯誤'),
                ]);
            }

            // 更新狀態為已處理（如果尚未處理）
            if ($contact['status'] == 0) {
                $this->appContactModel->skipValidation(true)->update($id, ['status' => 1]);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => '郵件發送成功',
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'sendContactEmail failed: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '發送郵件失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
    }

    /**
     * 建立郵件內容
     */
    private function buildEmailBody($contact): string
    {
        $name = htmlspecialchars($contact['name'] ?? '客戶', ENT_QUOTES, 'UTF-8');
        $reply = $contact['reply'] ?? '';
        
        // 如果回信內容是 HTML，直接使用；否則轉換為 HTML
        $replyHtml = strip_tags($reply) !== $reply ? $reply : nl2br(htmlspecialchars($reply, ENT_QUOTES, 'UTF-8'));

        $html = '<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>回覆您的聯絡表單</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
        <h2 style="color: #333; margin-top: 0;">親愛的 ' . $name . '，您好：</h2>
    </div>
    
    <div style="background-color: #ffffff; padding: 20px; border: 1px solid #e0e0e0; border-radius: 5px; margin-bottom: 20px;">
        <p style="margin-top: 0;">感謝您與我們聯繫，以下是我們對您留言的回覆：</p>
        <div style="background-color: #f8f9fa; padding: 15px; border-left: 4px solid #007bff; margin: 20px 0;">
            ' . $replyHtml . '
        </div>
    </div>
    
    <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 20px; font-size: 12px; color: #666;">
        <p style="margin: 0;">此為自動發送郵件，請勿直接回覆。</p>
        <p style="margin: 5px 0 0 0;">如有任何問題，歡迎再次與我們聯繫。</p>
    </div>
</body>
</html>';

        return $html;
    }
}
