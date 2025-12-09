<?php

namespace App\Controllers;

class testDbConnection2 extends BaseController
{
    public function index()
    {
        $data = [
            'connection_success' => false,
            'query_success' => false,
            'result' => null,
            'error' => null,
            'connection_info' => null
        ];

        try {
            // 嘗試連線資料庫
            $db = db_connect();
            
            if ($db) {
                $data['connection_success'] = true;
                $data['connection_info'] = [
                    'database' => $db->database ?? 'N/A',
                    'platform' => $db->getPlatform() ?? 'N/A'
                ];

                // 嘗試執行查詢（使用一個簡單的查詢來測試）
                try {
                    $result = $db->query('SELECT 1 as test');
                    if ($result) {
                        $data['query_success'] = true;
                        $data['result'] = $result->getResultArray();
                    } else {
                        $data['error'] = '查詢執行失敗';
                    }
                } catch (\Exception $e) {
                    $data['error'] = '查詢錯誤: ' . $e->getMessage();
                }
            } else {
                $data['error'] = '無法建立資料庫連線';
            }
        } catch (\Exception $e) {
            $data['error'] = '連線錯誤: ' . $e->getMessage();
        }

        return view('test_db_connection2', $data);
    }
}