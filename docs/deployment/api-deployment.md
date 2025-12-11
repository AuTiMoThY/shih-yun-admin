# API 部署指南

## 概述

此 API 是基於 CodeIgniter 4 的 PHP 應用程式，需要部署到 `https://test-sys.srl.tw/` 伺服器。

## 部署步驟

### 1. 準備伺服器環境

確保伺服器滿足以下要求：
- PHP 8.1 或更高版本
- Apache 或 Nginx 網頁伺服器
- MySQL/MariaDB 資料庫
- Composer（用於安裝依賴）

### 2. 上傳檔案到伺服器

將 `api` 資料夾的所有內容上傳到伺服器。建議的目錄結構：

```
/var/www/html/  (或您的網站根目錄)
├── api/
│   ├── app/
│   ├── public/        # 這個資料夾應該設為網站根目錄
│   ├── writable/
│   ├── vendor/
│   ├── composer.json
│   └── ...
└── admin/             # Nuxt 前端生成的檔案
```

### 3. 設定網站根目錄

**重要：** CodeIgniter 4 的安全性要求將網站根目錄指向 `public` 資料夾。

#### Apache 設定方式

如果使用 Apache，有兩種方式：

**方式 A：將 `public` 設為網站根目錄（推薦）**

在 Apache 虛擬主機設定中：

```apache
<VirtualHost *:80>
    ServerName test-sys.srl.tw
    DocumentRoot /var/www/html/api/public
    
    <Directory /var/www/html/api/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**方式 B：使用子目錄（如果無法修改虛擬主機）**

如果 API 需要部署在 `/api` 路徑下，需要修改 `public/.htaccess`：

```apache
RewriteBase /api/
```

並在 `app/Config/App.php` 或 `.env` 中設定：

```php
public string $baseURL = 'https://test-sys.srl.tw/api/';
```

### 4. 設定環境變數

1. 在伺服器上複製 `env` 檔案為 `.env`：
   ```bash
   cd /var/www/html/api
   cp env .env
   ```

2. 編輯 `.env` 檔案，設定以下重要參數：

```ini
# 環境設定
CI_ENVIRONMENT = production

# 應用程式 URL
app.baseURL = 'https://test-sys.srl.tw/api/'
# 或如果 public 是根目錄：
# app.baseURL = 'https://test-sys.srl.tw/'

# 資料庫設定
database.default.hostname = localhost
database.default.database = your_database_name
database.default.username = your_db_username
database.default.password = your_db_password
database.default.DBDriver = MySQLi
database.default.port = 3306

# 加密金鑰（重要！）
# 執行以下命令生成新的加密金鑰：
# php spark key:generate
encryption.key = 

# 強制 HTTPS（生產環境建議啟用）
app.forceGlobalSecureRequests = true
```

### 5. 安裝依賴

在伺服器上執行：

```bash
cd /var/www/html/api
composer install --no-dev --optimize-autoloader
```

### 6. 設定檔案權限

確保 `writable` 資料夾有寫入權限：

```bash
chmod -R 775 writable/
chown -R www-data:www-data writable/  # 根據您的網頁伺服器使用者調整
```

### 7. 生成加密金鑰

執行以下命令生成新的加密金鑰：

```bash
php spark key:generate
```

這會自動更新 `.env` 檔案中的 `encryption.key`。

### 8. 資料庫遷移（如果需要）

如果有資料庫遷移檔案，執行：

```bash
php spark migrate
```

### 9. 更新 App.php 設定（可選）

如果需要，可以更新 `app/Config/App.php` 中的 `baseURL`：

```php
public string $baseURL = 'https://test-sys.srl.tw/api/';
```

但建議使用 `.env` 檔案來設定，這樣更靈活。

### 10. 更新 CORS 設定

確保 `app/Config/Cors.php` 中的 `allowedOrigins` 包含生產環境網址：

```php
'allowedOrigins' => [
    'http://localhost:3000',
    'http://127.0.0.1:3000',
    'https://test-sys.srl.tw',
],
```

這樣前端（部署在 `https://test-sys.srl.tw/admin/`）才能正常呼叫 API。

### 11. 測試部署

訪問以下 URL 測試 API 是否正常運作：

- `https://test-sys.srl.tw/api/` （如果部署在子目錄）
- 或 `https://test-sys.srl.tw/` （如果 public 是根目錄）

測試 API 端點：
- `https://test-sys.srl.tw/api/test-cors` - 測試 CORS 設定
- `https://test-sys.srl.tw/structure/get` - 測試結構 API

## 部署架構建議

根據您的需求，建議的部署架構：

```
https://test-sys.srl.tw/
├── /admin/          # Nuxt 前端（靜態檔案）
└── /api/            # CodeIgniter 4 API（或根目錄）
```

## 安全檢查清單

- [ ] `.env` 檔案已設定且包含正確的資料庫憑證
- [ ] `writable` 資料夾權限正確（775）
- [ ] 加密金鑰已生成
- [ ] `CI_ENVIRONMENT` 設為 `production`
- [ ] `app.forceGlobalSecureRequests` 設為 `true`（啟用 HTTPS）
- [ ] `app.baseURL` 設定正確（包含 `/api/` 路徑或根目錄）
- [ ] CORS 設定已更新，包含 `https://test-sys.srl.tw`
- [ ] 資料庫連線正常
- [ ] 所有依賴已安裝（`composer install --no-dev`）

## 常見問題

### 問題 1：403 Forbidden 錯誤

**解決方案：** 檢查檔案權限和 `.htaccess` 設定。

### 問題 2：資料庫連線失敗

**解決方案：** 檢查 `.env` 檔案中的資料庫設定是否正確。

### 問題 3：路由無法正常工作

**解決方案：** 
- 確認 Apache `mod_rewrite` 已啟用
- 檢查 `.htaccess` 檔案是否存在
- 確認 `baseURL` 設定正確

### 問題 4：CORS 錯誤

**解決方案：** 檢查 `app/Config/Cors.php` 設定，確保允許來自前端的請求。

## 更新部署

當需要更新 API 時：

1. 上傳新的檔案（保留 `.env` 和 `writable` 資料夾）
2. 執行 `composer install --no-dev --optimize-autoloader`
3. 如有資料庫變更，執行 `php spark migrate`
4. 清除快取：刪除 `writable/cache/*` 中的檔案

## 相關檔案

- `api/public/.htaccess` - Apache 重寫規則
- `api/app/Config/App.php` - 應用程式設定
- `api/env` - 環境變數範本
- `api/composer.json` - PHP 依賴管理

