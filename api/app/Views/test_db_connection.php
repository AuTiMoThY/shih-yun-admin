<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>資料庫連線測試</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Microsoft JhengHei', 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 800px;
            width: 100%;
            padding: 40px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
            font-size: 28px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 15px;
        }
        
        .status {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            font-size: 18px;
            font-weight: bold;
            text-align: center;
        }
        
        .status.success {
            background-color: #d4edda;
            color: #155724;
            border: 2px solid #c3e6cb;
        }
        
        .status.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 2px solid #f5c6cb;
        }
        
        .section {
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 20px;
            color: #667eea;
            margin-bottom: 15px;
            font-weight: bold;
            border-left: 4px solid #667eea;
            padding-left: 10px;
        }
        
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .info-table th,
        .info-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .info-table th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 600;
            width: 200px;
        }
        
        .info-table td {
            color: #333;
            word-break: break-word;
        }
        
        .error-box {
            background-color: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 8px;
            padding: 15px;
            color: #856404;
            margin-top: 15px;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 30px;
            padding: 12px 24px;
            background-color: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: background-color 0.3s;
        }
        
        .back-link:hover {
            background-color: #5568d3;
        }
        
        .test-result {
            background-color: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            border-radius: 4px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔌 資料庫連線測試</h1>
        
        <div class="status <?= $success ? 'success' : 'error' ?>">
            <?php if ($success): ?>
                ✅ <?= esc($message) ?>
            <?php else: ?>
                ❌ <?= esc($message) ?>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <div class="section-title">📋 資料庫配置資訊</div>
            <table class="info-table">
                <tr>
                    <th>主機名稱</th>
                    <td><?= esc($config['hostname']) ?></td>
                </tr>
                <tr>
                    <th>連接埠</th>
                    <td><?= esc($config['port']) ?></td>
                </tr>
                <tr>
                    <th>使用者名稱</th>
                    <td><?= esc($config['username']) ?></td>
                </tr>
                <tr>
                    <th>密碼</th>
                    <td><?= esc($config['password']) ?></td>
                </tr>
                <tr>
                    <th>資料庫名稱</th>
                    <td><?= esc($config['database']) ?></td>
                </tr>
                <tr>
                    <th>資料庫驅動</th>
                    <td><?= esc($config['DBDriver']) ?></td>
                </tr>
                <tr>
                    <th>字元集</th>
                    <td><?= esc($config['charset']) ?></td>
                </tr>
                <?php if (isset($config['DSN']) && $config['DSN'] !== 'N/A' && !empty($config['DSN'])): ?>
                <tr>
                    <th>DSN</th>
                    <td><?= esc($config['DSN']) ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        
        <?php if ($success && !empty($connection_info)): ?>
        <div class="section">
            <div class="section-title">🔗 連線資訊</div>
            <table class="info-table">
                <?php if (isset($connection_info['database'])): ?>
                <tr>
                    <th>當前資料庫</th>
                    <td><?= esc($connection_info['database']) ?></td>
                </tr>
                <?php endif; ?>
                <?php if (isset($connection_info['version'])): ?>
                <tr>
                    <th>資料庫版本</th>
                    <td><?= esc($connection_info['version']) ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        <?php endif; ?>
        
        <?php if ($success && $test_query): ?>
        <div class="section">
            <div class="section-title">🧪 測試查詢結果</div>
            <div class="test-result">
                <?php if (isset($test_query['error'])): ?>
                    <strong>錯誤：</strong> <?= esc($test_query['error']) ?>
                <?php else: ?>
                    <table class="info-table">
                        <?php if (isset($test_query['test_value'])): ?>
                        <tr>
                            <th>測試值</th>
                            <td><?= esc($test_query['test_value']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (isset($test_query['current_time'])): ?>
                        <tr>
                            <th>伺服器時間</th>
                            <td><?= esc($test_query['current_time']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (isset($test_query['current_database'])): ?>
                        <tr>
                            <th>當前資料庫</th>
                            <td><?= esc($test_query['current_database']) ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="section">
            <div class="section-title">⚠️ 錯誤詳情</div>
            <div class="error-box">
                <strong>錯誤訊息：</strong><br>
                <?= esc($error) ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($error_details)): ?>
        <div class="section">
            <div class="section-title">🔍 詳細錯誤資訊</div>
            <div class="error-box">
                <?php if (isset($error_details['exception_type'])): ?>
                    <p><strong>異常類型：</strong> <?= esc($error_details['exception_type']) ?></p>
                <?php endif; ?>
                
                <?php if (isset($error_details['code'])): ?>
                    <p><strong>錯誤代碼：</strong> <?= esc($error_details['code']) ?></p>
                <?php endif; ?>
                
                <?php if (isset($error_details['file'])): ?>
                    <p><strong>檔案：</strong> <?= esc($error_details['file']) ?></p>
                <?php endif; ?>
                
                <?php if (isset($error_details['line'])): ?>
                    <p><strong>行號：</strong> <?= esc($error_details['line']) ?></p>
                <?php endif; ?>
                
                <?php if (isset($error_details['database_error'])): ?>
                    <p><strong>資料庫錯誤：</strong></p>
                    <pre style="background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto;"><?= esc(print_r($error_details['database_error'], true)) ?></pre>
                <?php endif; ?>
                
                <?php if (isset($error_details['mysqli_error_code'])): ?>
                    <p><strong>MySQLi 錯誤代碼：</strong> <?= esc($error_details['mysqli_error_code']) ?></p>
                <?php endif; ?>
                
                <?php if (isset($error_details['mysqli_error_message'])): ?>
                    <p><strong>MySQLi 錯誤訊息：</strong> <?= esc($error_details['mysqli_error_message']) ?></p>
                <?php endif; ?>
                
                <?php if (isset($error_details['php_error'])): ?>
                    <p><strong>PHP 錯誤：</strong></p>
                    <ul>
                        <?php if (isset($error_details['php_error']['message'])): ?>
                            <li><strong>訊息：</strong> <?= esc($error_details['php_error']['message']) ?></li>
                        <?php endif; ?>
                        <?php if (isset($error_details['php_error']['file'])): ?>
                            <li><strong>檔案：</strong> <?= esc($error_details['php_error']['file']) ?></li>
                        <?php endif; ?>
                        <?php if (isset($error_details['php_error']['line'])): ?>
                            <li><strong>行號：</strong> <?= esc($error_details['php_error']['line']) ?></li>
                        <?php endif; ?>
                        <?php if (isset($error_details['php_error']['code'])): ?>
                            <li><strong>錯誤代碼：</strong> <?= esc($error_details['php_error']['code']) ?></li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
                
                <?php if (isset($error_details['direct_connection_error'])): ?>
                    <p><strong>直接連接測試錯誤：</strong> <?= esc($error_details['direct_connection_error']) ?></p>
                <?php endif; ?>
                
                <?php if (isset($error_details['missing_extension'])): ?>
                    <p><strong>缺少擴展：</strong> <?= esc($error_details['missing_extension']) ?></p>
                <?php endif; ?>
                
                <?php if (isset($error_details['direct_connection_success'])): ?>
                    <div style="background: #e7f3ff; padding: 15px; border-left: 4px solid #2196F3; margin-top: 10px; border-radius: 4px;">
                        <p style="margin-bottom: 10px;"><strong>✅ 直接連接測試成功</strong></p>
                        
                        <?php if (isset($error_details['direct_connection_info'])): ?>
                            <p style="margin-top: 10px;"><strong>直接連接資訊：</strong></p>
                            <ul style="margin-left: 20px; margin-top: 5px;">
                                <?php if (isset($error_details['direct_connection_info']['hostname'])): ?>
                                    <li><strong>主機資訊：</strong> <?= esc($error_details['direct_connection_info']['hostname']) ?></li>
                                <?php endif; ?>
                                <?php if (isset($error_details['direct_connection_info']['server_info'])): ?>
                                    <li><strong>伺服器資訊：</strong> <?= esc($error_details['direct_connection_info']['server_info']) ?></li>
                                <?php endif; ?>
                                <?php if (isset($error_details['direct_connection_info']['protocol_version'])): ?>
                                    <li><strong>協議版本：</strong> <?= esc($error_details['direct_connection_info']['protocol_version']) ?></li>
                                <?php endif; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_details['dsn_configured'])): ?>
                    <p style="background: #fff3cd; padding: 10px; border-left: 4px solid #ffc107; margin-top: 10px;">
                        <strong>⚠️ DSN 配置：</strong> <?= esc($error_details['dsn_configured']) ?><br>
                        <small>CodeIgniter 使用 DSN 連接時，可能會忽略其他配置參數</small>
                    </p>
                <?php endif; ?>
                
                <?php if (isset($error_details['config_comparison'])): ?>
                    <p style="background: #fff3cd; padding: 10px; border-left: 4px solid #ffc107; margin-top: 10px;">
                        <strong>配置差異：</strong>
                        <ul style="margin-top: 5px; margin-left: 20px;">
                            <?php foreach ($error_details['config_comparison'] as $comparison): ?>
                                <li><?= esc($comparison) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </p>
                <?php endif; ?>
                
                <?php if (isset($error_details['codeigniter_connection_failed'])): ?>
                    <p style="background: #f8d7da; padding: 10px; border-left: 4px solid #dc3545; margin-top: 10px; color: #721c24;">
                        <strong>❌ CodeIgniter 連接狀態：</strong> <?= esc($error_details['codeigniter_connection_failed']) ?>
                    </p>
                <?php endif; ?>
                
                <?php if (isset($error_details['codeigniter_error'])): ?>
                    <p style="background: #f8d7da; padding: 10px; border-left: 4px solid #dc3545; margin-top: 10px; color: #721c24;">
                        <strong>CodeIgniter 錯誤：</strong>
                        <pre style="background: rgba(255,255,255,0.5); padding: 8px; margin-top: 5px; border-radius: 4px; overflow-x: auto;"><?= esc(print_r($error_details['codeigniter_error'], true)) ?></pre>
                    </p>
                <?php endif; ?>
                
                <?php if (isset($error_details['codeigniter_config_analysis'])): ?>
                    <div style="background: #e7f3ff; padding: 15px; border-left: 4px solid #2196F3; margin-top: 10px; border-radius: 4px;">
                        <p style="margin-bottom: 10px;"><strong>🔍 CodeIgniter 配置分析：</strong></p>
                        <ul style="margin-left: 20px; margin-top: 5px;">
                            <?php foreach ($error_details['codeigniter_config_analysis'] as $analysis): ?>
                                <li><?= esc($analysis) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_details['connection_attempt'])): ?>
                    <div style="background: #e7f3ff; padding: 15px; border-left: 4px solid #2196F3; margin-top: 10px; border-radius: 4px;">
                        <p style="margin-bottom: 10px;"><strong>🔧 CodeIgniter 連接嘗試使用的參數：</strong></p>
                        <table class="info-table" style="margin-top: 10px;">
                            <?php foreach ($error_details['connection_attempt'] as $key => $value): ?>
                                <tr>
                                    <th><?= esc(ucfirst($key)) ?></th>
                                    <td><?= esc($value) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_details['connection_object_analysis'])): ?>
                    <div style="background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin-top: 10px; border-radius: 4px; color: #721c24;">
                        <p style="margin-bottom: 10px;"><strong>🔍 連接對象分析：</strong></p>
                        <ul style="margin-left: 20px; margin-top: 5px;">
                            <?php foreach ($error_details['connection_object_analysis'] as $analysis): ?>
                                <li><?= esc($analysis) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_details['db_object_properties'])): ?>
                    <div style="background: #e7f3ff; padding: 15px; border-left: 4px solid #2196F3; margin-top: 10px; border-radius: 4px;">
                        <p style="margin-bottom: 10px;"><strong>📋 資料庫對象屬性：</strong></p>
                        <table class="info-table" style="margin-top: 10px;">
                            <?php foreach ($error_details['db_object_properties'] as $key => $value): ?>
                                <tr>
                                    <th><?= esc(ucfirst($key)) ?></th>
                                    <td><?= esc($value) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_details['parameter_comparison'])): ?>
                    <div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin-top: 10px; border-radius: 4px;">
                        <p style="margin-bottom: 10px;"><strong>⚖️ 參數比較：</strong></p>
                        <p><strong>直接連接使用的參數：</strong></p>
                        <table class="info-table" style="margin-top: 5px;">
                            <?php foreach ($error_details['parameter_comparison']['direct_connection_used'] as $key => $value): ?>
                                <tr>
                                    <th><?= esc(ucfirst($key)) ?></th>
                                    <td><?= esc($value) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                        <p style="margin-top: 15px;"><strong>CodeIgniter 配置的參數：</strong></p>
                        <table class="info-table" style="margin-top: 5px;">
                            <?php foreach ($error_details['parameter_comparison']['codeigniter_config'] as $key => $value): ?>
                                <tr>
                                    <th><?= esc(ucfirst($key)) ?></th>
                                    <td><?= esc($value) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_details['parameter_differences'])): ?>
                    <div style="background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin-top: 10px; border-radius: 4px; color: #721c24;">
                        <p style="margin-bottom: 10px;"><strong>⚠️ 參數差異：</strong></p>
                        <ul style="margin-left: 20px; margin-top: 5px;">
                            <?php foreach ($error_details['parameter_differences'] as $diff): ?>
                                <li><?= esc($diff) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_details['connect_error'])): ?>
                    <p style="background: #f8d7da; padding: 10px; border-left: 4px solid #dc3545; margin-top: 10px; color: #721c24;">
                        <strong>連接錯誤：</strong> <?= esc($error_details['connect_error']) ?>
                    </p>
                <?php endif; ?>
                
                <?php if (isset($error_details['error_retrieval_exception']) || isset($error_details['error_retrieval_error'])): ?>
                    <p style="background: #f8d7da; padding: 10px; border-left: 4px solid #dc3545; margin-top: 10px; color: #721c24;">
                        <strong>錯誤檢索問題：</strong>
                        <?php if (isset($error_details['error_retrieval_exception'])): ?>
                            <?= esc($error_details['error_retrieval_exception']) ?>
                        <?php endif; ?>
                        <?php if (isset($error_details['error_retrieval_error'])): ?>
                            <?= esc($error_details['error_retrieval_error']) ?>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
                
                <?php if (isset($error_details['all_php_errors'])): ?>
                    <details style="margin-top: 15px;">
                        <summary style="cursor: pointer; font-weight: bold; padding: 10px; background: #f8f9fa; border-radius: 4px;">所有 PHP 錯誤訊息 (點擊展開)</summary>
                        <div style="background: #f5f5f5; padding: 15px; border-radius: 4px; margin-top: 10px;">
                            <?php foreach ($error_details['all_php_errors'] as $index => $err): ?>
                                <div style="margin-bottom: 10px; padding: 10px; background: white; border-left: 3px solid #dc3545; border-radius: 4px;">
                                    <p><strong>錯誤 #<?= $index + 1 ?>:</strong></p>
                                    <ul style="margin-left: 20px; margin-top: 5px;">
                                        <li><strong>訊息：</strong> <?= esc($err['message']) ?></li>
                                        <li><strong>檔案：</strong> <?= esc($err['file']) ?></li>
                                        <li><strong>行號：</strong> <?= esc($err['line']) ?></li>
                                        <li><strong>錯誤級別：</strong> <?= esc($err['level']) ?></li>
                                    </ul>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </details>
                <?php endif; ?>
                
                <?php if (isset($error_details['codeigniter_connection_status'])): ?>
                    <p style="background: #f8d7da; padding: 10px; border-left: 4px solid #dc3545; margin-top: 10px; color: #721c24;">
                        <strong>CodeIgniter 連接狀態：</strong> <?= esc($error_details['codeigniter_connection_status']) ?>
                    </p>
                <?php endif; ?>
                
                <?php if (isset($error_details['php_error_after_ci_connect'])): ?>
                    <p style="background: #f8d7da; padding: 10px; border-left: 4px solid #dc3545; margin-top: 10px; color: #721c24;">
                        <strong>CodeIgniter 連接後的 PHP 錯誤：</strong>
                        <ul style="margin-left: 20px; margin-top: 5px;">
                            <li><strong>訊息：</strong> <?= esc($error_details['php_error_after_ci_connect']['message']) ?></li>
                            <li><strong>檔案：</strong> <?= esc($error_details['php_error_after_ci_connect']['file']) ?></li>
                            <li><strong>行號：</strong> <?= esc($error_details['php_error_after_ci_connect']['line']) ?></li>
                        </ul>
                    </p>
                <?php endif; ?>
                
                <?php if (isset($error_details['codeigniter_test_error'])): ?>
                    <div style="background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin-top: 10px; border-radius: 4px; color: #721c24;">
                        <p style="margin-bottom: 10px;"><strong>❌ CodeIgniter 測試錯誤：</strong></p>
                        <?php if (is_array($error_details['codeigniter_test_error'])): ?>
                            <ul style="margin-left: 20px; margin-top: 5px;">
                                <li><strong>訊息：</strong> <?= esc($error_details['codeigniter_test_error']['message']) ?></li>
                                <li><strong>檔案：</strong> <?= esc($error_details['codeigniter_test_error']['file']) ?></li>
                                <li><strong>行號：</strong> <?= esc($error_details['codeigniter_test_error']['line']) ?></li>
                            </ul>
                            <?php if (isset($error_details['codeigniter_test_error']['trace'])): ?>
                                <details style="margin-top: 10px;">
                                    <summary style="cursor: pointer; font-weight: bold;">堆疊追蹤 (點擊展開)</summary>
                                    <pre style="background: rgba(255,255,255,0.5); padding: 10px; margin-top: 5px; border-radius: 4px; overflow-x: auto; font-size: 11px; max-height: 300px; overflow-y: auto;"><?= esc($error_details['codeigniter_test_error']['trace']) ?></pre>
                                </details>
                            <?php endif; ?>
                        <?php else: ?>
                            <p><?= esc($error_details['codeigniter_test_error']) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_details['note'])): ?>
                    <p style="background: #e7f3ff; padding: 10px; border-left: 4px solid #2196F3; margin-top: 10px;">
                        <strong>提示：</strong> <?= esc($error_details['note']) ?>
                    </p>
                <?php endif; ?>
                
                <?php if (isset($error_details['suggestions'])): ?>
                    <p style="background: #fff3cd; padding: 10px; border-left: 4px solid #ffc107; margin-top: 10px;">
                        <strong>可能的解決方案：</strong>
                        <ul style="margin-top: 5px; margin-left: 20px;">
                            <?php foreach ($error_details['suggestions'] as $suggestion): ?>
                                <li><?= esc($suggestion) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </p>
                <?php endif; ?>
                
                <?php if (isset($error_details['config_errors'])): ?>
                    <p><strong>配置錯誤：</strong></p>
                    <ul>
                        <?php foreach ($error_details['config_errors'] as $configError): ?>
                            <li><?= esc($configError) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                
                <?php if (isset($error_details['trace'])): ?>
                    <details style="margin-top: 15px;">
                        <summary style="cursor: pointer; font-weight: bold; margin-bottom: 10px;">堆疊追蹤 (點擊展開)</summary>
                        <pre style="background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 12px; max-height: 400px; overflow-y: auto;"><?= esc($error_details['trace']) ?></pre>
                    </details>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($debug_info)): ?>
        <div class="section">
            <div class="section-title">🐛 Debug 資訊</div>
            <table class="info-table">
                <?php if (isset($debug_info['php_version'])): ?>
                <tr>
                    <th>PHP 版本</th>
                    <td><?= esc($debug_info['php_version']) ?></td>
                </tr>
                <?php endif; ?>
                
                <?php if (isset($debug_info['codeigniter_version'])): ?>
                <tr>
                    <th>CodeIgniter 版本</th>
                    <td><?= esc($debug_info['codeigniter_version']) ?></td>
                </tr>
                <?php endif; ?>
                
                <?php if (isset($debug_info['environment'])): ?>
                <tr>
                    <th>環境</th>
                    <td><?= esc($debug_info['environment']) ?></td>
                </tr>
                <?php endif; ?>
                
                <?php if (isset($debug_info['extensions'])): ?>
                <tr>
                    <th>PHP 擴展</th>
                    <td>
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            <?php foreach ($debug_info['extensions'] as $ext => $loaded): ?>
                                <li>
                                    <?php if ($loaded): ?>
                                        ✅ <?= esc($ext) ?> (已載入)
                                    <?php else: ?>
                                        ❌ <?= esc($ext) ?> (未載入)
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        <?php endif; ?>
        
        <a href="/" class="back-link">← 返回首頁</a>
    </div>
</body>
</html>