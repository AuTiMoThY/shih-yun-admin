# API 部署快速參考

## 虛擬主機部署（無法執行指令）

### 📋 本地準備步驟

1. **安裝依賴**
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

2. **準備 .env 檔案**
   ```bash
   # Windows
   copy env .env
   
   # Mac/Linux
   cp env .env
   ```
   
   然後編輯 `.env`，設定：
   - `CI_ENVIRONMENT = production`
   - `app.baseURL = 'https://test-sys.srl.tw/api/'`
   - 資料庫設定
   - 執行 `php spark key:generate` 生成加密金鑰

3. **確認 CORS 設定**
   確認 `app/Config/Cors.php` 包含 `https://test-sys.srl.tw`

### 📤 上傳到伺服器

上傳整個 `api` 資料夾到 `public_html/api/`

### ⚙️ 伺服器設定

1. **設定檔案權限**
   - `writable/` 資料夾 → 755
   - `writable/cache/` → 755
   - `writable/logs/` → 755
   - `writable/session/` → 755
   - `writable/uploads/` → 755

2. **設定 .htaccess**
   
   a. 在 `api/` 目錄建立 `.htaccess`（與 `public` 同層級）：
   ```apache
   <IfModule mod_rewrite.c>
       RewriteEngine On
       RewriteCond %{REQUEST_FILENAME} !-f
       RewriteCond %{REQUEST_FILENAME} !-d
       RewriteRule ^(.*)$ public/$1 [L]
       RewriteCond %{REQUEST_URI} ^/api/?$
       RewriteRule ^$ public/ [L]
   </IfModule>
   ```
   
   b. 編輯 `public/.htaccess`，取消註解：
   ```apache
   RewriteBase /api/
   ```

### ✅ 測試

訪問：`https://test-sys.srl.tw/api/test-cors`

---

## 詳細文件

- **虛擬主機部署**：`docs/api-deployment-shared-hosting.md`
- **VPS/專屬伺服器**：`docs/api-deployment.md`
- **本地準備指南**：`prepare-for-deployment.md`

