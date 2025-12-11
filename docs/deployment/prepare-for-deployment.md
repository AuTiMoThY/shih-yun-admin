# 本地準備部署檔案指南

## 適用於虛擬主機部署

此指南幫助您在本地準備好所有檔案，然後上傳到虛擬主機。

## 步驟 1：安裝依賴

在本地電腦的 `api` 資料夾中執行：

```bash
composer install --no-dev --optimize-autoloader
```

這會下載所有必要的 PHP 套件到 `vendor` 資料夾。

## 步驟 2：準備 .env 檔案

### 2.1 複製 env 檔案

**Windows:**
```cmd
copy env .env
```

**Mac/Linux:**
```bash
cp env .env
```

### 2.2 編輯 .env 檔案

使用文字編輯器開啟 `.env` 檔案，設定以下項目：

```ini
# ============================================
# 環境設定
# ============================================
CI_ENVIRONMENT = production

# ============================================
# 應用程式 URL
# ============================================
app.baseURL = 'https://test-sys.srl.tw/api/'

# ============================================
# 資料庫設定（從虛擬主機控制台取得）
# ============================================
database.default.hostname = localhost
database.default.database = [填入您的資料庫名稱]
database.default.username = [填入您的資料庫使用者名稱]
database.default.password = [填入您的資料庫密碼]
database.default.DBDriver = MySQLi
database.default.port = 3306

# ============================================
# 加密設定
# ============================================
# 執行步驟 3 生成金鑰後，這裡會自動填入
encryption.key = 

# ============================================
# 安全設定
# ============================================
app.forceGlobalSecureRequests = true
```

## 步驟 3：生成加密金鑰

### 方法 A：使用 spark 命令（推薦）

在本地執行：

```bash
php spark key:generate
```

這會自動在 `.env` 檔案中生成 `encryption.key`。

### 方法 B：手動生成

如果無法執行 `php spark`：

1. 訪問：https://randomkeygen.com/
2. 選擇 "CodeIgniter Encryption Keys"
3. 複製生成的 32 字元金鑰（格式類似：`a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6`）
4. 在 `.env` 檔案中設定：
   ```ini
   encryption.key = a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6
   ```

## 步驟 4：確認 CORS 設定

確認 `app/Config/Cors.php` 檔案包含生產環境網址：

```php
'allowedOrigins' => [
    'http://localhost:3000',
    'http://127.0.0.1:3000',
    'https://test-sys.srl.tw',  // 確認這行存在
],
```

## 步驟 5：檢查需要上傳的檔案

確認以下檔案和資料夾都存在：

```
api/
├── app/              ✅ 必須上傳
├── public/           ✅ 必須上傳（包含 .htaccess）
├── vendor/           ✅ 必須上傳（composer 安裝的套件）
├── writable/         ✅ 必須上傳
├── .env              ✅ 必須上傳（已設定好的環境變數）
├── composer.json     ✅ 必須上傳
├── composer.lock     ✅ 必須上傳
├── spark             ✅ 必須上傳
└── 其他檔案...
```

## 步驟 6：準備上傳

### 檢查清單

- [ ] `vendor` 資料夾存在且包含檔案
- [ ] `.env` 檔案已設定所有必要參數
- [ ] `.env` 檔案中的 `encryption.key` 已填入
- [ ] `.env` 檔案中的資料庫設定已填入
- [ ] `.env` 檔案中的 `app.baseURL` 已設定為 `https://test-sys.srl.tw/api/`
- [ ] CORS 設定包含 `https://test-sys.srl.tw`

### 上傳方式

1. **使用 FTP 客戶端**（如 FileZilla）
   - 上傳整個 `api` 資料夾到 `public_html/api/`

2. **使用 cPanel 檔案管理器**
   - 將 `api` 資料夾壓縮為 ZIP
   - 上傳 ZIP 檔案到 `public_html/`
   - 在 cPanel 中解壓縮

## 步驟 7：上傳後的設定

### 設定檔案權限

透過 cPanel 檔案管理器或 FTP：

1. 導航到 `api/writable` 資料夾
2. 設定權限為 `755`
3. 對 `writable` 下的子資料夾也設定為 `755`：
   - `writable/cache/`
   - `writable/logs/`
   - `writable/session/`
   - `writable/uploads/`

### 修改 .htaccess

#### 1. 在 `api/` 目錄建立 `.htaccess`（與 `public` 同層級）

建立檔案 `api/.htaccess`，內容如下：

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

#### 2. 修改 `public/.htaccess`

確認 `api/public/.htaccess` 中有：

```apache
RewriteBase /api/
```

**目錄結構應該是：**
```
api/
├── .htaccess      ← 新建（與 public 同層級）
├── public/
│   ├── .htaccess  ← 修改（取消 RewriteBase /api/ 的註解）
│   └── index.php
├── app/
└── ...
```

## 測試

訪問以下 URL 測試：

- `https://test-sys.srl.tw/api/` - 應該顯示 CodeIgniter 歡迎頁面
- `https://test-sys.srl.tw/api/test-cors` - 測試 CORS

## 常見問題

### Q: vendor 資料夾很大，上傳很慢

**A:** 這是正常的，`vendor` 資料夾包含所有 PHP 套件。可以：
- 使用壓縮上傳（ZIP），然後在伺服器上解壓縮
- 使用支援續傳的 FTP 客戶端

### Q: 忘記生成加密金鑰怎麼辦？

**A:** 在本地執行 `php spark key:generate`，然後重新上傳 `.env` 檔案。

### Q: 上傳後出現 500 錯誤

**A:** 檢查：
1. `.env` 檔案是否存在
2. `.env` 檔案中的設定是否正確
3. `writable` 資料夾權限是否為 755
4. 查看 cPanel 錯誤日誌

