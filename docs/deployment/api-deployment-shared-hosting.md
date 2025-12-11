# API 部署指南 - 虛擬主機版本

## 概述

此指南適用於**無法執行指令的虛擬主機**（如 cPanel、Plesk 等）。所有準備工作需要在**本地電腦**完成，然後透過 FTP 或檔案管理器上傳。

## 前置需求

### 本地電腦需要安裝：
- PHP 8.1 或更高版本
- Composer
- 文字編輯器

### 伺服器需求：
- PHP 8.1 或更高版本
- Apache 網頁伺服器（支援 `.htaccess`）
- MySQL/MariaDB 資料庫
- FTP 或 cPanel 檔案管理器存取權限

## 部署步驟

### 步驟 1：在本地準備檔案

#### 1.1 安裝依賴（在本地執行）

在本地電腦的 `api` 資料夾中執行：

```bash
cd api
composer install --no-dev --optimize-autoloader
```

這會下載所有必要的 PHP 套件到 `vendor` 資料夾。

#### 1.2 準備 .env 檔案

1. 複製 `env` 檔案為 `.env`：
   ```bash
   # Windows
   copy env .env
   
   # Mac/Linux
   cp env .env
   ```

2. 編輯 `.env` 檔案，設定以下項目：

```ini
# 環境設定
CI_ENVIRONMENT = production

# 應用程式 URL（根據您的部署路徑調整）
app.baseURL = 'https://test-sys.srl.tw/api/'

# 資料庫設定（從虛擬主機控制台取得）
database.default.hostname = localhost
database.default.database = [您的資料庫名稱]
database.default.username = [您的資料庫使用者名稱]
database.default.password = [您的資料庫密碼]
database.default.DBDriver = MySQLi
database.default.port = 3306

# 強制 HTTPS
app.forceGlobalSecureRequests = true

# 加密金鑰（下一步會生成）
encryption.key = 
```

#### 1.3 生成加密金鑰（在本地執行）

在本地電腦執行：

```bash
cd api
php spark key:generate
```

這會自動在 `.env` 檔案中生成 `encryption.key`。**請確認 `.env` 檔案中有這個金鑰值。**

如果無法執行 `php spark`，可以手動生成：

1. 訪問：https://randomkeygen.com/
2. 選擇 "CodeIgniter Encryption Keys"
3. 複製生成的 32 字元金鑰
4. 在 `.env` 檔案中設定：
   ```ini
   encryption.key = [複製的金鑰]
   ```

#### 1.4 確認 CORS 設定

確認 `api/app/Config/Cors.php` 包含生產環境網址：

```php
'allowedOrigins' => [
    'http://localhost:3000',
    'http://127.0.0.1:3000',
    'https://test-sys.srl.tw',
],
```

### 步驟 2：準備上傳的檔案

#### 2.1 需要上傳的檔案和資料夾

上傳以下內容到伺服器：

```
api/
├── app/              # 必須上傳
├── public/           # 必須上傳（包含 .htaccess）
├── vendor/           # 必須上傳（composer 安裝的套件）
├── writable/         # 必須上傳
├── .env              # 必須上傳（已設定好的環境變數）
├── composer.json     # 必須上傳
├── composer.lock     # 必須上傳
├── spark             # 必須上傳
└── 其他檔案...
```

#### 2.2 不需要上傳的檔案

- `tests/` 資料夾（測試檔案）
- `.git/` 資料夾（如果有的話）
- `node_modules/`（如果有的話）

### 步驟 3：上傳到虛擬主機

#### 方式 A：使用 FTP 客戶端（推薦）

1. 使用 FileZilla、WinSCP 或其他 FTP 客戶端
2. 連接到您的虛擬主機
3. 導航到網站根目錄（通常是 `public_html` 或 `www`）
4. 上傳整個 `api` 資料夾

**建議的伺服器目錄結構：**

```
public_html/          (或 www/)
├── admin/            # Nuxt 前端生成的檔案
└── api/              # CodeIgniter 4 API
    ├── app/
    ├── public/
    ├── vendor/
    ├── writable/
    ├── .env
    └── ...
```

#### 方式 B：使用 cPanel 檔案管理器

1. 登入 cPanel
2. 開啟「檔案管理器」
3. 導航到 `public_html` 資料夾
4. 上傳 `api` 資料夾（可能需要先壓縮為 ZIP，然後在 cPanel 中解壓縮）

### 步驟 4：設定網站根目錄

在虛擬主機中，有幾種方式可以讓 `/api` 子目錄指向 `public` 資料夾：

#### 方法 A：使用 .htaccess 重定向（推薦，最簡單）

在 `public_html/api/` 目錄中建立一個 `.htaccess` 檔案（與 `public` 資料夾同層級），內容如下：

```apache
# 將 /api 子目錄的所有請求重定向到 public 資料夾
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # 如果請求的檔案或資料夾不存在，重定向到 public 資料夾
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ public/$1 [L]
    
    # 如果直接訪問 /api/，重定向到 /api/public/
    RewriteCond %{REQUEST_URI} ^/api/?$
    RewriteRule ^$ public/ [L]
</IfModule>
```

**目錄結構：**
```
public_html/
└── api/
    ├── .htaccess      ← 新建這個檔案（與 public 同層級）
    ├── app/
    ├── public/        ← 實際的網站根目錄
    │   ├── .htaccess
    │   └── index.php
    ├── vendor/
    └── ...
```

然後修改 `api/public/.htaccess`，取消註解：
```apache
RewriteBase /api/
```

#### 方法 B：使用 cPanel 子域名（如果支援）

如果您的虛擬主機支援設定子域名：

1. 在 cPanel 中建立子域名 `api.test-sys.srl.tw`（或使用現有域名）
2. 將 DocumentRoot 指向 `public_html/api/public`
3. 這樣可以直接訪問 `https://api.test-sys.srl.tw/`，而不需要 `/api/` 路徑

#### 方法 C：符號連結（如果虛擬主機支援）

如果您的虛擬主機支援符號連結（Symbolic Links），可以：

1. 在 `public_html/api/` 中建立符號連結指向 `public` 資料夾
2. 但這通常需要 SSH 權限，虛擬主機可能不支援

#### 確認設定

無論使用哪種方法，都需要確認：

1. **`.env` 中的 baseURL**：
   ```ini
   app.baseURL = 'https://test-sys.srl.tw/api/'
   ```

2. **`api/public/.htaccess` 中的 RewriteBase**：
   ```apache
   RewriteBase /api/
   ```

### 步驟 5：設定檔案權限

#### 使用 cPanel 檔案管理器：

1. 登入 cPanel
2. 開啟「檔案管理器」
3. 導航到 `api/writable` 資料夾
4. 右鍵點擊 `writable` 資料夾
5. 選擇「變更權限」（Change Permissions）
6. 設定為 `755` 或 `775`（如果 775 不行，試試 755）
7. 對 `writable` 下的子資料夾也設定相同權限：
   - `writable/cache/` → 755
   - `writable/logs/` → 755
   - `writable/session/` → 755
   - `writable/uploads/` → 755

#### 使用 FTP 客戶端：

1. 連接到 FTP
2. 導航到 `api/writable` 資料夾
3. 右鍵點擊資料夾 → 屬性/權限
4. 設定為 `755` 或 `775`

### 步驟 6：設定資料庫

1. 在 cPanel 中建立資料庫和使用者（如果還沒建立）
2. 記下資料庫名稱、使用者名稱和密碼
3. 更新 `.env` 檔案中的資料庫設定（透過 FTP 或 cPanel 檔案管理器編輯）

### 步驟 7：測試部署

訪問以下 URL 測試：

- `https://test-sys.srl.tw/api/` - 應該顯示 CodeIgniter 歡迎頁面或 API 回應
- `https://test-sys.srl.tw/api/test-cors` - 測試 CORS 設定
- `https://test-sys.srl.tw/structure/get` - 測試結構 API

## 常見問題排除

### 問題 1：500 Internal Server Error

**可能原因：**
- `.env` 檔案設定錯誤
- 檔案權限不正確
- PHP 版本不符合要求

**解決方法：**
1. 檢查 cPanel 錯誤日誌
2. 確認 PHP 版本（cPanel → Select PHP Version）
3. 檢查 `.env` 檔案是否存在且設定正確
4. 確認 `writable` 資料夾權限為 755

### 問題 2：403 Forbidden

**可能原因：**
- `.htaccess` 檔案問題
- 檔案權限問題

**解決方法：**
1. 確認 `public/.htaccess` 檔案存在
2. 檢查 `.htaccess` 檔案權限（應該是 644）
3. 確認 Apache `mod_rewrite` 已啟用（通常虛擬主機預設已啟用）

### 問題 3：找不到 vendor 資料夾

**解決方法：**
- 確認已上傳 `vendor` 資料夾
- 如果忘記上傳，在本地執行 `composer install --no-dev`，然後重新上傳 `vendor` 資料夾

### 問題 4：資料庫連線失敗

**解決方法：**
1. 檢查 `.env` 中的資料庫設定
2. 確認資料庫主機名稱（虛擬主機通常是 `localhost`）
3. 確認資料庫使用者有正確的權限

### 問題 5：加密金鑰錯誤

**解決方法：**
1. 在本地重新生成金鑰：`php spark key:generate`
2. 複製 `.env` 中的 `encryption.key` 值
3. 透過 FTP 或 cPanel 更新伺服器上的 `.env` 檔案

## 更新部署

當需要更新 API 時：

1. **在本地更新程式碼**
2. **重新安裝依賴**（如果有新的套件）：
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
3. **上傳更新的檔案**（保留伺服器上的 `.env` 和 `writable` 資料夾）
4. **清除快取**：透過 FTP 或 cPanel 刪除 `writable/cache/` 中的所有檔案

## 安全建議

1. **保護 `.env` 檔案**：
   - 確認 `.htaccess` 有保護 `.env` 檔案（CodeIgniter 4 預設已包含）
   - 不要將 `.env` 檔案提交到版本控制系統

2. **定期備份**：
   - 備份資料庫
   - 備份 `writable` 資料夾（包含上傳的檔案）

3. **檢查檔案權限**：
   - `.env` 應該是 644
   - `writable` 資料夾應該是 755

## 檢查清單

部署前確認：
- [ ] 在本地執行 `composer install --no-dev`
- [ ] `.env` 檔案已設定且包含加密金鑰
- [ ] CORS 設定包含 `https://test-sys.srl.tw`
- [ ] 所有檔案已上傳到伺服器
- [ ] `writable` 資料夾權限設為 755
- [ ] `.env` 中的資料庫設定正確
- [ ] `app.baseURL` 設定正確
- [ ] 測試 API 端點可以正常訪問

