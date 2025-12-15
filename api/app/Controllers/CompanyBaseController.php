<?php
namespace App\Controllers;

use App\Models\CompanyBaseModel;
use CodeIgniter\HTTP\ResponseInterface;

class CompanyBaseController extends BaseController
{
    protected $companyBaseModel;

    public function __construct()
    {
        $this->companyBaseModel = new CompanyBaseModel();
    }

    /**
     * 取得公司基本資料（通常只有一筆）
     */
    public function get()
    {
        try {
            $company = $this->companyBaseModel->first();

            // 如果沒有資料，回傳空物件
            if (!$company) {
                return $this->response->setJSON([
                    'success' => true,
                    'data' => null,
                ]);
            }

            return $this->response->setJSON([
                'success' => true,
                'data' => $company,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'getCompanyBase failed: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '取得公司基本資料失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
    }

    /**
     * 新增或更新公司基本資料
     */
    public function save()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();

        $rules = [
            'name' => 'permit_empty|max_length[255]',
            'copyright' => 'permit_empty|max_length[255]',
            'phone' => 'permit_empty|max_length[50]',
            'fax' => 'permit_empty|max_length[50]',
            'email' => 'permit_empty|valid_email|max_length[255]',
            'case_email' => 'permit_empty|valid_email|max_length[255]',
            'zipcode' => 'permit_empty|max_length[10]',
            'city' => 'permit_empty|max_length[100]',
            'district' => 'permit_empty|max_length[100]',
            'address' => 'permit_empty|max_length[500]',
            'fb_url' => 'permit_empty|max_length[500]',
            'yt_url' => 'permit_empty|max_length[500]',
            'line_url' => 'permit_empty|max_length[500]',
            'keywords' => 'permit_empty',
            'description' => 'permit_empty',
            'head_code' => 'permit_empty',
            'body_code' => 'permit_empty',
        ];

        if (!$this->validateData($data, $rules)) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_UNPROCESSABLE_ENTITY)->setJSON([
                'success' => false,
                'message' => '驗證失敗',
                'errors' => $this->validator->getErrors(),
            ]);
        }

        try {
            // 檢查是否已存在資料
            $existing = $this->companyBaseModel->first();

            // 準備要儲存的資料（只包含 allowedFields 中的欄位）
            $saveData = [];
            $allowedFields = [
                'name',
                'copyright',
                'phone',
                'fax',
                'email',
                'case_email',
                'zipcode',
                'city',
                'district',
                'address',
                'fb_url',
                'yt_url',
                'line_url',
                'keywords',
                'description',
                'head_code',
                'body_code'
            ];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $saveData[$field] = $data[$field] === '' ? null : $data[$field];
                }
            }

            if ($existing) {
                // 更新現有資料
                $updated = $this->companyBaseModel->update($existing['id'], $saveData);

                if (!$updated) {
                    return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                        'success' => false,
                        'message' => '更新公司基本資料失敗，請稍後再試',
                    ]);
                }

                return $this->response->setJSON([
                    'success' => true,
                    'message' => '更新公司基本資料成功',
                ]);
            } else {
                // 新增資料
                $insertId = $this->companyBaseModel->insert($saveData);

                if (!$insertId) {
                    return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                        'success' => false,
                        'message' => '新增公司基本資料失敗，請稍後再試',
                    ]);
                }

                return $this->response->setJSON([
                    'success' => true,
                    'message' => '新增公司基本資料成功',
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'saveCompanyBase failed: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)->setJSON([
                'success' => false,
                'message' => '儲存公司基本資料失敗，請稍後再試',
                'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
            ]);
        }
    }
}
