# 部署時設定 API Base URL

## 問題

部署到伺服器後，`console.log(apiBase)` 顯示 `http://localhost:8080`，而不是 `https://test-sys.srl.tw`。

## 解決方案

已經修改 `nuxt.config.ts`，會自動判斷環境：
- 開發環境：`http://localhost:8080`
- 生產環境：`https://test-sys.srl.tw`

## 部署步驟

### 方法 1：使用 package.json 腳本（已更新）

`package.json` 的 `generate` 腳本已經更新，會自動設定環境變數：

```json
"generate": "cross-env NODE_ENV=production NUXT_PUBLIC_API_BASE=https://test-sys.srl.tw nuxt generate"
```

**需要安裝 `cross-env`：**
```bash
cd admin
npm install --save-dev cross-env
```

然後執行：
```bash
npm run generate
```

### 方法 2：手動設定環境變數

**Windows (CMD):**
```cmd
set NODE_ENV=production
set NUXT_PUBLIC_API_BASE=https://test-sys.srl.tw
npm run generate
```

**Windows (PowerShell):**
```powershell
$env:NODE_ENV="production"
$env:NUXT_PUBLIC_API_BASE="https://test-sys.srl.tw"
npm run generate
```

**Mac/Linux:**
```bash
NODE_ENV=production NUXT_PUBLIC_API_BASE=https://test-sys.srl.tw npm run generate
```

### 方法 3：建立 .env.production 檔案

在 `admin/` 目錄下建立 `.env.production` 檔案：

```env
NODE_ENV=production
NUXT_PUBLIC_API_BASE=https://test-sys.srl.tw
```

然後執行：
```bash
npm run generate
```

## 驗證

生成後，檢查生成的檔案：

1. 開啟 `admin/.output/public/` 中的 HTML 檔案
2. 搜尋 `apiBase` 或 `localhost:8080`
3. 應該看到 `https://test-sys.srl.tw` 而不是 `http://localhost:8080`

或在瀏覽器中：
1. 開啟開發者工具
2. 在 Console 中執行：`console.log(useRuntimeConfig().public.apiBase)`
3. 應該顯示 `https://test-sys.srl.tw`

## 如果還是不行

如果上述方法都不行，可以嘗試：

1. **清除快取後重新生成：**
   ```bash
   rm -rf admin/.output admin/.nuxt
   npm run generate
   ```

2. **檢查環境變數是否正確設定：**
   ```bash
   # Windows
   echo %NODE_ENV%
   echo %NUXT_PUBLIC_API_BASE%
   
   # Mac/Linux
   echo $NODE_ENV
   echo $NUXT_PUBLIC_API_BASE
   ```

3. **使用絕對路徑：**
   在 `nuxt.config.ts` 中，可以強制使用：
   ```typescript
   const apiBase = isProduction 
     ? "https://test-sys.srl.tw" 
     : "http://localhost:8080";
   ```

## 快速檢查清單

- [ ] 已安裝 `cross-env`（如果使用方法 1）
- [ ] 執行 `npm run generate` 時 `NODE_ENV=production`
- [ ] 執行 `npm run generate` 時 `NUXT_PUBLIC_API_BASE=https://test-sys.srl.tw`
- [ ] 生成後檢查檔案確認 `apiBase` 正確
- [ ] 部署後在瀏覽器 console 檢查 `apiBase` 值

