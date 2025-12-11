# API 部署檢查清單

> **注意：** 此檢查清單適用於**可以執行指令的伺服器**（VPS、專屬伺服器等）。
> 
> 如果是**虛擬主機**（無法執行指令），請參考：`docs/api-deployment-shared-hosting.md`

## 快速部署步驟（VPS/專屬伺服器）

### 1. 上傳檔案
```bash
# 將 api 資料夾上傳到伺服器
# 建議路徑：/var/www/html/api/
```

### 2. 設定環境變數
```bash
cd /var/www/html/api
cp env .env
# 編輯 .env 檔案，設定以下項目：
```

**必須設定的項目：**
```ini
CI_ENVIRONMENT = production
app.baseURL = 'https://test-sys.srl.tw/api/'
database.default.hostname = [您的資料庫主機]
database.default.database = [您的資料庫名稱]
database.default.username = [您的資料庫使用者]
database.default.password = [您的資料庫密碼]
app.forceGlobalSecureRequests = true
```

### 3. 安裝依賴
```bash
composer install --no-dev --optimize-autoloader
```

### 4. 設定權限
```bash
chmod -R 775 writable/
chown -R www-data:www-data writable/  # 根據您的伺服器使用者調整
```

### 5. 生成加密金鑰
```bash
php spark key:generate
```

### 6. 資料庫遷移（如有需要）
```bash
php spark migrate
```

### 7. 驗證設定

**檢查項目：**
- [ ] `.env` 檔案存在且設定正確
- [ ] `writable` 資料夾權限為 775
- [ ] 加密金鑰已生成
- [ ] CORS 設定包含 `https://test-sys.srl.tw`
- [ ] 資料庫連線正常

**測試 API：**
```bash
curl https://test-sys.srl.tw/api/test-cors
```

---

## 虛擬主機快速檢查清單

### 本地準備（必須在本地完成）

- [ ] 執行 `composer install --no-dev` 安裝依賴
- [ ] 複製 `env` 為 `.env` 並設定所有參數
- [ ] 執行 `php spark key:generate` 生成加密金鑰
- [ ] 確認 CORS 設定包含 `https://test-sys.srl.tw`
- [ ] 確認 `vendor` 資料夾已存在

### 上傳到伺服器

- [ ] 上傳所有檔案（包含 `vendor` 資料夾）
- [ ] 確認 `.env` 檔案已上傳
- [ ] 透過 cPanel/FTP 設定 `writable` 資料夾權限為 755

### 伺服器設定

- [ ] 確認 `.env` 中的資料庫設定正確
- [ ] 確認 `app.baseURL` 設定正確
- [ ] 測試 API 端點可以正常訪問

## 常見問題快速解決

### 403 Forbidden
```bash
# 檢查 .htaccess 檔案
# 檢查檔案權限
chmod 644 public/.htaccess
```

### 資料庫連線失敗
```bash
# 檢查 .env 中的資料庫設定
# 測試資料庫連線
php spark db:table [table_name]
```

### CORS 錯誤
```bash
# 確認 app/Config/Cors.php 包含生產環境網址
# 清除快取
rm -rf writable/cache/*
```

## 更新部署

當需要更新時：
```bash
# 1. 上傳新檔案（保留 .env 和 writable）
# 2. 安裝依賴
composer install --no-dev --optimize-autoloader
# 3. 清除快取
rm -rf writable/cache/*
# 4. 如有資料庫變更
php spark migrate
```

