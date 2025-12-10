# 系統架構樹狀表格說明

## 後端資料來源
- API 路由：`GET /api/structure/get?tree=1`
- Controller：`StructureController::getLevels()` 會在收到 `tree=1` 時呼叫 `StructureModel::getAllLevels()`。
- Model：`getAllLevels()` 取得所有層級後，使用 `buildTree()` 將平面資料轉成樹狀結構（填入 `children` 陣列），然後回傳給前端。
- 因此前端取得的 `data` 已經是樹狀結構，`TreeTableRow` 只需要遞迴渲染，不再自行組裝。

## 前端渲染流程
檔案：`admin/app/pages/system/structure.vue`
- `fetchData()`：呼叫上述 API 取得樹狀資料並存入 `data`。
- `StructureTreeTableRow`：遞迴元件，負責展開/收合與縮排。
- `StructureAddSubLevel`：新增子層級的 modal，點擊「加入子層級」時帶入父層級 id/name。

## TreeTableRow 運作方式
檔案：`admin/app/components/Structure/TreeTableRow.vue`
- Props：`level`(當前節點)、`depth`(層級，用於縮排)、`onEdit`、`onAddSub`。
- 展開/收合：若 `level.children` 有資料，顯示箭頭按鈕，控制 `isExpanded`。
- 縮排：`padding-left` = `depth * 24px`，層級越深縮排越多。
- 遞迴：在 template 下方再次渲染 `<TreeTableRow ... :level="child" :depth="depth+1">` 以顯示子層級。
- 狀態顯示：沿用既有的 badge 樣式，顯示前台/後台顯示與是否上線。
- 操作：保留「編輯」與「加入子層級」按鈕，事件由父層控制。

## 為何沒有在前端自行呼叫 buildTree？
- 後端已在 `StructureModel::getAllLevels()` 中執行 `buildTree()`，並在 `getLevels()` 收到 `tree=1` 時回傳樹狀資料。
- 前端只需消費已有的樹狀 JSON 並做遞迴渲染，不需重複轉換，避免額外計算與一致性問題。

## 常見維護點
- 若需要平面列表，呼叫 `/api/structure/get`（不帶 `tree=1`）。
- 若要調整縮排，修改 `TreeTableRow.vue` 的 `indentWidth`。
- 若要預設全部收合，可將 `isExpanded` 初始值改為 `false`，或只在第一層預設展開。
- 新增排序欄位時，後端可調整 `sort_order`，前端不須改動遞迴邏輯。


---

## 同層級可拖曳排序（使用 @vueuse/integrations/useSortable）

### 需求與做法
- 目標：同一層級的節點，可拖曳改變順序，並同步更新後端的 `sort_order`。
- 工具：`useSortable`（封裝 SortableJS）。
- 策略：每個「同層級容器」掛一個 `useSortable`，`onEnd` 時先在前端 array 重新排序，再呼叫 API 將最新順序寫回。

### API 邏輯（後端已存在）
- 使用 `/api/structure/update`，傳入 `{ id, sort_order }`。
- 建議批次更新：根據當前 siblings 順序，依序送出 update。

### 前端實作步驟摘要
1) 安裝（若未安裝）  
   `pnpm add @vueuse/integrations sortablejs`

2) 頂層頁面 (`admin/app/pages/system/structure.vue`)  
   - 匯入：`import { useSortable } from "@vueuse/integrations/useSortable";`  
   - 在 `<tbody>`（根層級容器）加 `ref="rootBodyRef"`。  
   - 呼叫 `useSortable(rootBodyRef, { handle: ".drag-handle", animation: 150, onEnd })`。  
   - `onEnd` 中：依 `oldIndex/newIndex` 重新排列 `data.value`，然後呼叫 `persistOrder(null, data.value)`。
   - `persistOrder(parentId, siblings)`：將 siblings 依序送 `/api/structure/update`，body `{ id, sort_order: index }`。

3) 子層級（遞迴）  
   - 在 `TreeTableRow.vue` 中為「子節點群」放一個容器，掛上 `useSortable`。  
   - 只允許拖曳 `.drag-handle`；`onEnd` 時重排 `level.children`，再呼叫 `onReorder?.(level.id, level.children)`。

4) 拖曳把手 UI  
   - 在每個 row 名稱前放一個 icon：  
     ```html
     <UIcon name="i-lucide-grip-vertical" class="w-4 h-4 cursor-grab drag-handle" />
     ```
   - `handle` 選項對應 `.drag-handle`，避免誤拖。

### 範例程式片段（根層級）
```vue
<script setup lang="ts">
import { useSortable } from "@vueuse/integrations/useSortable";
const rootBodyRef = ref<HTMLElement | null>(null);

const persistOrder = async (parentId: number | null, siblings: any[]) => {
  try {
    const updates = siblings.map((item, index) => ({ id: item.id, sort_order: index }));
    await Promise.all(
      updates.map((u) =>
        $fetch(`${apiBase}/api/structure/update`, {
          method: "POST",
          body: u,
          headers: { "Content-Type": "application/json" },
          credentials: "include"
        })
      )
    );
    toast.add({ title: "排序已更新", color: "success" });
  } catch (error: any) {
    toast.add({ title: "更新排序失敗，請稍後再試", color: "error" });
    fetchData(); // 確保畫面與後端同步
  }
};

useSortable(rootBodyRef, {
  handle: ".drag-handle",
  animation: 150,
  onEnd: async (evt) => {
    const list = data.value;
    if (!list?.length) return;
    const from = evt.oldIndex ?? 0;
    const to = evt.newIndex ?? 0;
    const [moved] = list.splice(from, 1);
    list.splice(to, 0, moved);
    await persistOrder(null, list);
  }
});
</script>

<tbody ref="rootBodyRef">
  <StructureTreeTableRow
    v-for="level in data"
    :key="level.id"
    :level="level"
    :depth="0"
    :on-edit="editLevel"
    :on-add-sub="addSubLevel"
    :on-reorder="persistOrder"
    @refresh="fetchData" />
</tbody>
```

### 範例程式片段（子層級）
```vue
<!-- TreeTableRow.vue -->
<script setup lang="ts">
import { useSortable } from "@vueuse/integrations/useSortable";
const childrenRef = ref<HTMLElement | null>(null);

const setupSortable = () => {
  if (!childrenRef.value || !hasChildren.value) return;
  useSortable(childrenRef, {
    handle: ".drag-handle",
    animation: 150,
    onEnd: async (evt) => {
      const list = props.level.children || [];
      const from = evt.oldIndex ?? 0;
      const to = evt.newIndex ?? 0;
      const [moved] = list.splice(from, 1);
      list.splice(to, 0, moved);
      await props.onReorder?.(props.level.id ?? null, list);
    }
  });
};

onMounted(setupSortable);
watch(hasChildren, setupSortable);
</script>

<tr>
  <td>
    <UIcon name="i-lucide-grip-vertical" class="w-4 h-4 cursor-grab drag-handle" />
    {{ level.name }}
  </td>
  ...
</tr>

<template v-if="hasChildren">
  <tbody ref="childrenRef" style="display: contents">
    <TreeTableRow
      v-for="child in level.children"
      :key="child.id"
      :level="child"
      :depth="currentDepth + 1"
      :on-edit="onEdit"
      :on-add-sub="onAddSub"
      :on-reorder="onReorder"
      @refresh="emit('refresh')" />
  </tbody>
</template>
```

> 註：`display: contents` 用於讓子層 tbody 不破壞表格佈局，同時提供容器給 `useSortable`。若瀏覽器不支援，建議改為 div-based 佈局再套用同樣的拖曳邏輯。

### 使用與測試
- 在同層級拖曳把手重新排序，確認前端順序改變且提示成功。
- 重新整理頁面，順序應與拖曳後一致，代表後端 `sort_order` 已更新。


---


以下為樹狀表格的運作說明與文件產出，並回答 buildTree 的疑問。

### 樹狀表格如何運作
- 前端載入：`fetchData()` 攜帶 `tree=1` 呼叫 `/api/structure/get`，取得後端已整理好的樹狀資料（含 `children`）。
```27:43:admin/app/pages/system/structure.vue
const fetchData = async () => {
    loading.value = true;
    const res = await $fetch<{
        success: boolean;
        data: any[];
        message?: string;
    }>(`${apiBase}/api/structure/get?tree=1`, { method: "GET" });
    if (res?.success) {
        data.value = res.data;
    } else {
        console.error(res.message);
        toast.add({ title: res.message, color: "error" });
    }
    loading.value = false;
};
```
- 前端渲染：`StructureTreeTableRow` 遞迴渲染 `level.children`，並用 `depth * 24px` 做縮排；有子節點時顯示展開/收合按鈕。
```40:104:admin/app/components/Structure/TreeTableRow.vue
<tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
    <td ...>
        <div ... :style="{ paddingLeft: `${currentDepth * indentWidth}px` }">
            <button v-if="hasChildren" @click="toggleExpand">
                <UIcon :name="isExpanded ? 'i-lucide-chevron-down' : 'i-lucide-chevron-right'" />
            </button>
            <span v-else class="w-5"></span>
            <span class="font-medium">{{ level.name }}</span>
        </div>
    </td>
    ...
</tr>
<template v-if="hasChildren && isExpanded">
    <TreeTableRow
        v-for="child in level.children"
        :key="child.id"
        :level="child"
        :depth="currentDepth + 1"
        :on-edit="onEdit"
        :on-add-sub="onAddSub"
        @refresh="emit('refresh')" />
</template>
```
- 新增子層級：點「加入子層級」會帶入當前節點的 `id/name` 給 `StructureAddSubLevel` 的 modal，送出後再 `fetchData()` 重新取得樹狀資料。

### 為何沒有使用到 `StructureModel::buildTree`？
- 其實有使用：當後端收到 `tree=1` 時，`StructureController::getLevels()` 會呼叫 `StructureModel::getAllLevels()`，而 `getAllLevels()` 內部就執行 `buildTree()` 先把平面資料轉成樹狀結構再回傳。前端直接消費這個樹狀 JSON，避免重複轉換。

### 說明文件
- 已新增說明文件：`admin/docs/structure-tree.md`（包含後端資料流程、前端渲染方式、遞迴元件行為與維護要點）。

若需要預設全部收合或調整縮排，只要調整 `TreeTableRow.vue` 的 `isExpanded` 初始值或 `indentWidth` 即可。