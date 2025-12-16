<script setup lang="ts">
import type { CutSectionData } from "~/types/CutSectionField";
import { useAbout } from "~/composables/useAbout";

definePageMeta({
    middleware: "auth"
});

const {
    buildId,
    sections: cutSections,
    loading,
    fetchData,
    saveAbout
} = useAbout();
const { hasPermission, isSuperAdmin } = usePermission();

// 權限檢查
const canCreateSection = computed(
    () => isSuperAdmin() || hasPermission("about.section.create")
);
const canSortSection = computed(
    () => isSuperAdmin() || hasPermission("about.section.sort")
);
const canDeleteSection = computed(
    () => isSuperAdmin() || hasPermission("about.section.delete")
);
const deleteConfirmModalOpen = ref(false);
const deleteTarget = ref<{ id: string; label: string } | null>(null);

// 新增區塊
const addCutSection = () => {
    cutSections.value.push({
        id: `section-${buildId.value}`,
        index: cutSections.value.length + 1,
        fields: []
    });
};

// 更新區塊資料
const updateSection = (updatedSection: CutSectionData) => {
    const index = cutSections.value.findIndex(
        (s) => s.id === updatedSection.id
    );
    if (index !== -1) {
        cutSections.value[index] = { ...updatedSection };
        // 這裡可以實作自動儲存或手動儲存邏輯
        console.log(`第${index + 1}卡已更新:`, updatedSection);
    } else {
        // 如果找不到對應的區塊，可能是第一個區塊（尚未加入陣列）
        // 將它加入陣列
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
    await saveAbout();
    // await fetchData();
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

    await saveAbout();
    // await fetchData();
};

// 儲存所有區塊資料
const saveAllSections = async () => {
    console.log("儲存所有區塊資料:", cutSections.value);
    await saveAbout();
    // await fetchData();
};

// 初始化資料
onMounted(async () => {
    await fetchData();
    await nextTick();
    console.log("cutSections:", cutSections.value);
});
</script>
<template>
    <UDashboardPanel>
        <template #header>
            <UDashboardNavbar title="關於我們" :ui="{ right: 'gap-3' }">
                <template #leading>
                    <UDashboardSidebarCollapse />
                </template>
                <template #right>
                    <UButton
                        v-if="canCreateSection"
                        label="新增區塊(卡)"
                        color="primary"
                        icon="i-lucide-plus"
                        @click="addCutSection"
                        :ui="{ base: 'justify-center' }" />
                    <UButton
                        label="儲存所有區塊"
                        color="success"
                        icon="i-lucide-save"
                        @click="saveAllSections"
                        :ui="{ base: 'justify-center' }" />
                </template>
            </UDashboardNavbar>
        </template>
        <template #body>
            <div v-if="loading" class="flex items-center justify-center py-12">
                <UIcon name="i-lucide-loader-2" class="w-6 h-6 animate-spin" />
            </div>
            <template v-else>
                <template v-if="cutSections.length == 0">
                    <AboutCutSection
                        index="1"
                        @update="updateSection"
                        @delete="deleteSection" />
                </template>
                <template v-else>
                    <AboutCutSection
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
                <div class="flex justify-end gap-3">
                    <UButton
                        v-if="canCreateSection"
                        label="新增區塊(卡)"
                        color="primary"
                        icon="i-lucide-plus"
                        @click="addCutSection"
                        :ui="{ base: 'justify-center' }" />
                    <UButton
                        label="儲存所有區塊"
                        color="success"
                        icon="i-lucide-save"
                        @click="saveAllSections"
                        :ui="{ base: 'justify-center' }" />
                </div>
            </template>
        </template>
    </UDashboardPanel>
    <AboutDeleteConfirmModal
        v-model:open="deleteConfirmModalOpen"
        title="確認刪除區塊"
        :description="
            deleteTarget
                ? `確定要刪除「${deleteTarget.label}」嗎？此操作無法復原，區塊內的所有欄位資料將會被永久刪除。`
                : ''
        "
        :on-confirm="confirmDeleteSection" />
</template>
