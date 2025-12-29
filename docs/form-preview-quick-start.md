# 表單預覽功能 - 快速開始指南

## 5 分鐘快速整合

### 步驟 1：導入 Composable 和組件

```vue
<script setup lang="ts">
import { useFormPreview } from "~/composables/useFormPreview";
import FormPreview from "~/components/FormPreview.vue";
</script>
```

### 步驟 2：初始化預覽功能

```vue
<script setup lang="ts">
// 在現有的 script 中添加
const preview = useFormPreview({
    defaultOpen: false,
    width: "500px",
    title: "內容預覽"
});
</script>
```

### 步驟 3：監聽表單數據變化

```vue
<script setup lang="ts">
// 監聽表單數據，自動更新預覽
watch(
    () => ({
        title: form.title,
        content: form.content,
        cover: form.cover,
        slide: form.slide
    }),
    () => {
        preview.updatePreview(
            {
                title: form.title,
                content: form.content,
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
```

### 步驟 4：添加預覽按鈕

```vue
<template>
    <UCard>
        <template #header>
            <div class="flex items-center justify-between">
                <h3>編輯</h3>
                <UButton
                    icon="i-lucide-eye"
                    label="預覽"
                    @click="preview.toggle()" />
            </div>
        </template>
        <!-- 表單內容 -->
    </UCard>
</template>
```

### 步驟 5：添加側邊欄預覽

```vue
<template>
    <!-- 表單內容 -->
    
    <!-- 側邊欄預覽 -->
    <USlideover v-model="preview.isOpen.value">
        <template #header>
            <div class="flex items-center justify-between">
                <h2>{{ preview.title }}</h2>
                <UButton
                    icon="i-lucide-x"
                    @click="preview.close()" />
            </div>
        </template>
        <div class="h-full overflow-y-auto">
            <FormPreview
                :data="{
                    ...preview.previewData.value,
                    slide: preview.previewData.value.slide
                        ? [...preview.previewData.value.slide]
                        : undefined
                }"
                :cover-url="preview.getCoverUrl()"
                :slide-urls="preview.getSlideUrls()"
                module-type="news" />
        </div>
    </USlideover>
</template>
```

## 完整範例（News 模組）

```vue
<script setup lang="ts">
import { useFormPreview } from "~/composables/useFormPreview";
import FormPreview from "~/components/FormPreview.vue";

const { form, coverUpload, slideUpload } = useAppNews();

// 初始化預覽
const preview = useFormPreview({
    defaultOpen: false,
    width: "500px",
    title: "最新消息預覽"
});

// 監聽表單變化
watch(
    () => ({
        title: form.title,
        content: form.content,
        show_date: form.show_date,
        cover: form.cover,
        slide: form.slide
    }),
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
    <UForm>
        <UCard>
            <template #header>
                <div class="flex items-center justify-between">
                    <h3>編輯</h3>
                    <UButton
                        icon="i-lucide-eye"
                        label="預覽"
                        @click="preview.toggle()" />
                </div>
            </template>
            <!-- 表單欄位 -->
        </UCard>
    </UForm>
    
    <!-- 側邊欄預覽 -->
    <USlideover v-model="preview.isOpen.value">
        <template #header>
            <div class="flex items-center justify-between">
                <h2>{{ preview.title }}</h2>
                <UButton icon="i-lucide-x" @click="preview.close()" />
            </div>
        </template>
        <div class="h-full overflow-y-auto">
            <FormPreview
                :data="{
                    ...preview.previewData.value,
                    slide: preview.previewData.value.slide
                        ? [...preview.previewData.value.slide]
                        : undefined
                }"
                :cover-url="preview.getCoverUrl()"
                :slide-urls="preview.getSlideUrls()"
                module-type="news" />
        </div>
    </USlideover>
</template>
```

## 不同模組的 moduleType

- `news` - 最新消息模組
- `case` - 建案模組
- `about` - 關於我們模組
- `custom` - 自訂模組（僅渲染 HTML）

## 常見問題

### Q: 預覽內容不更新？
A: 確認 `watch` 有設定 `deep: true` 和 `immediate: true`

### Q: 圖片不顯示？
A: 確認有傳入 `imageSources` 參數，包含 `preview` 和 `formValue`

### Q: 如何調整側邊欄寬度？
A: 在 `useFormPreview` 的 `width` 選項中設定，例如 `"600px"`

## 下一步

- 查看 [完整說明文件](./form-preview-feature.md)
- 參考 [News FormPage 實作範例](../admin/app/components/App/News/FormPage.vue)

