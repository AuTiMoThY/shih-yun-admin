# 修正 API 路徑重複問題

## 問題描述

部署後訪問 `https://test-sys.srl.tw/api/test-cors` 時，實際變成了 `https://test-sys.srl.tw/api/api/test-cors`，路徑重複。

## 問題原因

當 `public/.htaccess` 中設定了 `RewriteBase /api/` 後，CodeIgniter 會自動在所有路由前加上 `/api/` 前綴。但路由定義中也包含了 `/api/` 前綴，導致路徑重複。

**流程：**
1. 訪問 `/api/test-cors`
2. `api/.htaccess` 重定向到 `public/test-cors`
3. `public/.htaccess` 的 `RewriteBase /api/` 讓 CodeIgniter 認為路徑是 `/api/test-cors`
4. 但路由定義是 `/api/test-cors`，所以最終匹配成了 `/api/api/test-cors` ❌

## 解決方案

移除路由定義中的 `/api/` 前綴，因為 `RewriteBase /api/` 已經處理了。

### 修改前：
```php
$routes->get('/api/test-cors', 'TestCors::index');
$routes->post('/api/admins/login', 'AuthController::login');
$routes->get('/structure/get', 'StructureController::getLevels');
```

### 修改後：
```php
$routes->get('/test-cors', 'TestCors::index');
$routes->post('/admins/login', 'AuthController::login');
$routes->get('/structure/get', 'StructureController::getLevels');
```

## 工作原理

**正確流程：**
1. 前端呼叫：`https://test-sys.srl.tw/api/test-cors`
2. `api/.htaccess` 重定向到 `public/test-cors`
3. `public/.htaccess` 的 `RewriteBase /api/` 讓 CodeIgniter 認為完整路徑是 `/api/test-cors`
4. 路由定義是 `/test-cors`，但因為 `RewriteBase /api/`，CodeIgniter 會匹配 `/api/test-cors` ✅

## 前端不需要修改

前端繼續使用 `${apiBase}/api/...` 來呼叫 API，例如：
- `${apiBase}/api/test-cors`
- `${apiBase}/api/admins/login`
- `${apiBase}/structure/get`

其中 `apiBase` 在生產環境應該是 `https://test-sys.srl.tw`。

## 已修改的路由

以下路由已移除 `/api/` 前綴：

- `/test-cors` (原 `/api/test-cors`)
- `/admins/add` (原 `/api/admins/add`)
- `/admins/update` (原 `/api/admins/update`)
- `/admins/delete` (原 `/api/admins/delete`)
- `/admins/get` (原 `/api/admins/get`)
- `/admins/login` (原 `/api/admins/login`)
- `/admins/me` (原 `/api/admins/me`)
- `/admins/logout` (原 `/api/admins/logout`)
- `/structure/add` (原 `/structure/add`)
- `/structure/update` (原 `/structure/update`)
- `/structure/update-sort-order` (原 `/structure/update-sort-order`)
- `/structure/delete` (原 `/structure/delete`)
- `/structure/get` (原 `/structure/get`)

## 測試

部署後測試以下 URL：

- ✅ `https://test-sys.srl.tw/api/test-cors`
- ✅ `https://test-sys.srl.tw/api/admins/login`
- ✅ `https://test-sys.srl.tw/structure/get`

應該都能正常運作，不會再出現路徑重複的問題。

