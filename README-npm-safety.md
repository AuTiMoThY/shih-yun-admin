# npm 指令安全檢查

為了避免在根目錄意外執行 npm 指令，本專案已設置自動檢查機制。

## 自動檢查

當您在根目錄執行以下指令時，會自動顯示警告並阻止執行：

- `npm install` / `npm i`
- `npm start`
- `npm run build`
- `npm run dev`
- 其他常見的 npm lifecycle hooks

## 正確的使用方式

### 前端專案 (admin/)
```bash
cd admin
npm install
npm run dev
npm i -D <package-name>
npx <command>
```

### 後端專案 (api/)
```bash
cd api
composer install
php spark serve
```

### 根目錄便捷指令
```bash
npm run admin  # 啟動前端開發伺服器
npm run api    # 啟動後端伺服器
```

## 關於 npx 指令

`npx` 指令無法通過 npm lifecycle hooks 攔截。如果您需要執行 `npx` 指令：

1. **推薦方式**：先切換到正確的目錄
   ```bash
   cd admin
   npx nuxt@latest module add vueuse
   ```

2. **使用輔助腳本**（Windows）：
   ```bash
   # PowerShell
   .\scripts\npx-safe.ps1 nuxt@latest module add vueuse
   
   # 或批處理文件
   scripts\npx-safe.bat nuxt@latest module add vueuse
   ```

## 手動檢查

如果需要手動檢查當前目錄是否正確：

```bash
npm run check
```

## 故障排除

如果檢查機制沒有觸發，請確認：

1. `.npmrc` 文件中 `ignore-scripts=false` 已設置
2. `scripts/check-directory.js` 文件存在且可執行
3. 您使用的 npm 版本支持 lifecycle hooks

