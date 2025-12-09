<?php
namespace App\Controllers;

use App\Models\SysadminModel;

class Admins extends BaseController
{
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
}