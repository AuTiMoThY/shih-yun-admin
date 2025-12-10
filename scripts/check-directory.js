#!/usr/bin/env node

const path = require('path');
const fs = require('fs');

// 獲取當前工作目錄
const currentDir = process.cwd();
const rootDir = path.resolve(__dirname, '..');

// 檢查是否在根目錄
if (currentDir === rootDir) {
  console.error('\n⚠️  警告：您正在根目錄執行 npm 指令！\n');
  console.error('這個專案的 npm 指令應該在以下目錄執行：');
  console.error('  - admin/   (前端專案)');
  console.error('  - api/     (後端專案)\n');
  console.error('請使用以下指令：');
  console.error('  - cd admin && npm [指令]');
  console.error('  - cd api && npm [指令]\n');
  console.error('或者在根目錄使用：');
  console.error('  - npm run admin  (啟動前端開發伺服器)');
  console.error('  - npm run api    (啟動後端伺服器)\n');
  process.exit(1);
}

process.exit(0);

