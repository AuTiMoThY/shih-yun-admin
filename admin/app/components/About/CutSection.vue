<script setup lang="ts">
import type {
    FieldConfig,
    FieldType,
    CutSectionData
} from "~/types/CutSectionField";

const { buildId } = useAbout();
const { hasPermission, isSuperAdmin } = usePermission();

// 權限檢查
const canCreateField = computed(() => isSuperAdmin() || hasPermission('about.field.create'));
const canDeleteSection = computed(() => isSuperAdmin() || hasPermission('about.section.delete'));
const canSortSection = computed(() => isSuperAdmin() || hasPermission('about.section.sort'));

const props = defineProps<{
    index: string | number;
    data?: CutSectionData;
    canMoveUp?: boolean;
    canMoveDown?: boolean;
}>();

const emit = defineEmits<{
    (e: "update", data: CutSectionData): void;
    (e: "delete", sectionId: string): void;
    (e: "delete-request", sectionId: string, label: string): void;
    (e: "move-up", sectionId: string): void;
    (e: "move-down", sectionId: string): void;
}>();

// 區塊資料
const sectionData = ref<CutSectionData>({
    id: props.data?.id || `section-${buildId.value}`,
    index:
        typeof props.index === "number"
            ? props.index
            : parseInt(props.index) || 1,
    fields: props.data?.fields || []
});

// 是否顯示欄位選擇器
const showFieldSelector = ref(false);

// 發送更新事件
const emitUpdate = () => {
    emit("update", { ...sectionData.value });
};

const handleAddField = (event: Event) => {
    event.preventDefault();
    event.stopPropagation();
    showFieldSelector.value = !showFieldSelector.value;
};

const handleDeleteSection = (event: Event) => {
    event.preventDefault();
    event.stopPropagation();
    emit(
        "delete-request",
        sectionData.value.id,
        `第${sectionData.value.index}卡內容編輯`
    );
};

const handleMoveSection = (event: Event, direction: "up" | "down") => {
    event.preventDefault();
    event.stopPropagation();
    if (direction === "up") {
        emit("move-up", sectionData.value.id);
    } else {
        emit("move-down", sectionData.value.id);
    }
    emitUpdate();
};

// 新增欄位
const addField = (type: FieldType) => {
    console.log("新增欄位:", type);
    // 每次新增欄位都產生新的唯一 id，避免重複導致更新錯位
    const uniqueId = `field-${Date.now()}-${Math.random()
        .toString(36)
        .slice(2, 8)}`;
    const newField: FieldConfig = {
        id: uniqueId,
        type,
        label: getDefaultLabel(type),
        value: ""
    };
    sectionData.value.fields.push(newField);
    showFieldSelector.value = false;
    emitUpdate();
};

// 取得預設標題
const getDefaultLabel = (type: FieldType): string => {
    const labels: Record<FieldType, string> = {
        title: "標題",
        subtitle: "副標題",
        desktop_image: "電腦版圖片",
        mobile_image: "手機版圖片",
        content: "內文"
    };
    return labels[type];
};

// 更新欄位
const updateField = (updatedField: FieldConfig) => {
    const index = sectionData.value.fields.findIndex(
        (f) => f.id === updatedField.id
    );
    if (index !== -1) {
        sectionData.value.fields[index] = { ...updatedField };
        emitUpdate();
    }
};

// 刪除欄位
const deleteField = (fieldId: string) => {
    const ok = window.confirm("確定要刪除此欄位嗎？此操作無法復原。");
    if (!ok) return;
    sectionData.value.fields = sectionData.value.fields.filter(
        (f) => f.id !== fieldId
    );
    emitUpdate();
};

// 移動欄位
const moveField = (currentIndex: number, direction: "up" | "down") => {
    const newIndex = direction === "up" ? currentIndex - 1 : currentIndex + 1;
    if (newIndex >= 0 && newIndex < sectionData.value.fields.length) {
        const fields = [...sectionData.value.fields];
        [fields[currentIndex], fields[newIndex]] = [
            fields[newIndex] as FieldConfig,
            fields[currentIndex] as FieldConfig
        ];
        sectionData.value.fields = fields;
        emitUpdate();
    }
};

// 確認刪除
// 監聽外部資料變更
watch(
    () => props.data,
    (newData) => {
        if (newData) {
            sectionData.value = { ...newData };
        }
    },
    { deep: true }
);
</script>

<template>
    <UCollapsible class="flex flex-col" :default-open="true" :data-id="sectionData.id">
        <div
            class="flex items-center justify-between bg-primary/10 p-4 rounded-lg sticky top-0 z-10 backdrop-blur-xs">
            <div class="leading flex items-center gap-2 cursor-pointer ">
                <UIcon name="i-lucide-list-collapse" class="size-5" />

                <h3 class="text-lg font-semibold">
                    第{{ index }}卡內容編輯
                </h3>
            </div>
            <div class="flex items-center gap-2">
                <UButton
                    v-if="canSortSection"
                    icon="i-lucide-arrow-up"
                    color="neutral"
                    variant="ghost"
                    size="sm"
                    :disabled="!canMoveUp"
                    @click="handleMoveSection($event, 'up')" />
                <UButton
                    v-if="canSortSection"
                    icon="i-lucide-arrow-down"
                    color="neutral"
                    variant="ghost"
                    size="sm"
                    :disabled="!canMoveDown"
                    @click="handleMoveSection($event, 'down')" />
                <UButton
                    v-if="canCreateField"
                    :label="showFieldSelector ? '取消' : '新增欄位'"
                    :icon="
                        showFieldSelector ? 'i-lucide-x' : 'i-lucide-plus'
                    "
                    color="primary"
                    variant="outline"
                    size="sm"
                    @click="handleAddField($event)" />
                <UButton
                    v-if="canDeleteSection"
                    label="刪除"
                    icon="i-lucide-trash-2"
                    color="error"
                    variant="ghost"
                    size="sm"
                    @click="handleDeleteSection($event)" />
            </div>
        </div>

        <template #content>
            <div class="p-4">
                <!-- 欄位列表 -->
                <div v-if="sectionData.fields.length > 0" class="space-y-4">
                    <AboutFieldItem
                        v-for="(field, index) in sectionData.fields"
                        :key="field.id"
                        :field="field"
                        :index="index"
                        @update="updateField"
                        @delete="deleteField"
                        @move-up="moveField(index, 'up')"
                        @move-down="moveField(index, 'down')" />
                </div>

                <!-- 空狀態 -->
                <div
                    v-else
                    class="text-center py-12 text-gray-400 border-2 border-dashed rounded-lg">
                    <UIcon
                        name="i-lucide-file-text"
                        class="w-12 h-12 mx-auto mb-2" />
                    <p>尚未新增任何欄位</p>
                    <p class="text-sm">點擊「新增欄位」開始建立內容</p>
                </div>

                <!-- 欄位選擇器 -->
                <div v-if="showFieldSelector" class="mt-4">
                    <AboutFieldSelector @select="addField" />
                </div>
            </div>
        </template>
    </UCollapsible>
</template>
