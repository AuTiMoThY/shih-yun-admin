# Pinia 整合指南

## 方案六：使用 Pinia Store 管理結構狀態

### 可行性分析

✅ **可行**，但需要額外安裝與配置。

### 優點

1. **集中式狀態管理**：所有狀態邏輯集中在 Store 中，易於追蹤和除錯
2. **DevTools 支援**：可以使用 Vue DevTools 查看狀態變化
3. **自動同步**：多個組件共享同一個 Store，自動同步更新
4. **結構清晰**：使用 actions、getters 等，程式碼結構更清晰
5. **可擴展性**：適合大型專案，可以輕鬆擴展其他功能

### 缺點

1. **需要額外安裝**：需要安裝 `@pinia/nuxt` 模組
2. **增加複雜度**：對於簡單場景可能過度設計
3. **學習曲線**：團隊需要了解 Pinia 的使用方式

---

## 安裝步驟

### 1. 安裝 Pinia

```bash
cd admin
pnpm add @pinia/nuxt
```

### 2. 配置 Nuxt

在 `nuxt.config.ts` 的 `modules` 陣列中加入 `@pinia/nuxt`：

```typescript
export default defineNuxtConfig({
    modules: [
      "@nuxt/eslint",
      "@nuxt/ui",
      "@nuxt/image",
      "@vueuse/nuxt",
      "nuxt-tiptap-editor",
      "@pinia/nuxt", // 新增這一行
    ],
    // ... 其他配置
});
```

### 3. 建立 Store

Store 檔案已建立於 `admin/stores/structure.ts`。

**注意：** 在 Nuxt 3 中，`ref`、`computed`、`reactive`、`$fetch`、`useRuntimeConfig`、`useToast` 等都是自動導入的，無需手動 import。只有在 TypeScript 類型檢查時可能需要明確的類型導入。

---

## 使用方式

### 在 composable 中（向後兼容）

可以建立一個包裝函數來保持向後兼容：

```typescript
// admin/app/composables/useStructure.ts
import { useStructureStore } from "~/stores/structure";

export const useStructure = () => {
    const store = useStructureStore();
    
    // 返回與原本相同的 API，保持向後兼容
    return {
        data: store.data,
        asideData: store.asideData,
        loading: store.loading,
        fetchData: store.fetchData,
        fetchDataForAside: store.fetchDataForAside,
        updateSortOrder: store.updateSortOrder,
        deleteLevel: store.deleteLevel,
        form: store.form,
        errors: store.errors,
        submitError: store.submitError,
        clearError: store.clearError,
        resetForm: store.resetForm,
        loadFormData: store.loadFormData,
        addLevel: store.addLevel,
        updateLevel: store.updateLevel,
        modalOpen: store.modalOpen
    };
};
```

### 直接使用 Store

```vue
<script setup lang="ts">
// 直接使用 Store
const structureStore = useStructureStore();

// 使用 computed 自動響應
const rootLevels = computed(() => structureStore.rootLevels);

// 調用 actions
await structureStore.fetchData();
await structureStore.addLevel(event, options);
</script>
```

### 在 default.vue 中使用

```vue
<script setup lang="ts">
const structureStore = useStructureStore();

// 自動響應式，當 asideData 更新時，links 會自動重新計算
const links = computed(() => {
    const structureMenuItems = buildStructureMenu(open);
    // ... 其他邏輯
});

onMounted(async () => {
    if (!structureStore.asideData.length) {
        await structureStore.fetchDataForAside();
    }
});
</script>
```

---

## 與目前方案的比較

### 目前方案（useState + composable）

✅ **優點：**
- 無需額外依賴
- Nuxt 內建支援
- 簡單直接
- 已實作完成

❌ **缺點：**
- 需要在多處手動同步
- 狀態分散在多個 composable 中

### Pinia 方案

✅ **優點：**
- 集中式狀態管理
- 自動同步（多個組件共享同一狀態）
- DevTools 支援
- 適合大型專案

❌ **缺點：**
- 需要額外安裝
- 增加專案複雜度
- 需要重構現有程式碼

---

## 建議

### 適合使用 Pinia 的情況：

1. **大型專案**：有多個模組需要共享狀態
2. **複雜狀態邏輯**：需要複雜的狀態計算和同步
3. **團隊協作**：需要清晰的狀態管理規範
4. **長期維護**：專案會持續擴展

### 適合繼續使用目前方案的情況：

1. **中小型專案**：狀態管理需求簡單
2. **已穩定運作**：目前方案運作良好
3. **快速開發**：不想增加額外複雜度
4. **團隊熟悉度**：團隊更熟悉 Nuxt 的 useState

---

## 遷移步驟（如果決定使用 Pinia）

1. **安裝依賴**：`pnpm add @pinia/nuxt`
2. **配置 Nuxt**：在 `nuxt.config.ts` 加入模組
3. **建立 Store**：使用已建立的 `stores/structure.ts`
4. **更新 composable**：修改 `useStructure.ts` 使用 Store
5. **測試驗證**：確保所有功能正常運作
6. **逐步遷移**：可以保留 composable 作為包裝層，逐步遷移

---

## 結論

**目前方案（方案二）已經足夠**，因為：
- ✅ 已實作完成且運作良好
- ✅ 自動同步選單更新
- ✅ 無需額外依賴
- ✅ 程式碼簡潔

**如果未來專案規模擴大**，可以考慮遷移到 Pinia，但現在不是必須的。

