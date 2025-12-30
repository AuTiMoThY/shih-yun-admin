<?php
namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

class PasswordHashController extends BaseController
{
    /**
     * 產生密碼雜湊值
     * 使用方式：GET /password-hash?password=your_password
     * 
     * @return ResponseInterface
     */
    public function hash()
    {
        try {
            // 從 URL 參數取得密碼
            $password = $this->request->getGet('password');

            // 驗證密碼是否存在
            if (empty($password)) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                    'success' => false,
                    'message' => '請提供密碼參數',
                    'usage' => 'GET /password-hash?password=your_password',
                ]);
            }

            // 驗證密碼長度（建議至少 8 個字元）
            if (strlen($password) < 8) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                    'success' => false,
                    'message' => '密碼長度至少需要 8 個字元',
                ]);
            }

            // 產生密碼雜湊值
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);

            // 驗證雜湊值是否成功產生
            if ($passwordHash === false) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                    'success' => false,
                    'message' => '密碼雜湊產生失敗',
                ]);
            }

            $responseData = [
                'success' => true,
                'data' => [
                    'hash' => $passwordHash,
                    'algorithm' => 'PASSWORD_BCRYPT',
                ],
                'message' => '密碼雜湊產生成功',
            ];

            // 僅在非生產環境顯示原始密碼（用於除錯）
            if (ENVIRONMENT !== 'production') {
                $responseData['data']['password'] = $password;
            }

            return $this->response->setJSON($responseData);
        } catch (\Throwable $e) {
            log_message('error', 'PasswordHash failed: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '產生密碼雜湊失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
    }
}

