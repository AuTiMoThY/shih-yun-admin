<?php

namespace App\Controllers;

use Config\Database;

class Home extends BaseController
{
    public function index(): string
    {
        return view('welcome_message');
    }

    /**
     * Preflight handler to ensure OPTIONS requests get CORS headers.
     */
    public function options()
    {
        return $this->response->setStatusCode(204);
    }
}
