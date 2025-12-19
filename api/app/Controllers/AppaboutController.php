<?php
namespace App\Controllers;

use App\Models\AppAboutModel;
use CodeIgniter\HTTP\ResponseInterface;

class AppAboutController extends BaseController
{
    protected $appAboutModel;

    public function __construct()
    {
        try {
            $this->appAboutModel = new AppAboutModel();
            log_message('debug', 'AppAboutController: Model initialized successfully');
        } catch (\Throwable $e) {
            log_message('error', 'AppAboutController constructor failed: {message}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * 取得關於頁設定
     */
    public function get()
    {
        try {
            log_message('debug', 'AppAboutController::get: Starting to fetch data');

            $structureId = $this->request->getGet('structure_id');
            $query = $this->appAboutModel->orderBy('id', 'DESC');
            
            // 如果提供了 structure_id，則過濾該單元的資料
            if ($structureId !== null) {
                $query->where('structure_id', (int)$structureId);
            }

            $existing = $query->first();

            log_message('debug', 'AppAboutController::get: Query executed', [
                'existing' => $existing ? 'found' : 'not found'
            ]);

            if (!$existing) {
                log_message('debug', 'AppAboutController::get: No existing data, returning empty');
                return $this->response->setJSON([
                    'success' => true,
                    'data' => [
                        'title' => null,
                        'sections' => []
                    ]
                ]);
            }

            $sectionsJson = $existing['sections_json'] ?? null;
            log_message('debug', 'AppAboutController::get: Decoding sections JSON', [
                'sections_json_length' => $sectionsJson ? strlen($sectionsJson) : 0
            ]);

            $sections = json_decode($sectionsJson, true);
            $jsonError = json_last_error();

            if ($jsonError !== JSON_ERROR_NONE) {
                $errorMsg = json_last_error_msg();
                log_message('error', 'AppAboutController::get: JSON decode failed', [
                    'error_code' => $jsonError,
                    'error_message' => $errorMsg,
                    'sections_json_preview' => substr($sectionsJson, 0, 200)
                ]);
                throw new \RuntimeException("JSON 解析失敗: {$errorMsg}");
            }

            $responseData = [
                'success' => true,
                'data' => [
                    'id' => $existing['id'],
                    'title' => $existing['title'],
                    'sections' => $sections ?? []
                ]
            ];

            log_message('debug', 'AppAboutController::get: Success', [
                'id' => $existing['id'],
                'sections_count' => is_array($sections) ? count($sections) : 0
            ]);

            return $this->response->setJSON($responseData);
        } catch (\Throwable $e) {
            log_message('error', 'AppAboutController::get failed: {message}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)
                ->setJSON([
                    'success' => false,
                    'message' => '取得資料失敗，請稍後再試',
                    'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
                    'debug' => ENVIRONMENT !== 'production' ? [
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ] : null,
                ]);
        }
    }

    /**
     * 儲存關於頁設定
     */
    public function save()
    {
        try {
            log_message('debug', 'AppAboutController::save: Starting save operation');

            $payload = $this->request->getJSON(true);

            if ($payload === null) {
                $rawInput = $this->request->getBody();
                log_message('error', 'AppAboutController::save: Invalid JSON payload', [
                    'raw_input_preview' => substr($rawInput, 0, 500)
                ]);
                return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                    ->setJSON([
                        'success' => false,
                        'message' => '無效的 JSON 資料',
                        'error' => ENVIRONMENT !== 'production' ? 'JSON parse failed' : null,
                    ]);
            }

            log_message('debug', 'AppAboutController::save: Payload received', [
                'has_title' => isset($payload['title']),
                'has_sections' => isset($payload['sections']),
                'sections_count' => isset($payload['sections']) && is_array($payload['sections']) ? count($payload['sections']) : 0
            ]);

            $title = $payload['title'] ?? null;
            $sections = $payload['sections'] ?? null;
            $structureId = $payload['structure_id'] ?? null;

            if ($sections === null) {
                log_message('error', 'AppAboutController::save: Missing sections data');
                return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                    ->setJSON([
                        'success' => false,
                        'message' => '缺少 sections 資料'
                    ]);
            }

            $query = $this->appAboutModel->orderBy('id', 'DESC');
            
            // 如果提供了 structure_id，則查詢該單元的資料
            if ($structureId !== null) {
                $query->where('structure_id', (int)$structureId);
            }

            $existing = $query->first();

            log_message('debug', 'AppAboutController::save: Existing record check', [
                'existing' => $existing ? 'found' : 'not found',
                'existing_id' => $existing['id'] ?? null
            ]);

            $sectionsJson = json_encode($sections, JSON_UNESCAPED_UNICODE);
            $jsonError = json_last_error();

            if ($jsonError !== JSON_ERROR_NONE) {
                $errorMsg = json_last_error_msg();
                log_message('error', 'AppAboutController::save: JSON encode failed', [
                    'error_code' => $jsonError,
                    'error_message' => $errorMsg
                ]);
                throw new \RuntimeException("JSON 編碼失敗: {$errorMsg}");
            }

            $data = [
                'structure_id' => $structureId !== null ? (int)$structureId : null,
                'title' => $title,
                'sections_json' => $sectionsJson,
                'status' => 1
            ];

            log_message('debug', 'AppAboutController::save: Prepared data', [
                'data_size' => strlen($sectionsJson)
            ]);

            if ($existing) {
                $updated = $this->appAboutModel->update($existing['id'], $data);
                $id = $existing['id'];

                if (!$updated) {
                    $errors = $this->appAboutModel->errors();
                    log_message('error', 'AppAboutController::save: Update failed', [
                        'id' => $id,
                        'errors' => $errors
                    ]);
                    return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)
                        ->setJSON([
                            'success' => false,
                            'message' => '更新失敗，請稍後再試',
                            'error' => ENVIRONMENT !== 'production' ? 'Model update failed' : null,
                            'model_errors' => ENVIRONMENT !== 'production' ? $errors : null,
                        ]);
                }

                log_message('debug', 'AppaboutController::save: Update successful', ['id' => $id]);
            } else {
                $id = $this->appAboutModel->insert($data, true);

                if (!$id) {
                    $errors = $this->appAboutModel->errors();
                    log_message('error', 'AppAboutController::save: Insert failed', [
                        'errors' => $errors
                    ]);
                    return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)
                        ->setJSON([
                            'success' => false,
                            'message' => '新增失敗，請稍後再試',
                            'error' => ENVIRONMENT !== 'production' ? 'Model insert failed' : null,
                            'model_errors' => ENVIRONMENT !== 'production' ? $errors : null,
                        ]);
                }

                log_message('debug', 'AppAboutController::save: Insert successful', ['id' => $id]);
            }

            return $this->response->setJSON([
                'success' => true,
                'id' => $id
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'AppAboutController::save failed: {message}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)
                ->setJSON([
                    'success' => false,
                    'message' => '儲存失敗，請稍後再試',
                    'error' => ENVIRONMENT !== 'production' ? $e->getMessage() : null,
                    'debug' => ENVIRONMENT !== 'production' ? [
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ] : null,
                ]);
        }
    }
}