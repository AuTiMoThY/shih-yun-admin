# 表單預覽功能說明文件

## 概述

表單預覽功能提供了一個側邊欄預覽機制，讓使用者在新增或編輯內容時，可以即時查看最終的顯示效果。此功能設計為可重複使用的 Composable，可在 News、Case、About 等不同模組中使用。

## 功能特點

- ✅ **即時預覽**：表單數據變更時自動更新預覽內容
- ✅ **圖片處理**：自動處理臨時圖片 ID，顯示預覽圖
- ✅ **模組化設計**：可輕鬆整合到不同模組
- ✅ **響應式設計**：側邊欄可調整寬度，適配不同螢幕
- ✅ **類型安全**：完整的 TypeScript 類型定義

## 檔案結構

```
admin/app/
├── composables/
│   └── useFormPreview.ts      # 預覽功能 Composable
└── components/
    └── FormPreview.vue        # 預覽內容渲染組件
```

## 使用方式

### 1. 基本使用（News 模組範例）

```vue
<script setup lang="ts">
import { useFormPreview } from "~/composables/useFormPreview";
import FormPreview from "~/components/FormPreview.vue";

// 初始化預覽功能
const preview = useFormPreview({
    defaultOpen: false,
    width: "500px",
    title: "最新消息預覽"
});

// 監聽表單數據變化
watch(
    () => form,
    () => {
        preview.updatePreview(
            {
                title: form.title,
                content: form.content,
                show_date: form.show_date,
                cover: form.cover,
                slide: form.slide
            },
            {
                cover: {
                    preview: coverUpload.preview.value,
                    formValue: coverUpload.formValue.value
                },
                slide: {
                    previews: slideUpload.previews.value,
                    formValue: slideUpload.formValue.value
                }
            }
        );
    },
    { deep: true, immediate: true }
);
</script>

<template>
    <div>
        <!-- 表單內容 -->
        <UForm>
            <!-- ... -->
        </UForm>
        
        <!-- 預覽按鈕 -->
        <UButton @click="preview.toggle()">預覽</UButton>
        
        <!-- 側邊欄預覽 -->
        <USlideover v-model="preview.isOpen.value" :ui="{ width: preview.width }">
            <template #header>
                <h2>{{ preview.title }}</h2>
            </template>
            <FormPreview
                :data="preview.previewData.value"
                :cover-url="preview.getCoverUrl()"
                :slide-urls="preview.getSlideUrls()"
                module-type="news" />
        </USlideover>
    </div>
</template>
```

### 2. Case 模組使用範例

```vue
<script setup lang="ts">
const preview = useFormPreview({
    defaultOpen: false,
    width: "600px",
    title: "建案預覽"
});

watch(
    () => ({
        title: form.title,
        year: form.year,
        s_text: form.s_text,
        cover: form.cover,
        slide: form.slide,
        content: form.content
    }),
    () => {
        preview.updatePreview(
            {
                title: form.title,
                year: form.year,
                s_text: form.s_text,
                cover: form.cover,
                slide: form.slide,
                content: form.content
            },
            {
                cover: {
                    preview: coverUpload.preview.value,
                    formValue: coverUpload.formValue.value
                },
                slide: {
                    previews: slideUpload.previews.value,
                    formValue: slideUpload.formValue.value
                }
            }
        );
    },
    { deep: true, immediate: true }
);
</script>

<template>
    <FormPreview
        :data="preview.previewData.value"
        :cover-url="preview.getCoverUrl()"
        :slide-urls="preview.getSlideUrls()"
        module-type="case" />
</template>
```

## API 參考

### useFormPreview(options?)

#### 參數

| 參數 | 類型 | 預設值 | 說明 |
|------|------|--------|------|
| `defaultOpen` | `boolean` | `false` | 預設是否開啟預覽 |
| `width` | `string` | `"500px"` | 側邊欄寬度 |
| `title` | `string` | `"預覽"` | 預覽標題 |
| `customRenderer` | `function` | `undefined` | 自訂渲染函數（可選） |

#### 返回值

```typescript
{
    // 狀態（只讀）
    isOpen: Readonly<Ref<boolean>>;
    previewData: Readonly<Ref<PreviewData>>;
    
    // 方法
    open(): void;              // 開啟預覽
    close(): void;             // 關閉預覽
    toggle(): void;            // 切換預覽開關
    updatePreview(data, imageSources?): void;  // 更新預覽數據
    getCoverUrl(): string;     // 取得有效的封面圖 URL
    getSlideUrls(): string[];  // 取得有效的輪播圖 URL 陣列
    reset(): void;             // 重置預覽數據
    
    // 配置
    width: string;
    title: string;
    customRenderer?: function;
}
```

### FormPreview 組件

#### Props

| 參數 | 類型 | 必填 | 說明 |
|------|------|------|------|
| `data` | `PreviewData` | ✅ | 預覽數據 |
| `coverUrl` | `string` | ❌ | 封面圖 URL（會覆蓋 data.cover） |
| `slideUrls` | `string[]` | ❌ | 輪播圖 URL 陣列（會覆蓋 data.slide） |
| `title` | `string` | ❌ | 預覽標題 |
| `moduleType` | `"news" \| "case" \| "about" \| "custom"` | ❌ | 模組類型，用於決定渲染方式 |

#### PreviewData 類型

```typescript
interface PreviewData {
    title?: string;           // 標題
    content?: string;         // HTML 內容
    cover?: string;           // 封面圖 URL
    slide?: string[];         // 輪播圖 URL 陣列
    show_date?: string;       // 顯示日期（News 模組）
    year?: number;            // 年份（Case 模組）
    s_text?: string;          // 小字（Case 模組）
    [key: string]: any;       // 其他自訂欄位
}
```

## 圖片處理機制

### 臨時圖片 ID

當使用者選擇圖片但尚未上傳時，系統會生成臨時 ID（格式：`temp_${timestamp}_${random}`）。預覽功能會自動處理這些臨時 ID：

1. **封面圖**：優先使用 `coverUpload.preview.value`（本地預覽），如果沒有則使用 `form.cover`
2. **輪播圖**：優先使用 `slideUpload.previews.value`（本地預覽陣列），如果沒有則使用 `form.slide`

### 圖片來源優先順序

```typescript
// 封面圖
coverUrl = imageSources.cover.preview 
        || imageSources.cover.formValue 
        || data.cover

// 輪播圖
slideUrls = imageSources.slide.previews 
         || imageSources.slide.formValue 
         || data.slide
```

## 模組類型說明

### news（最新消息）

- 顯示標題、日期
- 顯示封面圖
- 顯示輪播圖（網格佈局）
- 渲染 HTML 內容

### case（建案）

- 顯示標題、年份、小字等基本資訊
- 顯示封面圖
- 顯示輪播圖（網格佈局）
- 渲染內容區塊（CutSection）

### about（關於我們）

- 顯示標題
- 渲染內容區塊（CutSection）

### custom（自訂）

- 僅渲染 HTML 內容
- 適合簡單的內容預覽

## 自訂渲染

如果需要自訂預覽內容的渲染方式，可以使用 `customRenderer` 選項：

```typescript
const preview = useFormPreview({
    customRenderer: (data: PreviewData) => {
        // 自訂渲染邏輯
        return `<div>${data.title}</div>`;
    }
});
```

## 最佳實踐

### 1. 即時更新

建議使用 `watch` 監聽表單數據變化，並設定 `deep: true` 和 `immediate: true`：

```typescript
watch(
    () => form,
    () => {
        preview.updatePreview(formData, imageSources);
    },
    { deep: true, immediate: true }
);
```

### 2. 圖片來源處理

確保傳入正確的圖片來源，以便處理臨時圖片：

```typescript
preview.updatePreview(
    formData,
    {
        cover: {
            preview: coverUpload.preview.value,
            formValue: coverUpload.formValue.value
        },
        slide: {
            previews: slideUpload.previews.value,
            formValue: slideUpload.formValue.value
        }
    }
);
```

### 3. 響應式寬度

根據內容複雜度調整側邊欄寬度：

- 簡單內容：`400px` - `500px`
- 複雜內容：`600px` - `800px`
- 全寬：`100vw`（不建議，會遮擋表單）

### 4. 效能優化

如果表單數據很大，可以考慮使用 `debounce` 延遲更新：

```typescript
import { debounce } from "lodash-es";

const updatePreviewDebounced = debounce((data, sources) => {
    preview.updatePreview(data, sources);
}, 300);

watch(() => form, () => {
    updatePreviewDebounced(formData, imageSources);
}, { deep: true });
```

## 疑難排解

### 預覽內容不更新

1. 確認 `watch` 有設定 `deep: true`
2. 確認 `updatePreview` 有正確呼叫
3. 檢查瀏覽器控制台是否有錯誤

### 圖片不顯示

1. 確認圖片 URL 是否正確
2. 檢查臨時圖片是否有對應的預覽 URL
3. 確認 `imageSources` 參數是否正確傳入

### 側邊欄無法開啟

1. 確認 `v-model` 綁定到 `preview.isOpen.value`
2. 檢查 `USlideover` 組件是否正確導入
3. 確認沒有 CSS 衝突

## 擴展指南

### 新增模組類型

1. 在 `FormPreview.vue` 的 `moduleType` 中添加新類型
2. 在 template 中添加對應的渲染邏輯
3. 更新類型定義

### 自訂樣式

可以透過覆寫 CSS 類別來自訂預覽樣式：

```vue
<style scoped>
.form-preview {
    /* 自訂樣式 */
}
</style>
```

## 範例專案

完整的實作範例請參考：
- `admin/app/components/App/News/FormPage.vue` - News 模組實作
- `admin/app/components/App/Case/FormPage.vue` - Case 模組實作（可參考此文件實作）

## 更新日誌

### v1.0.0 (2025-01-XX)
- ✅ 初始版本
- ✅ 支援 News、Case、About 模組
- ✅ 即時預覽功能
- ✅ 圖片處理機制
- ✅ TypeScript 類型定義

## 相關文件

- [圖片上傳功能](../image-upload-feature.md)
- [表單驗證指南](../form-validation-guide.md)

