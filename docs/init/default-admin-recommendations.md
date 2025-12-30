# 預設超級管理員建議

## 問題：預設超級管理員是否該包含在 DB 中？

這是一個常見的系統設計問題，以下是不同方案的優缺點分析：

## 方案比較

### 方案 A：包含預設超級管理員（推薦用於開發環境）

**優點：**
- ✅ 快速開始，無需手動建立帳號
- ✅ 適合開發和測試環境
- ✅ 降低初次設定的複雜度
- ✅ 方便自動化部署腳本

**缺點：**
- ⚠️ 安全性風險（如果使用預設密碼）
- ⚠️ 需要額外的安全措施
- ⚠️ 可能被未授權人員使用

**適用場景：**
- 開發環境
- 測試環境
- 快速原型開發
- 內部系統

### 方案 B：不包含預設超級管理員（推薦用於生產環境）

**優點：**
- ✅ 更高的安全性
- ✅ 避免預設帳號被濫用
- ✅ 強制管理員手動建立帳號
- ✅ 符合安全最佳實踐

**缺點：**
- ❌ 需要額外的初始化步驟
- ❌ 初次設定較複雜
- ❌ 可能忘記建立管理員帳號

**適用場景：**
- 生產環境
- 對安全性要求高的系統
- 公開部署的系統

## 建議方案

### 混合方案（最佳實踐）

**開發/測試環境：**
- 使用 `docs/init/init-with-super-admin.sql`
- 包含預設帳號（帳號：`admin`，密碼：`admin`）
- 方便快速開發和測試

**生產環境：**
- 使用 `docs/init/init-clean.sql`
- 不包含預設帳號
- 手動建立第一個管理員帳號
- 或使用安全的初始化腳本

## 安全建議

### 如果使用預設超級管理員：

1. **立即修改密碼**
   ```sql
   -- 使用 PHP 產生新的密碼雜湊
   UPDATE sys_admin 
   SET password_hash = '$2y$10$新產生的雜湊值' 
   WHERE username = 'admin';
   ```

2. **使用強密碼**
   - 至少 12 個字元
   - 包含大小寫字母、數字和特殊字元
   - 不使用常見密碼

3. **限制預設帳號使用**
   - 建立專屬的管理員帳號後，考慮停用或刪除預設帳號
   - 或將預設帳號改為不同的使用者名稱

4. **記錄和監控**
   - 記錄所有管理員登入活動
   - 監控異常登入行為

### 如果不使用預設超級管理員：

1. **建立第一個管理員的步驟**
   ```sql
   -- 1. 建立管理員帳號（使用 PHP password_hash() 產生密碼雜湊）
   INSERT INTO sys_admin (username, password_hash, name, status) 
   VALUES ('your_admin', '$2y$10$...', '管理員姓名', 1);
   
   -- 2. 分配超級管理員角色
   INSERT INTO sys_user_roles (user_id, role_id) 
   VALUES (LAST_INSERT_ID(), 1);
   ```

2. **使用初始化腳本**
   - 建立 PHP 腳本自動化此過程
   - 從環境變數讀取初始密碼
   - 部署後自動執行

## 實作範例

### 安全的初始化腳本（PHP）

```php
<?php
// init-admin.php
// 從環境變數讀取初始密碼，避免寫死在程式碼中

$db = new PDO('mysql:host=localhost;dbname=your_db', 'user', 'pass');

// 從環境變數或設定檔讀取
$username = getenv('INIT_ADMIN_USERNAME') ?: 'admin';
$password = getenv('INIT_ADMIN_PASSWORD');
$name = getenv('INIT_ADMIN_NAME') ?: '系統管理員';

if (!$password) {
    die("錯誤：請設定 INIT_ADMIN_PASSWORD 環境變數\n");
}

$passwordHash = password_hash($password, PASSWORD_BCRYPT);

// 建立管理員
$stmt = $db->prepare("
    INSERT INTO sys_admin (username, password_hash, name, status) 
    VALUES (?, ?, ?, 1)
");
$stmt->execute([$username, $passwordHash, $name]);

$userId = $db->lastInsertId();

// 分配超級管理員角色
$stmt = $db->prepare("
    INSERT INTO sys_user_roles (user_id, role_id) 
    VALUES (?, 1)
");
$stmt->execute([$userId]);

echo "管理員帳號建立成功：{$username}\n";
```

## 結論

**建議：**
- **開發環境**：使用包含預設超級管理員的 SQL 文件，方便快速開發
- **生產環境**：使用乾淨的 SQL 文件，手動或透過腳本建立第一個管理員
- **無論哪種方案**：都要確保使用強密碼，並在部署後立即修改預設密碼

## 相關文件

- [專案遷移指南](../migration-guide.md)
- [RBAC 快速開始指南](../rbac/rbac-quick-start.md)
- [部署指南](../deployment/README-DEPLOYMENT.md)

