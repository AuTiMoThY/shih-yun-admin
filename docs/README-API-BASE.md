# API Base URL 設定說明

## 概述

前端需要知道後端 API 的 URL。根據環境不同，使用不同的 URL：
- **開發環境**：`http://localhost:8080`
- **生產環境**：`https://test-sys.srl.tw`

## 自動判斷機制

`nuxt.config.ts` 已經設定為自動判斷：

1. **優先使用環境變數** `NUXT_PUBLIC_API_BASE`
2. **如果沒有設定環境變數**：
   - 開發環境（`npm run dev`）：使用 `http://localhost:8080`
   - 生產環境（`npm run generate`）：使用 `https://test-sys.srl.tw`

## 設定方式

### 方式 1：使用環境變數（推薦）

#### 開發環境

建立 `.env` 檔案（在 `admin/` 目錄下）：

```env
NUXT_PUBLIC_API_BASE=http://localhost:8080
```

#### 生產環境

在執行 `npm run generate` 前，設定環境變數：

**Windows (CMD):**
```cmd
set NUXT_PUBLIC_API_BASE=https://test-sys.srl.tw
npm run generate
```

**Windows (PowerShell):**
```powershell
$env:NUXT_PUBLIC_API_BASE="https://test-sys.srl.tw"
npm run generate
```

**Mac/Linux:**
```bash
export NUXT_PUBLIC_API_BASE=https://test-sys.srl.tw
npm run generate
```

或者建立 `.env.production` 檔案：

```env
NUXT_PUBLIC_API_BASE=https://test-sys.srl.tw
```

### 方式 2：依賴自動判斷（已實作）

如果沒有設定環境變數，系統會自動判斷：
- 開發環境：`http://localhost:8080`
- 生產環境：`https://test-sys.srl.tw`

**注意：** 自動判斷依賴 `NODE_ENV`，確保在執行 `npm run generate` 時 `NODE_ENV=production`。

## 驗證設定

在程式碼中檢查 `apiBase` 的值：

```typescript
const { public: runtimePublic } = useRuntimeConfig();
const apiBase = runtimePublic.apiBase;
console.log('API Base URL:', apiBase);
```

## 常見問題

### Q: 為什麼生產環境還是顯示 `http://localhost:8080`？

**A:** 可能的原因：
1. 沒有設定環境變數，且 `NODE_ENV` 不是 `production`
2. 需要在執行 `npm run generate` 前設定環境變數

**解決方法：**
```bash
# 確保 NODE_ENV 是 production
NODE_ENV=production npm run generate

# 或明確設定 API Base
NUXT_PUBLIC_API_BASE=https://test-sys.srl.tw npm run generate
```

### Q: 如何在 package.json 中設定？

**A:** 可以在 `package.json` 的 scripts 中設定：

```json
{
  "scripts": {
    "dev": "nuxt dev",
    "build": "nuxt build",
    "generate": "cross-env NODE_ENV=production NUXT_PUBLIC_API_BASE=https://test-sys.srl.tw nuxt generate"
  }
}
```

需要安裝 `cross-env`：
```bash
npm install --save-dev cross-env
```

### Q: 可以動態判斷嗎？

**A:** 可以，在客戶端使用 `window.location.origin`：

```typescript
// 在 composable 或 plugin 中
const apiBase = import.meta.client 
  ? window.location.origin 
  : (useRuntimeConfig().public.apiBase || 'http://localhost:8080');
```

但目前的設定已經足夠，建議使用環境變數或自動判斷。

## 部署檢查清單

- [ ] 確認 `.env` 或 `.env.production` 檔案存在（可選）
- [ ] 執行 `npm run generate` 時確認 `NODE_ENV=production`
- [ ] 生成後檢查生成的檔案中 `apiBase` 是否正確
- [ ] 在瀏覽器 console 中檢查 `apiBase` 的值

