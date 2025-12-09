# 後台登入串接資料庫說明

本文件說明如何讓前端登入流程直接使用 `sysadmin` 資料表驗證，並介紹相關 API、資料流與測試步驟。

## 資料表

- 表名：`sysadmin`
- 重要欄位：`username`、`password_hash`（bcrypt）、`status`（1 啟用 / 0 停用）
- 參考範例：`sysadmin.sql`（已含示範帳號/密碼雜湊）

## 後端（CodeIgniter 4）

- 路由：`api/app/Config/Routes.php`
  - `POST /api/admins/login`：登入並寫入 Session
  - `GET /api/admins/me`：取得目前登入者
  - `POST /api/admins/logout`：登出並清除 Session
- 控制器：`api/app/Controllers/Admins.php`
  - `login()`：驗證 `username`/`password`、檢查 `status`，寫入 `admin_user` Session，回傳 `user` 與 `token(session_id)`
  - `me()`：從 Session 讀取 `admin_user`，未登入回 401
  - `logout()`：移除 Session 並回傳成功
- Model：`api/app/Models/SysadminModel.php` 對應 `sysadmin` 表，使用 `password_hash` 儲存密碼。
- 注意事項：
  - 新增帳號請使用 `password_hash($plain, PASSWORD_DEFAULT)` 生成雜湊。
  - 預設 Session 以檔案儲存，登入會 `regenerate()` 以避免固定式攻擊。

## 前端（Nuxt 3）

- 組件：`admin/app/composables/useAuth.ts`
  - `login()`：呼叫 `/api/admins/login`（`credentials: "include"`），成功後將 `token`/`user` 存於 `useState` 與 `localStorage`。
  - `fetchUser()`：呼叫 `/api/admins/me`，失敗則執行 `logout()`。
  - `logout()`：呼叫 `/api/admins/logout`，並清掉本地狀態與 localStorage。
  - `initAuth()`：若有 token，啟動時會嘗試 `fetchUser()` 以同步 Session 狀態。
- 頁面：`admin/app/pages/login.vue` 透過 `useAuth().login` 提交表單，成功導向首頁。
- 環境設定：`admin/nuxt.config.ts`
  - `runtimeConfig.public.apiBase` 預設 `http://localhost:8080`
  - 本機開發有 `/api` devProxy，跨域時請同時設定後端 CORS 並保持 `credentials: "include"`。

## 測試流程

1. 匯入 `sysadmin.sql` 至資料庫，確認有測試帳號（密碼為對應雜湊前的明文）。
2. 後端啟動（預設 8080）：`php spark serve --port 8080`
3. 前端啟動（預設 3000）：於 `admin` 執行 `npm run dev` 或對應指令。
4. 以瀏覽器開啟前端，使用資料庫中的帳密登入。
5. 成功後應能導向首頁；開新分頁呼叫 `/api/admins/me` 可取得登入資訊；登出後再呼叫 `/api/admins/me` 應回 401。

## 常見問題

- **登入回 401/403**：確認帳號存在、`status=1` 且密碼雜湊正確。
- **跨網域 Cookie 未寫入**：確保前端呼叫使用 `credentials: "include"`，後端 CORS 允許來源且 `Access-Control-Allow-Credentials: true`。
- **Session 未持久**：確認 PHP session 儲存路徑可寫入，或依需要改用 Redis / DB session driver。

