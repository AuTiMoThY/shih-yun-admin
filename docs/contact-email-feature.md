# 聯絡表單發送 Email 功能說明

本文件說明聯絡表單的發送郵件功能，包括配置方式、API 使用、前端整合與故障排除。

## 功能概述

此功能允許管理員在後台回覆聯絡表單後，直接發送回信郵件給提交表單的使用者。主要特點包括：

- **自動化發送**：填寫回信內容後一鍵發送郵件
- **HTML 郵件**：支援 HTML 格式的郵件內容
- **狀態自動更新**：發送成功後自動將狀態更新為「已處理」
- **錯誤處理**：完整的錯誤檢查與日誌記錄

## 配置方式

### 方式一：配置檔設定（推薦用於開發環境）

編輯 `api/app/Config/Email.php` 檔案：

```php
public string $fromEmail  = 'noreply@example.com';  // 發件人信箱
public string $fromName   = '系統管理員';             // 發件人名稱

// 使用 SMTP（推薦）
public string $protocol = 'smtp';
public string $SMTPHost = 'smtp.example.com';       // SMTP 伺服器
public string $SMTPUser = 'your-email@example.com'; // SMTP 帳號
public string $SMTPPass = 'your-password';          // SMTP 密碼
public int $SMTPPort = 587;                         // SMTP 埠號（587 為 TLS，465 為 SSL）
public string $SMTPCrypto = 'tls';                  // 加密方式：'tls' 或 'ssl'
```

### 方式二：環境變數設定（推薦用於生產環境）

在 `api/.env` 檔案中設定（如果 CodeIgniter 4 支援環境變數覆蓋）：

```ini
# Email 配置
email.fromEmail = noreply@example.com
email.fromName = 系統管理員
email.protocol = smtp
email.SMTPHost = smtp.example.com
email.SMTPUser = your-email@example.com
email.SMTPPass = your-password
email.SMTPPort = 587
email.SMTPCrypto = tls
```

### 常用 SMTP 服務商設定

#### Gmail

```php
public string $protocol = 'smtp';
public string $SMTPHost = 'smtp.gmail.com';
public string $SMTPUser = 'your-email@gmail.com';
public string $SMTPPass = 'your-app-password';  // 需使用應用程式密碼
public int $SMTPPort = 587;
public string $SMTPCrypto = 'tls';
```

**注意**：Gmail 需要使用「應用程式密碼」而非一般密碼。請到 Google 帳戶設定中生成應用程式密碼。

#### Microsoft Outlook / Office 365

```php
public string $protocol = 'smtp';
public string $SMTPHost = 'smtp.office365.com';
public string $SMTPUser = 'your-email@outlook.com';
public string $SMTPPass = 'your-password';
public int $SMTPPort = 587;
public string $SMTPCrypto = 'tls';
```

#### 其他 SMTP 服務

大多數 SMTP 服務使用以下標準設定：

```php
public string $protocol = 'smtp';
public string $SMTPHost = 'smtp.your-provider.com';  // 請查詢您的服務商
public string $SMTPUser = 'your-email@domain.com';
public string $SMTPPass = 'your-password';
public int $SMTPPort = 587;  // 或 465（SSL）
public string $SMTPCrypto = 'tls';  // 或 'ssl'（當 Port = 465 時）
```

### 使用 PHP mail() 函數（不推薦，限制較多）

如果伺服器支援且不需要 SMTP 認證，可以使用 PHP 內建的 mail 函數：

```php
public string $protocol = 'mail';
```

**注意**：此方式在大多數虛擬主機上可能無法正常運作，建議使用 SMTP。

## API 文檔

### 端點資訊

- **URL**：`POST /app-contact/send-email`
- **需要認證**：是（後台管理功能）
- **Content-Type**：`application/json`

### 請求格式

```json
{
  "id": 1
}
```

**參數說明**：

| 參數 | 類型 | 必填 | 說明 |
|------|------|------|------|
| id | integer | 是 | 聯絡表單的 ID |

### 回應格式

**成功回應**（HTTP 200）：

```json
{
  "success": true,
  "message": "郵件發送成功"
}
```

**錯誤回應**（HTTP 400/404/500）：

```json
{
  "success": false,
  "message": "錯誤訊息",
  "error": "詳細錯誤資訊（僅開發環境顯示）"
}
```

### 錯誤碼說明

| HTTP 狀態碼 | 說明 |
|------------|------|
| 200 | 郵件發送成功 |
| 400 | 缺少 ID、回信內容為空，或收件人信箱無效 |
| 404 | 聯絡表單不存在 |
| 500 | 郵件發送失敗或伺服器錯誤 |

### 驗證規則

API 會進行以下驗證：

1. **ID 驗證**：必須提供有效的聯絡表單 ID
2. **回信內容驗證**：該聯絡表單必須已有回信內容（`reply` 欄位不為空）
3. **收件人驗證**：聯絡表單的 `email` 欄位必須為有效的電子郵件格式

## 前端使用說明

### Composable 函數

在 `admin/app/composables/useAppContact.ts` 中已提供 `sendEmail` 函數：

```typescript
const { sendEmail } = useAppContact();

// 發送郵件
const result = await sendEmail(contactId);
if (result.success) {
    // 發送成功
} else {
    // 發送失敗，錯誤訊息已透過 toast 顯示
}
```

### 組件使用範例

在 `FormPage.vue` 中的實作範例：

```typescript
const handleSendEmail = async () => {
    if (!props.initialData?.id) {
        errors.reply = "缺少聯絡表單 ID";
        return;
    }

    // 驗證表單
    if (!validateForm()) {
        return;
    }

    formLoading.value = true;

    try {
        // 先更新回信內容
        const updateResult = await updateReply(
            props.initialData.id,
            form.reply || ""
        );

        if (!updateResult.success) {
            return;
        }

        // 然後發送郵件
        const sendResult = await sendEmail(props.initialData.id);

        if (sendResult.success) {
            // 發送成功後，如果狀態是未處理，自動更新為已處理
            if (form.status === 0) {
                await updateStatus(props.initialData.id, 1);
            }
        }
    } catch (error: any) {
        errors.reply = error.message || "發送郵件失敗";
    } finally {
        formLoading.value = false;
    }
};
```

## 郵件模板

### HTML 郵件結構

系統會自動生成 HTML 格式的郵件，包含以下部分：

1. **標題區塊**：顯示收件人姓名
2. **回信內容區塊**：顯示管理員填寫的回信內容
3. **頁尾區塊**：系統提示訊息

### 郵件內容處理

- **HTML 內容**：如果回信內容包含 HTML 標籤，會直接使用
- **純文字內容**：如果回信內容為純文字，會自動轉換為 HTML（換行轉為 `<br>` 標籤）

### 郵件主題

郵件主題格式：`回覆您的聯絡表單 - {客戶姓名}`

例如：`回覆您的聯絡表單 - 張三`

### 自訂郵件模板

如需自訂郵件模板，請編輯 `api/app/Controllers/AppContactController.php` 中的 `buildEmailBody()` 方法。

## 測試方法

### 1. 本地測試

#### 步驟 1：配置 Email 設定

在 `api/app/Config/Email.php` 中設定測試用的 SMTP 資訊。

#### 步驟 2：建立測試聯絡表單

透過前台或直接寫入資料庫建立一筆聯絡表單記錄，確保：

- `email` 欄位為有效的電子郵件地址
- `reply` 欄位有回信內容

#### 步驟 3：呼叫 API 測試

使用 Postman 或 curl 測試：

```bash
curl -X POST http://localhost:8080/app-contact/send-email \
  -H "Content-Type: application/json" \
  -H "Cookie: ci_session=your_session_id" \
  -d '{"id": 1}'
```

#### 步驟 4：檢查結果

- 檢查 API 回應是否成功
- 檢查收件信箱是否收到郵件
- 檢查資料庫中該筆記錄的 `status` 是否更新為 1

### 2. 前端測試

1. 登入後台管理系統
2. 進入聯絡表單列表頁面
3. 選擇一筆聯絡表單進行編輯
4. 填寫回信內容
5. 點擊「發送信件」按鈕
6. 確認是否顯示成功訊息
7. 檢查收件信箱是否收到郵件

### 3. 測試檢查清單

- [ ] Email 配置正確
- [ ] SMTP 連線正常（或 mail() 函數可用）
- [ ] 聯絡表單有回信內容
- [ ] 收件人信箱格式有效
- [ ] API 回傳成功
- [ ] 郵件成功送達
- [ ] 狀態自動更新為「已處理」
- [ ] 錯誤訊息正確顯示

## 常見問題

### Q1：郵件發送失敗，錯誤訊息顯示連線逾時

**A**：檢查以下項目：

1. **SMTP 設定**：確認 `SMTPHost`、`SMTPPort` 是否正確
2. **防火牆**：確認伺服器防火牆允許連線到 SMTP 伺服器
3. **加密方式**：確認 `SMTPCrypto` 設定正確（TLS 使用 587 埠，SSL 使用 465 埠）
4. **帳號密碼**：確認 `SMTPUser` 和 `SMTPPass` 正確

### Q2：Gmail 發送失敗，顯示認證錯誤

**A**：Gmail 需要使用「應用程式密碼」而非一般密碼：

1. 前往 Google 帳戶設定
2. 啟用「兩步驟驗證」
3. 生成「應用程式密碼」
4. 將應用程式密碼填入 `SMTPPass` 欄位

### Q3：郵件進入垃圾郵件匣

**A**：可嘗試以下方法改善：

1. **SPF 記錄**：在 DNS 中設定 SPF 記錄
2. **DKIM 簽章**：設定 DKIM 簽章（需要郵件伺服器支援）
3. **發件人地址**：使用與域名相符的發件人地址
4. **郵件內容**：避免使用可能被判定為垃圾郵件的關鍵字

### Q4：開發環境無法發送郵件

**A**：在開發環境中可以使用以下方式：

1. **使用 Mailtrap 等測試服務**：設定 Mailtrap 的 SMTP 資訊進行測試
2. **記錄郵件內容**：修改代碼，將郵件內容記錄到日誌檔案而不實際發送
3. **使用 mail() 函數**：如果本地環境支援，可暫時使用 `protocol = 'mail'`

### Q5：郵件內容顯示亂碼

**A**：確認以下設定：

1. **字符編碼**：確認 `charset` 設定為 `UTF-8`（預設已設定）
2. **郵件編碼**：確認郵件內容使用 UTF-8 編碼
3. **資料庫編碼**：確認資料庫欄位使用 UTF-8 編碼

## 故障排除

### 檢查日誌

郵件發送失敗時，錯誤會記錄在 CodeIgniter 的日誌檔案中：

- **日誌位置**：`api/writable/logs/log-YYYY-MM-DD.log`
- **搜尋關鍵字**：`sendContactEmail`、`發送郵件失敗`

### 啟用除錯模式

在開發環境中，可以在 `api/.env` 設定：

```ini
CI_ENVIRONMENT = development
```

這樣錯誤訊息會包含更詳細的資訊。

### 測試 SMTP 連線

可以使用 PHP 指令碼測試 SMTP 連線：

```php
<?php
$smtp = stream_socket_client("tls://smtp.example.com:587", $errno, $errstr, 30);
if ($smtp) {
    echo "連線成功\n";
    fclose($smtp);
} else {
    echo "連線失敗: $errstr ($errno)\n";
}
```

### 檢查 PHP 擴充功能

確認 PHP 已安裝必要的擴充功能：

```bash
php -m | grep -E "openssl|sockets"
```

如果缺少這些擴充功能，請安裝：

```bash
# Ubuntu/Debian
sudo apt-get install php-openssl php-sockets

# CentOS/RHEL
sudo yum install php-openssl php-sockets
```

## 安全性建議

1. **敏感資訊保護**：
   - 不要在程式碼中硬編碼 SMTP 密碼
   - 使用環境變數或配置檔（不提交到版本控制）

2. **輸入驗證**：
   - API 已實作完整的輸入驗證
   - 確保收件人信箱格式有效
   - 防止郵件注入攻擊（使用 CodeIgniter 內建的過濾機制）

3. **錯誤處理**：
   - 不要向使用者暴露詳細的錯誤訊息（生產環境）
   - 使用日誌記錄詳細錯誤資訊供除錯使用

4. **郵件內容**：
   - 使用 `htmlspecialchars()` 防止 XSS 攻擊
   - 驗證 HTML 內容的安全性

## 相關檔案

- **後端控制器**：`api/app/Controllers/AppContactController.php`
- **Email 配置檔**：`api/app/Config/Email.php`
- **路由設定**：`api/app/Config/Routes.php`
- **前端 Composable**：`admin/app/composables/useAppContact.ts`
- **前端組件**：`admin/app/components/App/Contact/FormPage.vue`

## 更新記錄

- **2024-XX-XX**：初始版本，實作基本發送郵件功能

