<script setup lang="ts">
import type { CutSectionData } from "~/types/CutSectionField";

// 接收 structureId 作為 prop
const props = defineProps<{
    structureId?: number | null;
}>();

const {
    buildId,
    sections: cutSections,
    loading,
    submitError,
    fetchData,
    saveAbout
} = useAppAbout();
const { hasPermission, isSuperAdmin } = usePermission();

// 權限檢查
const canSortSection = computed(
    () => isSuperAdmin() || hasPermission("about.section.sort")
);
const deleteConfirmModalOpen = ref(false);
const deleteTarget = ref<{ id: string; label: string } | null>(null);

// 新增區塊
const addCutSection = async () => {
    const newSectionId = `section-${buildId.value}`;
    cutSections.value.push({
        id: newSectionId,
        index: cutSections.value.length + 1,
        fields: []
    });

    // 等待 DOM 更新後再滾動
    await nextTick();

    // 使用 data-id 屬性來找到元素（因為 UCollapsible 使用 data-id）
    const target = document.querySelector(`[data-id="${newSectionId}"]`);
    if (target) {
        target.scrollIntoView({ behavior: "smooth", block: "start" });
    }
};

// 更新區塊資料
const updateSection = (updatedSection: CutSectionData) => {
    const index = cutSections.value.findIndex(
        (s) => s.id === updatedSection.id
    );
    if (index !== -1) {
        cutSections.value[index] = { ...updatedSection };
        console.log(`第${index + 1}卡已更新:`, updatedSection);
    } else {
        cutSections.value.push({ ...updatedSection });
    }
};

// 刪除區塊
const deleteSection = async (sectionId: string) => {
    const index = cutSections.value.findIndex((s) => s.id === sectionId);
    if (index !== -1) {
        cutSections.value.splice(index, 1);
        // 重新計算索引
        cutSections.value.forEach((section, idx) => {
            section.index = idx + 1;
        });
        console.log(`第${index + 1}卡已刪除:`, sectionId);
    }
    await saveAbout(props.structureId ?? null);
};

// 開啟刪除確認
const requestDeleteSection = (sectionId: string, label: string) => {
    deleteTarget.value = { id: sectionId, label };
    deleteConfirmModalOpen.value = true;
};

const confirmDeleteSection = () => {
    if (deleteTarget.value) {
        deleteSection(deleteTarget.value.id);
    }
    deleteConfirmModalOpen.value = false;
    deleteTarget.value = null;
};

// 移動區塊排序
const moveSection = async (sectionId: string, direction: "up" | "down") => {
    const currentIndex = cutSections.value.findIndex((s) => s.id === sectionId);
    if (currentIndex === -1) return;

    const targetIndex =
        direction === "up" ? currentIndex - 1 : currentIndex + 1;
    if (targetIndex < 0 || targetIndex >= cutSections.value.length) return;

    const sections = [...cutSections.value];
    [sections[currentIndex], sections[targetIndex]] = [
        sections[targetIndex] as CutSectionData,
        sections[currentIndex] as CutSectionData
    ];

    sections.forEach((section, idx) => {
        section.index = idx + 1;
    });
    cutSections.value = sections;

    await saveAbout(props.structureId ?? null);
};

// 儲存所有區塊資料
const saveAllSections = async () => {
    console.log("儲存所有區塊資料:", cutSections.value);
    await saveAbout(props.structureId ?? null);
};

// 初始化資料
onMounted(async () => {
    console.log("[AppAbout] onMounted: 開始初始化", {
        structureId: props.structureId,
        timestamp: new Date().toISOString()
    });
    try {
        console.log("[AppAbout] onMounted: 準備呼叫 fetchData");
        await fetchData(props.structureId ?? null);
        await nextTick();
        console.log("[AppAbout] onMounted: 初始化完成", {
            sectionsCount: cutSections.value.length,
            cutSections: cutSections.value
        });
    } catch (error) {
        console.error("[AppAbout] onMounted: 初始化失敗", error);
    }
});

// 添加 onBeforeMount 來確認組件生命週期
onBeforeMount(() => {
    console.log("[AppAbout] onBeforeMount: 組件即將掛載", {
        structureId: props.structureId
    });
});

// 暴露方法和狀態給父組件使用（用於 header 按鈕）
defineExpose({
    addCutSection,
    saveAllSections,
    loading,
    canSortSection
});
</script>

<template>
    <div>
        <div v-if="loading" class="flex items-center justify-center py-12">
            <UIcon name="i-lucide-loader-2" class="w-6 h-6 animate-spin" />
        </div>
        <template v-else>
            <UAlert
                v-if="submitError"
                color="error"
                variant="soft"
                icon="i-lucide-alert-circle"
                title="錯誤"
                :description="submitError"
                class="mb-4" />
            <template v-if="cutSections.length == 0">
                <CutSection
                    index="1"
                    @update="updateSection"
                    @delete="deleteSection" />
            </template>
            <template v-else>
                <CutSection
                    v-for="section in cutSections"
                    :key="section.id"
                    :index="section.index"
                    :data="section"
                    :can-move-up="section.index > 1 && canSortSection"
                    :can-move-down="
                        section.index < cutSections.length && canSortSection
                    "
                    @update="updateSection"
                    @delete-request="
                        requestDeleteSection(
                            section.id,
                            `第${section.index}卡內容編輯`
                        )
                    "
                    @move-up="moveSection(section.id, 'up')"
                    @move-down="moveSection(section.id, 'down')" />
            </template>
            <div class="flex justify-end gap-3 mt-4">
                <PermissionGuard permission="about.section.create">
                    <UButton
                        label="新增區塊(卡)"
                        color="primary"
                        icon="i-lucide-plus"
                        @click="addCutSection"
                        :ui="{ base: 'justify-center' }" />
                </PermissionGuard>
                <UButton
                    label="儲存"
                    color="success"
                    icon="i-lucide-save"
                    :loading="loading"
                    :disabled="loading"
                    @click="saveAllSections" />
            </div>
        </template>
    </div>
    <DeleteConfirmModal
        v-model:open="deleteConfirmModalOpen"
        title="確認刪除區塊"
        :description="
            deleteTarget
                ? `確定要刪除「${deleteTarget.label}」嗎？此操作無法復原，區塊內的所有欄位資料將會被永久刪除。`
                : ''
        "
        :on-confirm="confirmDeleteSection" />
</template>
