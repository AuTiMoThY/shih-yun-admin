<?php

namespace App\Controllers;

use Config\Database;

class testDbConnection extends BaseController
{
    // db_connect() 是 CodeIgniter 4 的核心輔助函數，無需手動載入
    public function index()
    {
        $data = [
            'success' => false,
            'message' => '',
            'config' => [],
            'connection_info' => [],
            'test_query' => null,
            'error' => null,
            'error_details' => [],
            'debug_info' => []
        ];

        try {
            // 獲取資料庫配置
            $dbConfig = config('Database');
            $defaultConfig = $dbConfig->default;

            // 準備配置資訊（隱藏密碼）
            $data['config'] = [
                'hostname' => $defaultConfig['hostname'] ?? 'N/A',
                'username' => $defaultConfig['username'] ?? 'N/A',
                'password' => !empty($defaultConfig['password']) ? '***已設定***' : '未設定',
                'database' => $defaultConfig['database'] ?? 'N/A',
                'DBDriver' => $defaultConfig['DBDriver'] ?? 'N/A',
                'port' => $defaultConfig['port'] ?? 'N/A',
                'charset' => $defaultConfig['charset'] ?? 'N/A',
                'DSN' => $defaultConfig['DSN'] ?? 'N/A',
            ];

            // 收集調試資訊
            $data['debug_info']['php_version'] = PHP_VERSION;
            $data['debug_info']['codeigniter_version'] = \CodeIgniter\CodeIgniter::CI_VERSION;
            $data['debug_info']['environment'] = ENVIRONMENT;
            
            // 檢查必要的擴展
            $data['debug_info']['extensions'] = [
                'mysqli' => extension_loaded('mysqli'),
                'pdo' => extension_loaded('pdo'),
                'pdo_mysql' => extension_loaded('pdo_mysql'),
            ];

            // 驗證配置
            $configErrors = [];
            if (empty($defaultConfig['hostname'])) {
                $configErrors[] = '主機名稱未設定';
            }
            if (empty($defaultConfig['database'])) {
                $configErrors[] = '資料庫名稱未設定';
            }
            if (!empty($configErrors)) {
                $data['error_details']['config_errors'] = $configErrors;
            }

            // 嘗試連接資料庫
            try {
                // 收集所有錯誤訊息
                $errorMessages = [];
                
                // 設置錯誤處理來捕獲連接錯誤
                set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$data, &$errorMessages) {
                    $errorMessages[] = [
                        'level' => $errno,
                        'message' => $errstr,
                        'file' => $errfile,
                        'line' => $errline
                    ];
                    
                    if (strpos($errstr, 'errno') !== false || 
                        strpos($errstr, 'connect') !== false ||
                        strpos($errstr, 'database') !== false ||
                        strpos($errstr, 'mysqli') !== false) {
                        if (!isset($data['error_details']['php_error'])) {
                            $data['error_details']['php_error'] = [
                                'message' => $errstr,
                                'file' => $errfile,
                                'line' => $errline,
                                'code' => $errno
                            ];
                        }
                    }
                    return false; // 繼續執行標準錯誤處理
                }, E_WARNING | E_NOTICE | E_ERROR);
                
                // 記錄連接前的配置
                $data['error_details']['connection_attempt'] = [
                    'hostname' => $defaultConfig['hostname'] ?? 'N/A',
                    'username' => $defaultConfig['username'] ?? 'N/A',
                    'database' => $defaultConfig['database'] ?? 'N/A',
                    'port' => $defaultConfig['port'] ?? 'N/A',
                    'DBDriver' => $defaultConfig['DBDriver'] ?? 'N/A',
                    'DSN' => $defaultConfig['DSN'] ?? 'N/A',
                ];
                
                // 使用 db_connect() 輔助函數（CodeIgniter 4 推薦方式）
                // 這與 testDbConnection2.php 中成功的方式相同
                $db = db_connect();
                
                // 恢復錯誤處理
                restore_error_handler();
                
                // 保存所有錯誤訊息
                if (!empty($errorMessages)) {
                    $data['error_details']['all_php_errors'] = $errorMessages;
                }
                
                // 使用與 testDbConnection2.php 相同的簡單檢查方式
                // db_connect() 成功時會返回資料庫連接對象，失敗時返回 false 或拋出異常
                if ($db) {
                    $data['success'] = true;
                    $data['message'] = '資料庫連線成功！';
                    
                    // 記錄使用的連接方式
                    $data['error_details']['connection_method'] = 'db_connect() 輔助函數（與 testDbConnection2.php 相同）';
                    
                    // 獲取連線資訊（與 testDbConnection2.php 相同的方式）
                    try {
                        $data['connection_info'] = [
                            'database' => $db->database ?? 'N/A',
                            'platform' => method_exists($db, 'getPlatform') ? $db->getPlatform() : 'N/A'
                        ];
                        
                        // 嘗試獲取更多資訊
                        if (method_exists($db, 'getVersion')) {
                            $data['connection_info']['version'] = $db->getVersion();
                        }
                        
                        if (method_exists($db, 'getDatabase')) {
                            $dbName = $db->getDatabase();
                            if ($dbName) {
                                $data['connection_info']['database'] = $dbName;
                            }
                        }
                    } catch (\Exception $e) {
                        // 忽略資訊獲取錯誤
                    }
                    
                    // 執行測試查詢（與 testDbConnection2.php 相同的方式）
                    try {
                        $result = $db->query('SELECT 1 as test_value, NOW() as current_time, DATABASE() as current_database');
                        if ($result) {
                            $data['test_query'] = $result->getRowArray();
                        } else {
                            $data['test_query'] = ['error' => '查詢執行失敗'];
                        }
                    } catch (\Exception $e) {
                        $data['test_query'] = ['error' => '查詢錯誤: ' . $e->getMessage()];
                    }
                } else {
                    $data['success'] = false;
                    $data['message'] = '無法建立資料庫連線 - db_connect() 返回 false';
                    
                    // 詳細檢查連接對象狀態
                    $data['error_details']['connection_object_analysis'] = [];
                    
                    // 如果 $db 是 false，無法訪問其屬性
                    if ($db !== false && $db !== null) {
                        try {
                            // 檢查 connID 的實際值
                            if (isset($db->connID)) {
                                $connIdType = gettype($db->connID);
                                $connIdValue = $db->connID;
                                
                                $data['error_details']['connection_object_analysis'][] = "connID 類型: {$connIdType}";
                                
                                if ($connIdType === 'boolean') {
                                    $data['error_details']['connection_object_analysis'][] = "connID 值: " . ($connIdValue ? 'true' : 'false');
                                } elseif ($connIdType === 'object') {
                                    $data['error_details']['connection_object_analysis'][] = "connID 類別: " . get_class($connIdValue);
                                    if ($connIdValue instanceof \mysqli) {
                                        $data['error_details']['connection_object_analysis'][] = "MySQLi connect_errno: " . $connIdValue->connect_errno;
                                        $data['error_details']['connection_object_analysis'][] = "MySQLi connect_error: " . $connIdValue->connect_error;
                                    }
                                } else {
                                    $data['error_details']['connection_object_analysis'][] = "connID 值: " . var_export($connIdValue, true);
                                }
                            } else {
                                $data['error_details']['connection_object_analysis'][] = "connID 屬性不存在";
                            }
                            
                            // 檢查資料庫對象的其他屬性
                            $dbProperties = get_object_vars($db);
                            $relevantProperties = [];
                            foreach (['DBDriver', 'hostname', 'database', 'username', 'port', 'DSN'] as $prop) {
                                if (isset($dbProperties[$prop])) {
                                    $relevantProperties[$prop] = $prop === 'password' ? '***' : $dbProperties[$prop];
                                }
                            }
                            if (!empty($relevantProperties)) {
                                $data['error_details']['db_object_properties'] = $relevantProperties;
                            }
                        } catch (\Exception $e) {
                            $data['error_details']['connection_object_analysis'][] = "分析連接對象時發生錯誤: " . $e->getMessage();
                        } catch (\Error $e) {
                            $data['error_details']['connection_object_analysis'][] = "分析連接對象時發生錯誤: " . $e->getMessage();
                        }
                        
                        // 嘗試獲取更詳細的錯誤資訊
                        try {
                            if (method_exists($db, 'error')) {
                                $dbError = $db->error();
                                if (!empty($dbError)) {
                                    $data['error_details']['database_error'] = $dbError;
                                }
                            }
                            
                            // 嘗試獲取連接錯誤（如果方法存在）
                            if (method_exists($db, 'getConnectError')) {
                                $connectError = $db->getConnectError();
                                if (!empty($connectError)) {
                                    $data['error_details']['connect_error'] = $connectError;
                                }
                            }
                        } catch (\Exception $e) {
                            $data['error_details']['error_retrieval_exception'] = $e->getMessage();
                        } catch (\Error $e) {
                            $data['error_details']['error_retrieval_error'] = $e->getMessage();
                        }
                    } else {
                        $data['error_details']['connection_object_analysis'][] = "db_connect() 返回 false，無法分析連接對象";
                    }
                    
                    // 嘗試直接使用 mysqli 連接（如果配置了）
                    if ($defaultConfig['DBDriver'] === 'MySQLi' && extension_loaded('mysqli')) {
                        try {
                            // 嘗試直接連接以獲取錯誤信息
                            $testConn = @new \mysqli(
                                $defaultConfig['hostname'] ?? 'localhost',
                                $defaultConfig['username'] ?? '',
                                $defaultConfig['password'] ?? '',
                                $defaultConfig['database'] ?? '',
                                $defaultConfig['port'] ?? 3306
                            );
                            
                            if ($testConn && $testConn->connect_errno) {
                                $data['error_details']['mysqli_error_code'] = $testConn->connect_errno;
                                $data['error_details']['mysqli_error_message'] = $testConn->connect_error;
                                
                                // 添加常見錯誤的解釋
                                $errorSuggestions = [];
                                switch ($testConn->connect_errno) {
                                    case 1045:
                                        $errorSuggestions[] = '使用者名稱或密碼錯誤';
                                        break;
                                    case 1049:
                                        $errorSuggestions[] = '資料庫不存在';
                                        break;
                                    case 2002:
                                        $errorSuggestions[] = '無法連接到資料庫伺服器，請檢查主機名稱和連接埠';
                                        break;
                                    case 2003:
                                        $errorSuggestions[] = '無法連接到資料庫伺服器，請確認 MySQL 服務是否運行';
                                        break;
                                    case 1044:
                                        $errorSuggestions[] = '使用者沒有訪問指定資料庫的權限';
                                        break;
                                }
                                
                                if (!empty($errorSuggestions)) {
                                    $data['error_details']['suggestions'] = $errorSuggestions;
                                }
                            } elseif ($testConn && $testConn->connect_errno === 0) {
                                // 連接成功但 CodeIgniter 連接失敗，可能是配置問題
                                $data['error_details']['direct_connection_success'] = true;
                                $data['error_details']['note'] = '直接連接成功，但 CodeIgniter 連接失敗，可能是配置或驅動問題';
                                
                                // 收集直接連接的詳細信息
                                $data['error_details']['direct_connection_info'] = [
                                    'hostname' => $testConn->host_info,
                                    'server_info' => $testConn->server_info,
                                    'protocol_version' => $testConn->protocol_version,
                                ];
                                
                                // 比較直接連接使用的參數和 CodeIgniter 配置
                                $directParams = [
                                    'hostname' => $defaultConfig['hostname'] ?? 'localhost',
                                    'username' => $defaultConfig['username'] ?? '',
                                    'password' => !empty($defaultConfig['password']) ? '***已設定***' : '未設定',
                                    'database' => $defaultConfig['database'] ?? '',
                                    'port' => $defaultConfig['port'] ?? 3306,
                                ];
                                
                                $data['error_details']['parameter_comparison'] = [
                                    'direct_connection_used' => $directParams,
                                    'codeigniter_config' => [
                                        'hostname' => $defaultConfig['hostname'] ?? 'N/A',
                                        'username' => $defaultConfig['username'] ?? 'N/A',
                                        'password' => !empty($defaultConfig['password']) ? '***已設定***' : '未設定',
                                        'database' => $defaultConfig['database'] ?? 'N/A',
                                        'port' => $defaultConfig['port'] ?? 'N/A',
                                        'DSN' => $defaultConfig['DSN'] ?? 'N/A',
                                    ]
                                ];
                                
                                // 檢查參數差異
                                $paramDifferences = [];
                                if (($directParams['hostname'] ?? '') !== ($defaultConfig['hostname'] ?? '')) {
                                    $paramDifferences[] = "hostname 不同：直接連接使用 '{$directParams['hostname']}'，配置為 '{$defaultConfig['hostname']}'";
                                }
                                if (($directParams['database'] ?? '') !== ($defaultConfig['database'] ?? '')) {
                                    $paramDifferences[] = "database 不同：直接連接使用 '{$directParams['database']}'，配置為 '{$defaultConfig['database']}'";
                                }
                                if (($directParams['port'] ?? 3306) != ($defaultConfig['port'] ?? 3306)) {
                                    $paramDifferences[] = "port 不同：直接連接使用 '{$directParams['port']}'，配置為 '{$defaultConfig['port']}'";
                                }
                                
                                if (!empty($paramDifferences)) {
                                    $data['error_details']['parameter_differences'] = $paramDifferences;
                                }
                                
                                // 檢查 CodeIgniter 配置與直接連接的差異
                                $configComparison = [];
                                
                                // 檢查 DSN 配置
                                if (!empty($defaultConfig['DSN'])) {
                                    $configComparison[] = 'CodeIgniter 配置中使用了 DSN，這可能與直接連接方式不同';
                                    $data['error_details']['dsn_configured'] = $defaultConfig['DSN'];
                                }
                                
                                // 檢查其他可能影響連接的配置
                                if (isset($defaultConfig['pConnect']) && $defaultConfig['pConnect']) {
                                    $configComparison[] = '持久連接 (pConnect) 已啟用，可能導致連接問題';
                                }
                                
                                if (isset($defaultConfig['compress']) && $defaultConfig['compress']) {
                                    $configComparison[] = '壓縮連接已啟用';
                                }
                                
                                if (isset($defaultConfig['encrypt']) && $defaultConfig['encrypt']) {
                                    $configComparison[] = '加密連接已啟用';
                                }
                                
                                // 檢查字符集設置
                                $actualCharset = $testConn->get_charset();
                                if ($actualCharset && isset($defaultConfig['charset'])) {
                                    if ($actualCharset->charset !== $defaultConfig['charset']) {
                                        $configComparison[] = "字符集不匹配：配置為 '{$defaultConfig['charset']}'，實際為 '{$actualCharset->charset}'";
                                    }
                                }
                                
                                if (!empty($configComparison)) {
                                    $data['error_details']['config_comparison'] = $configComparison;
                                }
                                
                                // 關閉測試連接
                                $testConn->close();
                                
                                // 分析 CodeIgniter 配置與直接連接的差異
                                $data['error_details']['codeigniter_config_analysis'] = [];
                                
                                // 檢查 DSN vs 參數連接
                                if (!empty($defaultConfig['DSN'])) {
                                    $data['error_details']['codeigniter_config_analysis'][] = '使用 DSN 連接：' . $defaultConfig['DSN'];
                                    $data['error_details']['codeigniter_config_analysis'][] = '當使用 DSN 時，CodeIgniter 會忽略 hostname、username、password 等參數';
                                } else {
                                    $data['error_details']['codeigniter_config_analysis'][] = '使用參數連接（非 DSN）';
                                    $data['error_details']['codeigniter_config_analysis'][] = 'CodeIgniter 將使用配置中的 hostname、username、password、database 等參數';
                                }
                                
                                // 檢查可能的配置問題
                                if (empty($defaultConfig['hostname']) && empty($defaultConfig['DSN'])) {
                                    $data['error_details']['codeigniter_config_analysis'][] = '⚠️ 警告：hostname 為空且未使用 DSN';
                                }
                                
                                if (empty($defaultConfig['database']) && empty($defaultConfig['DSN'])) {
                                    $data['error_details']['codeigniter_config_analysis'][] = '⚠️ 警告：database 為空且未使用 DSN';
                                }
                                
                                // 嘗試使用 CodeIgniter 的方式重新連接以獲取錯誤
                                try {
                                    // 使用錯誤抑制來避免顯示錯誤，但我們會捕獲它
                                    $lastError = error_get_last();
                                    
                                    // 嘗試使用 CodeIgniter 的連接方式（強制重新連接）
                                    $testDb = \Config\Database::connect('default', false);
                                    
                                    // 檢查連接狀態
                                    $connStatus = 'unknown';
                                    $connIdValue = null;
                                    
                                    try {
                                        if (isset($testDb->connID)) {
                                            $connIdValue = $testDb->connID;
                                            if ($connIdValue === false) {
                                                $connStatus = 'false';
                                            } elseif ($connIdValue === null) {
                                                $connStatus = 'null';
                                            } elseif (is_object($connIdValue) || is_resource($connIdValue)) {
                                                $connStatus = 'valid';
                                            } else {
                                                $connStatus = 'unexpected_type: ' . gettype($connIdValue);
                                            }
                                        } else {
                                            $connStatus = 'not_set';
                                        }
                                    } catch (\Exception $e) {
                                        $connStatus = 'exception: ' . $e->getMessage();
                                    } catch (\Error $e) {
                                        $connStatus = 'error: ' . $e->getMessage();
                                    }
                                    
                                    $data['error_details']['codeigniter_connection_status'] = $connStatus;
                                    
                                    if ($connStatus !== 'valid') {
                                        $data['error_details']['codeigniter_connection_failed'] = 'CodeIgniter 連接對象狀態：' . $connStatus;
                                        
                                        // 嘗試獲取 CodeIgniter 的錯誤
                                        try {
                                            if (method_exists($testDb, 'error')) {
                                                $ciError = $testDb->error();
                                                if (!empty($ciError)) {
                                                    $data['error_details']['codeigniter_error'] = $ciError;
                                                }
                                            }
                                        } catch (\Exception $e) {
                                            // 忽略
                                        }
                                        
                                        // 檢查是否有新的 PHP 錯誤
                                        $newError = error_get_last();
                                        if ($newError && $newError !== $lastError) {
                                            $data['error_details']['php_error_after_ci_connect'] = [
                                                'message' => $newError['message'],
                                                'file' => $newError['file'],
                                                'line' => $newError['line'],
                                            ];
                                        }
                                    }
                                } catch (\Exception $e) {
                                    $data['error_details']['codeigniter_test_error'] = [
                                        'message' => $e->getMessage(),
                                        'file' => $e->getFile(),
                                        'line' => $e->getLine(),
                                        'trace' => $e->getTraceAsString(),
                                    ];
                                } catch (\Error $e) {
                                    $data['error_details']['codeigniter_test_error'] = [
                                        'message' => $e->getMessage(),
                                        'file' => $e->getFile(),
                                        'line' => $e->getLine(),
                                        'trace' => $e->getTraceAsString(),
                                    ];
                                }
                                
                                // 提供解決建議
                                $suggestions = [
                                    '檢查 CodeIgniter 的 Database.php 配置文件',
                                    '確認 DSN 配置是否正確（如果使用 DSN）',
                                    '嘗試禁用持久連接 (pConnect: false)',
                                    '檢查字符集配置是否與資料庫匹配',
                                    '查看 CodeIgniter 日誌文件以獲取更多錯誤信息'
                                ];
                                $data['error_details']['suggestions'] = $suggestions;
                            }
                        } catch (\Exception $e) {
                            $data['error_details']['direct_connection_error'] = $e->getMessage();
                        } catch (\Error $e) {
                            $data['error_details']['direct_connection_error'] = $e->getMessage();
                        }
                    } elseif ($defaultConfig['DBDriver'] === 'MySQLi' && !extension_loaded('mysqli')) {
                        $data['error_details']['missing_extension'] = 'MySQLi 擴展未安裝或未啟用';
                    }
                }
            } catch (\CodeIgniter\Database\Exceptions\DatabaseException $e) {
                $data['success'] = false;
                $data['message'] = '資料庫連線失敗 (DatabaseException)';
                $data['error'] = $e->getMessage();
                $data['error_details'] = [
                    'exception_type' => 'DatabaseException',
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ];
            } catch (\Exception $e) {
                $data['success'] = false;
                $data['message'] = '資料庫連線失敗 (Exception)';
                $data['error'] = $e->getMessage();
                $data['error_details'] = [
                    'exception_type' => get_class($e),
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ];
            } catch (\Error $e) {
                $data['success'] = false;
                $data['message'] = '資料庫連線失敗 (Error)';
                $data['error'] = $e->getMessage();
                $data['error_details'] = [
                    'exception_type' => get_class($e),
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ];
            }
        } catch (\Exception $e) {
            $data['success'] = false;
            $data['message'] = '發生未預期的錯誤';
            $data['error'] = $e->getMessage();
            $data['error_details'] = [
                'exception_type' => get_class($e),
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ];
        } catch (\Error $e) {
            $data['success'] = false;
            $data['message'] = '發生未預期的錯誤';
            $data['error'] = $e->getMessage();
            $data['error_details'] = [
                'exception_type' => get_class($e),
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ];
        }

        return view('test_db_connection', $data);
    }
}