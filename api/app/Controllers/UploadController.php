<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

class UploadController extends BaseController
{
    /**
     * 圖片上傳
     */
    public function image()
    {
        helper(['url']);

        $file = $this->request->getFile('image');

        if (!$file || !$file->isValid()) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                ->setJSON([
                    'success' => false,
                    'message' => '未取得有效的檔案'
                ]);
        }

        // 檢查檔案大小（5MB）
        $maxSize = 5 * 1024 * 1024;
        if ($file->getSize() > $maxSize) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                ->setJSON([
                    'success' => false,
                    'message' => '檔案大小超出限制 (5MB)'
                ]);
        }

        // 檢查副檔名與 MIME
        $allowedMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml'
        ];

        if (!in_array($file->getMimeType(), $allowedMimeTypes, true)) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                ->setJSON([
                    'success' => false,
                    'message' => '檔案格式不支援，僅允許 JPG/PNG/GIF/WebP/SVG'
                ]);
        }

        $uploadPath = FCPATH . 'uploads';


        if (!is_dir($uploadPath) && !mkdir($uploadPath, 0755, true)) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)
                ->setJSON([
                    'success' => false,
                    'message' => '無法建立上傳目錄'
                ]);
        }

        $newName = $file->getRandomName();

        try {
            $file->move($uploadPath, $newName);
        } catch (\Throwable $th) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)
                ->setJSON([
                    'success' => false,
                    'message' => '檔案儲存失敗'
                ]);
        }

        $url = base_url('uploads/' . $newName);

        return $this->response->setJSON([
            'success' => true,
            'url' => $url
        ]);
    }

}
