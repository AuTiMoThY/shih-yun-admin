<?php

namespace App\Controllers;

class testCors extends BaseController
{
    public function index()
    {
        return $this->response->setJSON([
            'success' => true,
            'message' => 'CORS test successful'
        ]);
    }
}