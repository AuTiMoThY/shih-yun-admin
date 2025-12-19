# 架構方案比較：導向 vs 動態組件載入

## 方案一：目前做法（導向方式）

### 流程
```
URL (/custom-contact) 
  → [...slug]/index.vue 解析
  → navigateTo('/contact?structure_id=1')
  → pages/contact/index.vue 載入
  → 從 query 取得 structure_id
  → 呼叫 API 取得資料
```

### 優點
1. ✅ **簡單直接**：利用 Nuxt 的路由系統，每個頁面獨立
2. ✅ **易於維護**：頁面邏輯清晰，不需要額外的映射關係
3. ✅ **SEO 友好**：URL 會顯示實際的頁面路徑（如 `/contact`）
4. ✅ **開發體驗好**：可以直接訪問 `/contact` 進行開發和測試
5. ✅ **向後兼容**：保留原有的 `/about`, `/news`, `/contact` 路由

### 缺點
1. ❌ **URL 跳轉**：會有頁面重新載入，體驗不夠流暢
2. ❌ **URL 不真實**：自訂 URL（如 `/custom-contact`）最終還是跳轉到 `/contact`
3. ❌ **Query 參數依賴**：需要透過 query 參數傳遞 `structure_id`，URL 不夠乾淨
4. ❌ **無法真正支援自訂 URL**：雖然可以設定自訂 URL，但最終還是導向固定路徑

---

## 方案二：建議做法（動態組件載入）

### 流程
```
URL (/custom-contact) 
  → [...slug]/index.vue 解析
  → 取得 structure_id 和 module_name
  → 根據 module_name 動態載入對應的 component
  → 根據 structure_id 撈取資料
  → 渲染 component
```

### 優點
1. ✅ **真正支援自訂 URL**：URL 可以完全自訂，不需要跳轉
2. ✅ **無頁面跳轉**：體驗更流暢，沒有重新載入
3. ✅ **URL 乾淨**：不需要 query 參數，URL 就是實際的路徑
4. ✅ **邏輯清晰**：一個 URL 對應一個 structure，關係明確
5. ✅ **靈活性高**：可以動態載入不同的 component，易於擴展

### 缺點
1. ❌ **需要重構**：需要將現有頁面改成 components
2. ❌ **需要映射關係**：需要建立 module_name → component 的映射
3. ❌ **稍微複雜**：需要處理動態組件載入的邏輯
4. ❌ **開發體驗**：不能直接訪問 `/contact`，需要透過自訂 URL

---

## 詳細比較

### 1. URL 處理

**方案一（導向）：**
```typescript
// 自訂 URL: /custom-contact
// 實際訪問: /contact?structure_id=1
// URL 會改變，有跳轉
```

**方案二（動態組件）：**
```typescript
// 自訂 URL: /custom-contact
// 實際訪問: /custom-contact
// URL 不變，無跳轉
```

### 2. 資料載入

**方案一（導向）：**
```typescript
// 在 pages/contact/index.vue
const structureId = computed(() => {
    return route.query.structure_id ? Number(route.query.structure_id) : null;
});
await fetchData({ structure_id: structureId.value });
```

**方案二（動態組件）：**
```typescript
// 在 [...slug]/index.vue
const { structure_id, module_name } = resolvePath(route.path);
const Component = getComponentByModule(module_name);
// 直接傳入 structure_id 給 component
```

### 3. 組件結構

**方案一（導向）：**
```
pages/
  ├── about/index.vue (完整頁面)
  ├── news/index.vue (完整頁面)
  └── contact/index.vue (完整頁面)
```

**方案二（動態組件）：**
```
components/
  └── Module/
      ├── About.vue (組件)
      ├── News.vue (組件)
      └── Contact.vue (組件)

pages/
  └── [...slug]/index.vue (動態載入組件)
```

---

## 建議

### 推薦採用方案二（動態組件載入）

**理由：**
1. **符合需求**：真正支援自訂 URL，這是核心需求
2. **體驗更好**：無頁面跳轉，用戶體驗更流暢
3. **架構更清晰**：URL 和資料的對應關係更明確
4. **易於擴展**：未來新增模組更容易

### 實作建議

#### 1. 建立組件映射
```typescript
// composables/useModuleComponent.ts
const moduleComponentMap: Record<string, string> = {
    'about': 'ModuleAbout',
    'news': 'ModuleNews',
    'contact': 'ModuleContact'
};

export const getComponentByModule = (moduleName: string) => {
    return moduleComponentMap[moduleName] || null;
};
```

#### 2. 重構頁面為組件
- 將 `pages/about/index.vue` 的內容提取到 `components/Module/About.vue`
- 將 `pages/news/index.vue` 的內容提取到 `components/Module/News.vue`
- 將 `pages/contact/index.vue` 的內容提取到 `components/Module/Contact.vue`

#### 3. 實作動態載入
```vue
<!-- [...slug]/index.vue -->
<script setup lang="ts">
const { structure_id, module_name } = resolvePath(route.path);
const componentName = getComponentByModule(module_name);
</script>

<template>
  <component 
    :is="componentName" 
    :structure-id="structure_id" 
  />
</template>
```

#### 4. 保留原有路由（可選）
- 可以保留 `/about`, `/news`, `/contact` 作為快捷訪問
- 這些路由可以自動取得第一個對應的 structure_id

---

## 遷移步驟

1. **建立組件目錄結構**
   ```
   components/Module/
     ├── About.vue
     ├── News.vue
     └── Contact.vue
   ```

2. **提取頁面邏輯到組件**
   - 將頁面的 `<script setup>` 和 `<template>` 內容移到組件
   - 組件接收 `structureId` 作為 prop

3. **建立映射關係**
   - 建立 `useModuleComponent` composable
   - 定義 module_name → component 的映射

4. **實作動態載入**
   - 在 `[...slug]/index.vue` 中實作動態組件載入
   - 處理載入狀態和錯誤處理

5. **測試和驗證**
   - 測試自訂 URL 是否正常工作
   - 驗證資料是否正確過濾
   - 確認多個單元使用同一模組時資料是否正確區分

---

## 結論

**方案二（動態組件載入）更符合需求**，雖然需要一些重構工作，但能真正實現自訂 URL 的功能，並且提供更好的用戶體驗。建議採用此方案。
