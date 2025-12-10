<?php
namespace App\Controllers;

use App\Models\SysadminModel;
use CodeIgniter\HTTP\ResponseInterface;

class AuthController extends BaseController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new SysadminModel();
    }
    /**
     * 管理員登入：驗證帳號密碼並寫入 Session
     */
    public function login()
    {
        $payload = $this->request->getJSON(true) ?: $this->request->getPost();

        $rules = [
            'username' => 'required',
            'password' => 'required',
        ];

        if (! $this->validateData($payload, $rules)) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                'success' => false,
                'message' => '請提供帳號與密碼',
            ]);
        }

        $admin = $this->userModel->where('username', $payload['username'])->first();



        if (! $admin) {
            // HTTP_UNAUTHORIZED = 401 (未授權)
            return $this->response->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)->setJSON([
                'success' => false,
                'message' => '帳號不存在',
            ]);
        }

        if ((int) $admin['status'] !== 1) {
            // HTTP_FORBIDDEN = 403 (禁止存取)
            return $this->response->setStatusCode(ResponseInterface::HTTP_FORBIDDEN)->setJSON([
                'success' => false,
                'message' => '帳號已停用',
            ]);
        }

        if (! password_verify($payload['password'], $admin['password_hash'])) {
            // HTTP_UNAUTHORIZED = 401 (未授權)
            return $this->response->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)->setJSON([
                'success' => false,
                'message' => '密碼錯誤',
            ]);
        }

        // 建立 Session
        $session = session();
        $session->regenerate(true);

        $user = [
            'id'              => $admin['id'],
            'permission_name' => $admin['permission_name'],
            'status'          => (int) $admin['status'],
            'username'        => $admin['username'],
            'name'            => $admin['name'],
            'phone'           => $admin['phone'],
            'address'         => $admin['address'],
            'created_at'      => $admin['created_at'],
            'updated_at'      => $admin['updated_at'],
        ];

        $session->set('admin_user', $user);

        return $this->response->setJSON([
            'success' => true,
            'message' => '登入成功',
            'data'    => [
                'user'  => $user,
                'token' => session_id(),
            ],
        ]);
    }
    /**
     * 取得目前登入的管理員資料
     */
    public function me()
    {
        $session = session();
        $user = $session->get('admin_user');

        if (! $user) {
            // HTTP_UNAUTHORIZED = 401 (未授權)
            return $this->response->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)->setJSON([
                'success' => false,
                'message' => '尚未登入',
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'data'    => $user,
        ]);
    }

    /**
     * 登出並清除 Session
     */
    public function logout()
    {
        $session = session();
        $session->remove('admin_user');
        $session->destroy();

        return $this->response->setJSON([
            'success' => true,
            'message' => '已登出',
        ]);
    }
}