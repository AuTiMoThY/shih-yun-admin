# 系統架構與模組改進方案

## 問題描述

目前系統架構設計中：
- 系統架構（`sys_structure`）可以設定網頁單元和選模組
- URL 就是模組代碼（`sys_module.name`）
- **問題**：當 2 個不同的單元都使用同一個模組時，它們會撈同一筆資料

### 現有架構
```
sys_structure (系統架構)
  ├─ id
  ├─ label (單元名稱)
  ├─ module_id → sys_module.id
  └─ ...

sys_module (模組)
  ├─ id
  ├─ label (模組名稱)
  ├─ name (模組代碼，作為 URL)
  └─ ...

app_contact / app_news / app_about (模組資料表)
  ├─ id
  └─ ... (沒有 structure_id)
```

### 問題場景
假設有兩個單元：
- 單元 A：`label="聯絡表單A"`, `module_id=3` (contact)
- 單元 B：`label="聯絡表單B"`, `module_id=3` (contact)

兩者都會導向 `/contact`，因此會撈到同一筆資料。

---

## 改進方案

### 方案一：在資料表中加入 structure_id（推薦）

**優點**：
- 資料可以明確區分不同單元
- 不需要改變 URL 結構
- 向後兼容性好

**缺點**：
- 需要修改所有相關資料表
- 需要修改所有相關 API

#### 實作步驟

1. **資料庫修改**
   - 在 `sys_structure` 表中加入 `url` 欄位（可選，用於自訂 URL）
   - 在每個模組對應的資料表中加入 `structure_id` 欄位
     - `app_contact` 加入 `structure_id`
     - `app_news` 加入 `structure_id`
     - `app_about` 加入 `structure_id`
     - 其他模組資料表也要加入

2. **API 修改**
   - 所有查詢資料的 API 都要加入 `structure_id` 過濾
   - 所有新增資料的 API 都要自動帶入 `structure_id`
   - 修改 Controller 和 Model

3. **前端修改**
   - 在路由解析時，從當前 URL 或路由參數中取得 `structure_id`
   - 在呼叫 API 時帶入 `structure_id`

#### SQL 範例

```sql
-- 1. 在 sys_structure 表中加入 url 欄位（可選）
ALTER TABLE `sys_structure` 
ADD COLUMN `url` VARCHAR(255) NULL COMMENT '自訂 URL（可選，如果為空則使用模組的 name）' 
AFTER `module_id`;

-- 2. 在 app_contact 表中加入 structure_id
ALTER TABLE `app_contact` 
ADD COLUMN `structure_id` BIGINT(20) UNSIGNED NULL COMMENT '系統架構 ID' 
AFTER `id`;

-- 3. 在 app_news 表中加入 structure_id
ALTER TABLE `app_news` 
ADD COLUMN `structure_id` BIGINT(20) UNSIGNED NULL COMMENT '系統架構 ID' 
AFTER `id`;

-- 4. 在 app_about 表中加入 structure_id
ALTER TABLE `app_about` 
ADD COLUMN `structure_id` BIGINT(20) UNSIGNED NULL COMMENT '系統架構 ID' 
AFTER `id`;

-- 5. 建立索引以提升查詢效能
ALTER TABLE `app_contact` ADD INDEX `idx_structure_id` (`structure_id`);
ALTER TABLE `app_news` ADD INDEX `idx_structure_id` (`structure_id`);
ALTER TABLE `app_about` ADD INDEX `idx_structure_id` (`structure_id`);
```

---

### 方案二：在系統架構中加入自訂 URL

**優點**：
- 可以讓不同的單元使用不同的 URL
- 不需要修改資料表結構

**缺點**：
- URL 不見得是 Nuxt pages 有的（您提到的問題）
- 需要處理動態路由或 404 的情況

#### 實作步驟

1. **資料庫修改**
   - 在 `sys_structure` 表中加入 `url` 欄位

2. **路由解析邏輯**
   - 如果設定了自訂 URL，就使用自訂 URL
   - 否則使用模組的 name
   - 需要處理動態路由（例如 `/contact/[id]`）

3. **前端路由處理**
   - 使用 Nuxt 的動態路由或 catch-all 路由
   - 根據 URL 找到對應的 `structure_id`，然後載入對應的模組頁面

---

### 方案三：混合方案（推薦）

結合方案一和方案二的優點：

1. **在 `sys_structure` 表中加入 `url` 欄位**（可選）
   - 如果設定了自訂 URL，就使用自訂 URL
   - 否則使用模組的 name

2. **在每個模組對應的資料表中加入 `structure_id` 欄位**
   - 用來區分不同單元使用同一個模組時的資料

3. **URL 解析邏輯**
   ```typescript
   // 偽代碼
   function resolveUrl(structure: SysStructure, module: SysModule): string {
     return structure.url || `/${module.name}`;
   }
   ```

4. **資料查詢邏輯**
   ```php
   // 偽代碼
   function getData($structureId, $moduleId) {
     return Model::where('structure_id', $structureId)
                 ->where('module_id', $moduleId)
                 ->get();
   }
   ```

---

## 建議實作順序

### 階段一：資料庫結構調整
1. 在 `sys_structure` 表中加入 `url` 欄位
2. 在每個模組對應的資料表中加入 `structure_id` 欄位
3. 建立索引

### 階段二：後端 API 調整
1. 修改 Model，加入 `structure_id` 欄位到 `allowedFields`
2. 修改 Controller，在查詢時加入 `structure_id` 過濾
3. 修改 Controller，在新增時自動帶入 `structure_id`
4. 更新 API 文件

### 階段三：前端調整
1. 修改路由解析邏輯，支援自訂 URL
2. 修改 API 呼叫，帶入 `structure_id`
3. 更新相關頁面元件

### 階段四：資料遷移
1. 為現有資料設定預設的 `structure_id`
2. 驗證資料完整性

---

## 技術考量

### 1. URL 解析
如果使用自訂 URL，需要考慮：
- Nuxt 動態路由：`/contact/[id]` → `/custom-contact/[id]`
- Catch-all 路由：`/[...slug].vue` 來處理所有未定義的路由
- 路由守衛：根據 URL 找到對應的 `structure_id`

### 2. 向後兼容
- 如果 `structure_id` 為 NULL，表示是舊資料，可以顯示所有資料或提示需要設定
- 如果 `url` 為 NULL，使用模組的 name 作為 URL

### 3. 效能考量
- 在 `structure_id` 上建立索引
- 考慮是否需要快取結構資料

### 4. 資料完整性
- 考慮外鍵約束（如果需要）
- 考慮刪除單元時的資料處理（CASCADE 或 SET NULL）

---

## 範例：修改 AppContactController

```php
// 修改前
public function get()
{
    $status = $this->request->getGet('status');
    $query = $this->appContactModel->orderBy('created_at', 'DESC');
    
    if ($status !== null) {
        $query->where('status', (int)$status);
    }
    
    $contacts = $query->findAll();
    // ...
}

// 修改後
public function get()
{
    $status = $this->request->getGet('status');
    $structureId = $this->request->getGet('structure_id'); // 新增
    
    $query = $this->appContactModel->orderBy('created_at', 'DESC');
    
    // 必須要有 structure_id
    if ($structureId === null) {
        return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)->setJSON([
            'success' => false,
            'message' => '缺少 structure_id 參數',
        ]);
    }
    
    $query->where('structure_id', (int)$structureId); // 新增
    
    if ($status !== null) {
        $query->where('status', (int)$status);
    }
    
    $contacts = $query->findAll();
    // ...
}
```

---

## 總結

**推薦採用混合方案**，因為：
1. ✅ 可以解決資料區分問題（透過 `structure_id`）
2. ✅ 可以支援自訂 URL（透過 `url` 欄位）
3. ✅ 向後兼容（如果欄位為 NULL，使用預設行為）
4. ✅ 彈性高（可以同時使用自訂 URL 和資料區分）

這樣可以讓系統更靈活，同時解決您提到的問題。
