# 專案遷移指南

本指南說明如何將此管理後台系統遷移到其他專案。

## 前置需求

- PHP 8.1+ 與 CodeIgniter 4
- Node.js 18+ 與 pnpm
- MySQL/MariaDB 10.4+
- Nuxt.js 4.x

## 遷移步驟

### 1. 複製專案結構

將以下目錄和文件複製到新專案：

```
新專案/
├── admin/              # Nuxt.js 前端應用
├── api/                # CodeIgniter 4 後端 API
├── docs/               # 文件目錄（可選）
└── package.json        # 根目錄的 package.json
```

### 2. 安裝依賴

```bash
# 安裝前端依賴
cd admin
pnpm install

# 安裝後端依賴
cd ../api
composer install
```

### 3. 設定環境變數

#### 前端環境變數（admin/.env 或 nuxt.config.ts）

```env
NUXT_PUBLIC_API_BASE=http://localhost:8080
```

#### 後端環境變數（api/env）

複製 `api/env` 並修改資料庫連線設定：

```env
database.default.hostname = localhost
database.default.database = your_database_name
database.default.username = your_username
database.default.password = your_password
```

### 4. 初始化資料庫

#### 選項 A：使用乾淨的初始化 SQL（推薦）

執行以下 SQL 文件（按順序）：

1. `sys_structure.sql` - 系統架構表
2. `sys_module.sql` - 模組表（不含測試資料）
3. `sys_admin.sql` - 管理員表（不含測試資料）
4. `docs/rbac/rbac.sql` - RBAC 系統表（包含預設超級管理員角色）

然後手動建立第一個管理員帳號。

#### 選項 B：使用包含預設超級管理員的初始化 SQL

執行 `docs/init/init-with-super-admin.sql`，此文件包含：
- 所有系統表結構
- 預設超級管理員角色
- 預設超級管理員帳號（帳號：`admin`，密碼：`admin`）

**⚠️ 重要：部署到生產環境前，務必修改預設管理員密碼！**

### 5. 修改專案特定設定

#### 資料庫名稱

在所有 SQL 文件中，將資料庫名稱從 `shih-yun` 改為您的資料庫名稱：

```sql
-- 搜尋並替換
-- `shih-yun` → `your_database_name`
```

#### API 基礎路徑

修改 `admin/nuxt.config.ts` 中的 API 基礎路徑：

```typescript
runtimeConfig: {
  public: {
    apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8080'
  }
}
```

#### 專案特定模組

根據新專案需求，修改或新增：
- `sys_module.sql` - 模組定義
- `app_*.sql` - 應用程式相關資料表

### 6. 測試安裝

1. 啟動後端 API：
   ```bash
   cd api
   php spark serve
   ```

2. 啟動前端開發伺服器：
   ```bash
   cd admin
   pnpm run dev
   ```

3. 訪問 `http://localhost:3000/login` 並使用預設帳號登入

4. 驗證以下功能：
   - 登入/登出
   - 角色管理
   - 權限管理
   - 管理員管理

## 檔案清單

### 核心系統表（必須）

- `sys_structure.sql` - 系統架構層級表
- `sys_module.sql` - 模組表
- `sys_admin.sql` - 管理員表
- `docs/rbac/rbac.sql` - RBAC 系統表（角色、權限、關聯表）

### 應用程式表（依專案需求）

- `app_about.sql` - 關於我們
- `app_news.sql` - 最新消息
- `app_contact.sql` - 聯絡我們
- `app_case.sql` - 建案
- `company_base.sql` - 公司基本資料

### 可選初始化文件

- `docs/init/init-clean.sql` - 乾淨初始化（不含測試資料）
- `docs/init/init-with-super-admin.sql` - 包含預設超級管理員

## 常見問題

### Q: 如何建立第一個管理員帳號？

A: 有三種方式：

1. **使用 API 產生密碼雜湊後執行 SQL**（推薦）：
   ```bash
   # 1. 先取得密碼雜湊值
   curl "http://localhost:8080/password-hash?password=your_password"
   
   # 2. 使用返回的雜湊值執行 SQL
   ```
   ```sql
   INSERT INTO sys_admin (username, password_hash, name, status) 
   VALUES ('admin', '$2y$10$從API取得的雜湊值', '管理員', 1);
   
   -- 分配超級管理員角色
   INSERT INTO sys_user_roles (user_id, role_id) 
   VALUES (LAST_INSERT_ID(), 1);
   ```

2. **使用 PHP 產生密碼雜湊後執行 SQL**：
   ```php
   <?php
   echo password_hash('your_password', PASSWORD_BCRYPT);
   ?>
   ```
   然後使用產生的雜湊值執行上述 SQL。

3. **使用後台介面**：登入後在「系統設定」→「管理員設定」中新增

### Q: 如何產生密碼雜湊？

A: 有三種方式：

1. **使用 API 端點**（推薦）：
   ```
   GET http://localhost:8080/password-hash?password=your_password
   ```
   會返回 JSON 格式的雜湊值。

2. **使用 PHP**：
   ```php
   <?php
   echo password_hash('your_password', PASSWORD_BCRYPT);
   ?>
   ```

3. **使用 CodeIgniter 4 的 `password_hash()` 函數**。

### Q: 預設超級管理員是否該包含在 DB 中？

A: 請參考 `docs/init/default-admin-recommendations.md` 的詳細說明。

## 下一步

- 閱讀 [RBAC 快速開始指南](./rbac/rbac-quick-start.md)
- 閱讀 [部署指南](./deployment/README-DEPLOYMENT.md)
- 根據專案需求自訂模組和權限

