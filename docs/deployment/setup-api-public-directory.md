# 在虛擬主機中設定 /api 指向 public 資料夾

## 問題說明

CodeIgniter 4 的網站根目錄應該是 `public` 資料夾，但在虛擬主機中，如果 API 部署在 `/api` 子目錄，需要讓 `https://test-sys.srl.tw/api/` 自動指向 `public` 資料夾。

## 解決方案

### 方法 1：使用 .htaccess 重定向（推薦）

這是最簡單且最適合虛擬主機的方法。

#### 步驟 1：在 `api/` 目錄建立 `.htaccess`

在 `public_html/api/` 目錄中（與 `public` 資料夾同層級）建立一個 `.htaccess` 檔案：

**檔案位置：** `public_html/api/.htaccess`

**檔案內容：**
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

#### 步驟 2：修改 `api/public/.htaccess`

確認 `api/public/.htaccess` 中有以下設定（取消註解）：

```apache
RewriteBase /api/
```

#### 步驟 3：確認目錄結構

最終的目錄結構應該是：

```
public_html/
└── api/
    ├── .htaccess          ← 新建（與 public 同層級）
    ├── app/
    ├── public/            ← 實際的網站根目錄
    │   ├── .htaccess      ← 已修改（RewriteBase /api/）
    │   └── index.php
    ├── vendor/
    ├── writable/
    ├── .env
    └── ...
```

#### 工作原理

1. 當訪問 `https://test-sys.srl.tw/api/` 時
2. `api/.htaccess` 會將請求重定向到 `api/public/`
3. `api/public/.htaccess` 會處理 CodeIgniter 的路由
4. 最終請求會被正確處理

### 方法 2：使用 cPanel 子域名（進階）

如果您的虛擬主機支援設定子域名，可以建立一個專門的 API 子域名：

#### 步驟：

1. 在 cPanel 中建立子域名 `api.test-sys.srl.tw`
2. 將 DocumentRoot 指向 `public_html/api/public`
3. 這樣可以直接訪問 `https://api.test-sys.srl.tw/`
4. 需要更新 `.env` 中的 `app.baseURL` 為 `https://api.test-sys.srl.tw/`

**優點：**
- 不需要 `/api/` 路徑前綴
- 更符合 CodeIgniter 4 的標準部署方式

**缺點：**
- 需要 DNS 設定
- 需要 SSL 憑證（如果使用 HTTPS）

### 方法 3：符號連結（通常不適用於虛擬主機）

如果虛擬主機支援符號連結（Symbolic Links），可以建立符號連結，但這通常需要 SSH 權限，大多數虛擬主機不支援。

## 驗證設定

設定完成後，測試以下 URL：

1. `https://test-sys.srl.tw/api/` - 應該顯示 CodeIgniter 歡迎頁面或 API 回應
2. `https://test-sys.srl.tw/api/test-cors` - 測試 CORS 端點
3. `https://test-sys.srl.tw/structure/get` - 測試結構 API

## 常見問題

### Q1: 出現 500 Internal Server Error

**可能原因：**
- `.htaccess` 語法錯誤
- Apache `mod_rewrite` 未啟用（大多數虛擬主機預設已啟用）

**解決方法：**
- 檢查 `.htaccess` 檔案語法
- 確認檔案權限為 644
- 查看 cPanel 錯誤日誌

### Q2: 出現 404 Not Found

**可能原因：**
- `RewriteBase` 設定錯誤
- `.htaccess` 檔案位置錯誤

**解決方法：**
- 確認 `api/.htaccess` 在正確位置（與 `public` 同層級）
- 確認 `api/public/.htaccess` 中有 `RewriteBase /api/`

### Q3: 路徑重複（如 `/api/public/`）

**可能原因：**
- `.htaccess` 重定向規則有問題

**解決方法：**
- 確認 `api/.htaccess` 中的規則正確
- 清除瀏覽器快取

## 推薦設定

對於虛擬主機，**強烈推薦使用方法 1（.htaccess 重定向）**，因為：
- ✅ 不需要額外設定
- ✅ 不需要修改 DNS
- ✅ 適用於所有虛擬主機
- ✅ 設定簡單

## 相關檔案

- `api/.htaccess` - 根目錄重定向規則（新建）
- `api/public/.htaccess` - CodeIgniter 路由規則（已存在，需修改）
- `api/.env` - 環境設定（需確認 `app.baseURL`）

