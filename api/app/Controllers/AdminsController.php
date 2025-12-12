<?php
namespace App\Controllers;

use App\Models\SysadminModel;
use CodeIgniter\HTTP\ResponseInterface;

class AdminsController extends BaseController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new SysadminModel();
    }

    public function addAdmin()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();

        // 將布林/字串狀態轉成 '0'/'1'，避免驗證 in_list[0,1] 失敗
        $truthy = ['1', 1, true, 'true', 'on', 'yes'];
        $status = $data['status'] ?? null;
        $data['status'] = in_array($status, $truthy, true) ? '1' : '0';

        $rules = [
            'permission_name' => 'required',
            'status' => 'required|in_list[0,1]',
            'username' => 'required|min_length[3]|is_unique[sysadmin.username]',
            'password' => 'required',
            'password_confirmation' => 'required|matches[password]',
            'name' => 'required',
            'phone' => 'permit_empty',
            'address' => 'permit_empty',
        ];

        if (!$this->validateData($data, $rules)) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_UNPROCESSABLE_ENTITY)->setJSON([
                'success' => false,
                'message' => '驗證失敗',
                'errors' => $this->validator->getErrors(),
            ]);
        }

        try {
            $insertId = $this->userModel->insert([
                'permission_name' => $data['permission_name'],
                'status' => (int) $data['status'],
                'username' => $data['username'],
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
            ]);

            if (!$insertId) {
                // 可能是 DB 拒絕（如 unique key），回傳 500
                return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
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
            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '新增管理員失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
    }

    public function getAdmins()
    {
        $admins = $this->userModel->findAll();

        return $this->response->setJSON([
            'success' => true,
            'data' => $admins,
        ]);
    }

    public function updateAdmin()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();

        $id = $data['id'] ?? null;
        if (!$id) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                'success' => false,
                'message' => '缺少管理員 ID',
            ]);
        }

        // 檢查管理員是否存在
        $admin = $this->userModel->find($id);
        if (!$admin) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_NOT_FOUND)->setJSON([
                'success' => false,
                'message' => '管理員不存在',
            ]);
        }

        // 將布林/字串狀態轉成 '0'/'1'
        $truthy = ['1', 1, true, 'true', 'on', 'yes'];
        $status = $data['status'] ?? null;
        $data['status'] = in_array($status, $truthy, true) ? '1' : '0';

        // 構建驗證規則
        $rules = [
            'permission_name' => 'required',
            'status' => 'in_list[0,1]',
            'username' => "required|min_length[3]|is_unique[sysadmin.username,id,{$id}]",
            'name' => 'required',
            'phone' => 'permit_empty',
            'address' => 'permit_empty',
        ];

        // 如果提供了密碼，則需要驗證
        if (isset($data['password']) && $data['password'] !== '') {
            $rules['password'] = 'required';
            $rules['password_confirmation'] = 'required|matches[password]';
        }

        if (!$this->validateData($data, $rules)) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_UNPROCESSABLE_ENTITY)->setJSON([
                'success' => false,
                'message' => '驗證失敗',
                'errors' => $this->validator->getErrors(),
            ]);
        }

        try {
            $updateData = [
                'permission_name' => $data['permission_name'],
                'status' => (int) $data['status'],
                'username' => $data['username'],
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
            ];

            // 只有當密碼有提供時才更新
            if (isset($data['password']) && $data['password'] !== '') {
                $updateData['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            $updated = $this->userModel->update($id, $updateData);

            if (!$updated) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                    'success' => false,
                    'message' => '更新管理員失敗，請稍後再試',
                ]);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => '更新管理員成功',
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'updateAdmin failed: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '更新管理員失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
    }

    public function deleteAdmin()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();
        $id = $data['id'] ?? null;
        if (!$id) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                'success' => false,
                'message' => '缺少管理員 ID',
            ]);
        }
        try {
            // 檢查是否只剩下一個管理員帳號
            $totalAdmins = $this->userModel->countAllResults();
            if ($totalAdmins <= 1) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
                    'success' => false,
                    'message' => '無法刪除，系統至少需要保留一個管理員帳號',
                ]);
            }
            
            $deleted = $this->userModel->delete($id);
            if (!$deleted) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                    'success' => false,
                    'message' => '刪除管理員失敗，請稍後再試',
                ]);
            }
        }
        catch (\Throwable $e) {
            log_message('error', 'deleteAdmin failed: {message}', ['message' => $e->getMessage()]);
            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '刪除管理員失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
        return $this->response->setJSON([
            'success' => true,
            'message' => '刪除管理員成功',
        ]);
    }
}