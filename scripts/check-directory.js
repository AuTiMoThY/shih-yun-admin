#!/usr/bin/env node

const path = require('path');
const fs = require('fs');

// 獲取當前工作目錄
const currentDir = process.cwd();
const rootDir = path.resolve(__dirname, '..');

// 檢查是否在根目錄
if (currentDir === rootDir) {
  // 檢查是否有 package.json 且包含 dependencies 或 devDependencies
  const packageJsonPath = path.join(rootDir, 'package.json');
  let shouldWarn = true;
  
  try {
    const packageJson = JSON.parse(fs.readFileSync(packageJsonPath, 'utf8'));
    // 如果根目錄的 package.json 有依賴項，可能是合法的操作
    if (packageJson.dependencies || packageJson.devDependencies) {
      shouldWarn = false;
    }
  } catch (e) {
    // 如果讀取失敗，繼續警告
  }

  if (shouldWarn) {
    console.error('\n⚠️  警告：您正在根目錄執行 npm 指令！\n');
    console.error('這個專案的 npm 指令應該在以下目錄執行：');
    console.error('  - admin/   (前端專案)');
    console.error('  - api/     (後端專案)\n');
    console.error('請使用以下指令：');
    console.error('  - cd admin && npm [指令]');
    console.error('  - cd api && npm [指令]\n');
    console.error('例如：');
    console.error('  - cd admin && npm i -D @vueuse/nuxt @vueuse/core');
    console.error('  - cd admin && npx nuxt@latest module add vueuse\n');
    console.error('或者在根目錄使用：');
    console.error('  - npm run admin  (啟動前端開發伺服器)');
    console.error('  - npm run api    (啟動後端伺服器)\n');
    process.exit(1);
  }
}

process.exit(0);

