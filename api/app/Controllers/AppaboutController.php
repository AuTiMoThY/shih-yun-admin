<?php
namespace App\Controllers;

use App\Models\AppaboutModel;
use CodeIgniter\HTTP\ResponseInterface;

class AppaboutController extends BaseController
{
    protected $appaboutModel;

    public function __construct()
    {
        $this->appaboutModel = new AppaboutModel();
    }

    /**
     * 取得關於頁設定
     */
    public function get()
    {
        $existing = $this->appaboutModel
            ->orderBy('id', 'DESC')
            ->first();

        if (!$existing) {
            return $this->response->setJSON([
                'success' => true,
                'data' => [
                    'title' => null,
                    'sections' => []
                ]
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'data' => [
                'id' => $existing['id'],
                'title' => $existing['title'],
                'sections' => json_decode($existing['sections_json'], true) ?? []
            ]
        ]);
    }

    /**
     * 儲存關於頁設定
     */
    public function save()
    {
        $payload = $this->request->getJSON(true);
        $title = $payload['title'] ?? null;
        $sections = $payload['sections'] ?? null;

        if ($sections === null) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                ->setJSON([
                    'success' => false,
                    'message' => '缺少 sections 資料'
                ]);
        }

        $existing = $this->appaboutModel
            ->orderBy('id', 'DESC')
            ->first();

        $data = [
            'title' => $title,
            'sections_json' => json_encode($sections, JSON_UNESCAPED_UNICODE),
            'status' => 1
        ];

        if ($existing) {
            $this->appaboutModel->update($existing['id'], $data);
            $id = $existing['id'];
        } else {
            $id = $this->appaboutModel->insert($data, true);
        }

        return $this->response->setJSON([
            'success' => true,
            'id' => $id
        ]);
    }
}