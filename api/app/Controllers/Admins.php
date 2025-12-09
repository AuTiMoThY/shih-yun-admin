<?php
namespace App\Controllers;

use App\Models\SysadminModel;

class Admins extends BaseController
{
    /**
     * 管理員登入：驗證帳號密碼並寫入 Session
     */
    public function login()
    {
        $payload = $this->request->getJSON(true) ?: $this->request->getPost();

        if (empty($payload['username']) || empty($payload['password'])) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => '請提供帳號與密碼',
            ]);
        }

        $model = new SysadminModel();
        $admin = $model->where('username', $payload['username'])->first();

        if (! $admin) {
            return $this->response->setStatusCode(401)->setJSON([
                'success' => false,
                'message' => '帳號或密碼錯誤',
            ]);
        }

        if ((int) $admin['status'] !== 1) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => '帳號已停用',
            ]);
        }

        if (! password_verify($payload['password'], $admin['password_hash'])) {
            return $this->response->setStatusCode(401)->setJSON([
                'success' => false,
                'message' => '帳號或密碼錯誤',
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

    public function addAdmin()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();

        // 將布林/字串狀態轉成 '0'/'1'，避免驗證 in_list[0,1] 失敗
        $truthy = ['1', 1, true, 'true', 'on', 'yes'];
        $status  = $data['status'] ?? null;
        $data['status'] = in_array($status, $truthy, true) ? '1' : '0';

        $rules = [
            'permission_name'        => 'required',
            'status'                 => 'required|in_list[0,1]',
            'username'               => 'required|min_length[3]|is_unique[sysadmin.username]',
            'password'               => 'required',
            'password_confirmation'  => 'required|matches[password]',
            'name'                   => 'required',
            'phone'                  => 'permit_empty',
            'address'                => 'permit_empty',
        ];

        if (! $this->validateData($data, $rules)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => '驗證失敗',
                'errors'  => $this->validator->getErrors(),
            ]);
        }

        $model = new SysadminModel();
        try {
            $insertId = $model->insert([
                'permission_name' => $data['permission_name'],
                'status'          => (int) $data['status'],
                'username'        => $data['username'],
                'password_hash'   => password_hash($data['password'], PASSWORD_DEFAULT),
                'name'            => $data['name'],
                'phone'           => $data['phone'] ?? null,
                'address'         => $data['address'] ?? null,
            ]);
        
            if (! $insertId) {
                // 可能是 DB 拒絕（如 unique key），回傳 500
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'message' => '新增管理員失敗，請稍後再試',
                ]);
            }
        
            return $this->response->setJSON([
                'success' => true,
                'message' => '新增管理員成功',
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'addAdmin failed: {message}', ['message' => $e->getMessage()]);
        
            // 若有 SQL 重複鍵錯誤，可在這裡判斷並回 409
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => '新增管理員失敗，請稍後再試',
                'error'   => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
    }

    public function getAdmins()
    {
        $model = new SysadminModel();
        $admins = $model->findAll();

        return $this->response->setJSON([
            'success' => true,
            'data'    => $admins,
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
            return $this->response->setStatusCode(401)->setJSON([
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