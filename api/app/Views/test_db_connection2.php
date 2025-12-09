<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>資料庫連線測試 2</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        .status {
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        pre {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            border: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>資料庫連線測試 2</h1>
        
        <?php if ($connection_success): ?>
            <div class="status success">
                <strong>✓ 資料庫連線成功</strong>
            </div>
            
            <?php if ($connection_info): ?>
                <div class="status info">
                    <strong>連線資訊：</strong><br>
                    資料庫: <?= esc($connection_info['database']) ?><br>
                    平台: <?= esc($connection_info['platform']) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($query_success): ?>
                <div class="status success">
                    <strong>✓ 查詢執行成功</strong>
                </div>
                <div class="status info">
                    <strong>查詢結果：</strong>
                    <pre><?= print_r($result, true) ?></pre>
                </div>
            <?php else: ?>
                <div class="status error">
                    <strong>✗ 查詢執行失敗</strong>
                    <?php if ($error): ?>
                        <br>錯誤訊息: <?= esc($error) ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="status error">
                <strong>✗ 資料庫連線失敗</strong>
                <?php if ($error): ?>
                    <br>錯誤訊息: <?= esc($error) ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>