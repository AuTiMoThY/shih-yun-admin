<script setup lang="ts">
type ModalMode = "add" | "edit";
const props = withDefaults(
    defineProps<{
        mode: ModalMode;
        data?: any;
    }>(),
    {
        data: undefined
    }
);

const router = useRouter();

const {
    form,
    errors,
    loading,
    submitError,
    clearError,
    resetForm,
    loadFormData,
    addRole,
    editRole
} = useRole();

const { data: permissionData, fetchData: fetchPermissions } =
    usePermissionData();
const { data: structureData, fetchData: fetchStructures } = useStructure();

// 標題
const pageTitle = computed(() => {
    switch (props.mode) {
        case "add":
            return "新增角色";
        case "edit":
            return "編輯角色";
        default:
            return "角色";
    }
});

const handleSubmit = async () => {
    if (props.mode === "add") {
        await addRole({
            onSuccess: () => router.push("/system/roles")
        });
    }
    if (props.mode === "edit") {
        await editRole({
            id: props.data?.id,
            onSuccess: () => router.push("/system/roles")
        });
    }
};

// 搜尋關鍵字
const searchKeyword = ref("");

// 扁平化單元樹狀結構，用於分組
const flattenStructures = (items: any[]): any[] => {
    const result: any[] = [];
    const traverse = (nodes: any[]) => {
        for (const node of nodes) {
            if (node && node.id) {
                // 只顯示有 URL 的單元（表示是實際的單元，而非分類）
                if (node.url) {
                    result.push({
                        id: node.id,
                        label: node.label,
                        url: node.url
                    });
                }
                // 遞迴處理子節點
                if (node.children && Array.isArray(node.children)) {
                    traverse(node.children);
                }
            }
        }
    };
    traverse(items);
    return result;
};

const structureList = computed(() =>
    flattenStructures(structureData.value || [])
);

// 按單元分組的權限資料
const groupedPermissions = computed(() => {
    const groups: Record<string, any[]> = {};
    const noStructureGroup: any[] = [];

    permissionData.value.forEach((permission: any) => {
        // 如果沒有搜尋關鍵字，或權限標籤/名稱包含關鍵字
        const matchesSearch =
            !searchKeyword.value ||
            permission.label
                .toLowerCase()
                .includes(searchKeyword.value.toLowerCase()) ||
            permission.name
                ?.toLowerCase()
                .includes(searchKeyword.value.toLowerCase());

        if (!matchesSearch) return;

        // 從權限名稱中提取 URL 前綴（例如：about.view -> about）
        if (permission.name) {
            const urlPrefix = permission.name.split(".")[0];
            const structure = structureList.value.find(
                (s: any) => s.url === urlPrefix
            );

            if (structure) {
                const structureKey = `${structure.id}_${structure.label}`;
                if (!groups[structureKey]) {
                    groups[structureKey] = [];
                }
                groups[structureKey].push(permission);
            } else {
                // 找不到對應單元的權限，歸類到「其他」
                noStructureGroup.push(permission);
            }
        } else {
            // 沒有權限名稱的，歸類到「其他」
            noStructureGroup.push(permission);
        }
    });

    // 如果有未分組的權限，加入「其他」群組
    if (noStructureGroup.length > 0) {
        groups["other_其他"] = noStructureGroup;
    }

    return groups;
});

// 取得單元資訊
const getStructureInfo = (key: string) => {
    if (key.startsWith("other_")) {
        return { id: null, label: "其他", name: "other" };
    }

    // key 格式：${structure.id}_${structure.label}
    // 為了處理 label 中可能包含底線的情況，我們需要找到第一個底線的位置
    const firstUnderscoreIndex = key.indexOf("_");
    if (firstUnderscoreIndex === -1) {
        return { id: null, label: "未知單元", name: "" };
    }

    const structureIdStr = key.substring(0, firstUnderscoreIndex);
    const labelFromKey = key.substring(firstUnderscoreIndex + 1);

    if (!structureIdStr) {
        return { id: null, label: "未知單元", name: "" };
    }

    // 轉換 ID 為數字進行比較（確保類型一致）
    const structureId = Number(structureIdStr);
    if (isNaN(structureId)) {
        return { id: null, label: labelFromKey || "未知單元", name: "" };
    }

    // 在 structureList 中查找（同時比較數字和字串格式的 ID）
    const structure = structureList.value.find(
        (s: any) =>
            Number(s.id) === structureId || String(s.id) === structureIdStr
    );

    // 如果找到，返回完整的 structure 資訊；否則使用 key 中的 label 作為備用
    return (
        structure || {
            id: structureId,
            label: labelFromKey || `單元 ${structureId}`,
            name: ""
        }
    );
};

// 輔助函數：統一 ID 類型為數字進行比較
const isPermissionSelected = (permissionId: any): boolean => {
    const id = Number(permissionId);
    return form.permission_ids.some(
        (selectedId: number) => Number(selectedId) === id
    );
};

// 計算每個單元的已選數量
const getStructureSelectedCount = (permissions: any[]) => {
    return permissions.filter((p: any) => isPermissionSelected(p.id)).length;
};

// 檢查單元是否全選
const isStructureAllSelected = (permissions: any[]) => {
    if (permissions.length === 0) return false;
    return permissions.every((p: any) => isPermissionSelected(p.id));
};

// 檢查單元是否部分選中
const isStructureIndeterminate = (permissions: any[]) => {
    const selectedCount = getStructureSelectedCount(permissions);
    return selectedCount > 0 && selectedCount < permissions.length;
};

// 切換單元全選/取消全選
const toggleStructureSelection = (permissions: any[]) => {
    const allSelected = isStructureAllSelected(permissions);
    const permissionIds = permissions.map((p: any) => Number(p.id));

    if (allSelected) {
        // 取消全選：移除該單元的所有權限
        form.permission_ids = form.permission_ids.filter(
            (id: number) => !permissionIds.includes(Number(id))
        );
    } else {
        // 全選：加入該單元的所有權限
        permissionIds.forEach((id: number) => {
            if (!isPermissionSelected(id)) {
                form.permission_ids.push(id);
            }
        });
    }
};

// 全選/取消全選所有權限
const toggleAllSelection = () => {
    const allPermissionIds = permissionData.value.map((p: any) => Number(p.id));
    const allSelected = allPermissionIds.every((id: number) =>
        isPermissionSelected(id)
    );

    if (allSelected) {
        form.permission_ids = [];
    } else {
        form.permission_ids = [...allPermissionIds];
    }
};

// 檢查是否全選
const isAllSelected = computed(() => {
    if (permissionData.value.length === 0) return false;
    return permissionData.value.every((p: any) => isPermissionSelected(p.id));
});

// 已選擇的權限數量
const selectedCount = computed(() => form.permission_ids.length);

// 展開的單元（預設全部展開）
const expandedStructures = ref<Set<string>>(new Set());

// 初始化展開狀態
const initExpandedStructures = () => {
    expandedStructures.value = new Set(Object.keys(groupedPermissions.value));
};

// 切換單元展開/收合
const toggleStructureExpansion = (key: string) => {
    if (expandedStructures.value.has(key)) {
        expandedStructures.value.delete(key);
    } else {
        expandedStructures.value.add(key);
    }
};

// 當 modal 開啟時重置表單或載入資料
onMounted(async () => {
    await fetchPermissions();
    await fetchStructures();
    console.log("permissionData", permissionData.value);

    // 檢查是否為編輯模式且有資料
    if (props.mode === "edit") {
        // 如果 data 是 ref，等待其值載入
        if (
            props.data &&
            typeof props.data === "object" &&
            "value" in props.data
        ) {
            // props.data 是一個 ref
            if (props.data.value) {
                loadFormData(props.data.value);
            }
        } else if (props.data) {
            // props.data 是直接的值
            loadFormData(props.data);
        }
    } else {
        // 新增模式：重置表單
        resetForm();
    }
    initExpandedStructures();
});

// 監聽 props.data 的變化（用於編輯模式，當資料異步載入完成時）
watch(
    () => {
        // 如果 data 是 ref，返回其 value；否則返回 data 本身
        if (
            props.data &&
            typeof props.data === "object" &&
            "value" in props.data
        ) {
            return props.data.value;
        }
        return props.data;
    },
    (newData) => {
        if (props.mode === "edit" && newData) {
            console.log("載入編輯資料", newData);
            loadFormData(newData);
        }
    },
    { immediate: false }
);

// 當分組資料變化時，更新展開狀態
watch(
    groupedPermissions,
    () => {
        initExpandedStructures();
    },
    { deep: true }
);
</script>
<template>
    <UDashboardPanel>
        <template #header>
            <UDashboardNavbar
                :title="pageTitle"
                :ui="{ right: 'gap-3', title: 'text-primary' }">
                <template #leading>
                    <UDashboardSidebarCollapse />
                </template>
                <template #right>
                    <UButton
                        label="儲存"
                        type="button"
                        color="success"
                        icon="i-lucide-save"
                        :loading="loading"
                        :disabled="loading"
                        @click="handleSubmit()" />
                </template>
            </UDashboardNavbar>
            <UDashboardToolbar>
                <template #left>
                    <UButton
                        label="返回列表"
                        color="neutral"
                        variant="ghost"
                        icon="i-lucide-arrow-left"
                        to="/system/roles" />
                </template>
            </UDashboardToolbar>
        </template>
        <template #body>
            <UForm :state="form" @submit="handleSubmit" class="space-y-4">
                <UFormField
                    label="角色代碼"
                    name="name"
                    :error="errors.name"
                    required>
                    <UInput
                        v-model="form.name"
                        placeholder="請輸入角色代碼（英數字、底線、連字號）"
                        size="lg"
                        :disabled="loading"
                        class="w-full"
                        @input="clearError('name')" />
                </UFormField>
                <UFormField
                    label="角色名稱"
                    name="label"
                    :error="errors.label"
                    required>
                    <UInput
                        v-model="form.label"
                        placeholder="請輸入角色名稱"
                        size="lg"
                        :disabled="loading"
                        class="w-full"
                        @input="clearError('label')" />
                </UFormField>
                <UFormField label="描述" name="description">
                    <UTextarea
                        v-model="form.description"
                        placeholder="請輸入角色描述（選填）"
                        :disabled="loading"
                        class="w-full"
                        :rows="3" />
                </UFormField>
                <FormStatusField
                    v-model="form.status"
                    label="狀態"
                    name="status"
                    :disabled="loading" />

                <h3 class="block font-medium text-default">權限</h3>
                <!-- 權限列表 -->
                <UCard>
                    <template #header>
                        <div class="space-y-4">
                            <!-- 搜尋和全選控制列 -->
                            <div
                                v-if="permissionData.length > 0"
                                class="flex items-center gap-3">
                                <div class="flex-1">
                                    <UInput
                                        v-model="searchKeyword"
                                        placeholder="搜尋權限..."
                                        icon="i-lucide-search"
                                        size="sm"
                                        :disabled="loading" />
                                </div>
                                <div
                                    class="flex items-center gap-2 text-sm text-gray-600">
                                    <span
                                        >已選擇：{{ selectedCount }} /
                                        {{ permissionData.length }}</span
                                    >
                                    <UButton
                                        size="xs"
                                        variant="ghost"
                                        color="primary"
                                        :disabled="loading"
                                        @click="toggleAllSelection">
                                        {{
                                            isAllSelected ? "取消全選" : "全選"
                                        }}
                                    </UButton>
                                </div>
                            </div>
                        </div>
                    </template>
                    <div
                        v-if="permissionData.length === 0"
                        class="p-8 text-center text-sm text-gray-500">
                        暫無權限資料
                    </div>
                    <div
                        v-else-if="Object.keys(groupedPermissions).length === 0"
                        class="p-8 text-center text-sm text-gray-500">
                        沒有符合搜尋條件的權限
                    </div>
                    <div v-else class="divide-y">
                        <!-- 按單元分組顯示 -->
                        <div
                            v-for="(
                                permissions, structureKey
                            ) in groupedPermissions"
                            :key="structureKey"
                            class="bg-white">
                            <!-- 單元標題列（可展開/收合） -->
                            <div
                                class="flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 cursor-pointer transition-colors"
                                @click="toggleStructureExpansion(structureKey)">
                                <div class="flex items-center gap-3 flex-1">
                                    <UIcon
                                        :name="
                                            expandedStructures.has(structureKey)
                                                ? 'i-lucide-chevron-down'
                                                : 'i-lucide-chevron-right'
                                        "
                                        class="w-4 h-4 text-gray-500" />
                                    <div class="flex items-center gap-2 flex-1">
                                        <span class="font-medium text-gray-900">
                                            {{
                                                getStructureInfo(structureKey)
                                                    .label
                                            }}
                                        </span>
                                        <span class="text-xs text-gray-500">
                                            ({{
                                                getStructureSelectedCount(
                                                    permissions
                                                )
                                            }}
                                            / {{ permissions.length }})
                                        </span>
                                    </div>
                                </div>
                                <UCheckbox
                                    :model-value="
                                        isStructureAllSelected(permissions)
                                    "
                                    :indeterminate="
                                        isStructureIndeterminate(permissions)
                                    "
                                    :disabled="loading"
                                    @click.stop="
                                        toggleStructureSelection(permissions)
                                    " />
                            </div>

                            <!-- 權限列表（可展開/收合） -->
                            <div
                                v-show="expandedStructures.has(structureKey)"
                                class="p-3 space-y-2 bg-white">
                                <ul
                                    v-for="permission in permissions"
                                    :key="permission.id"
                                    class="flex items-start gap-2 p-2 rounded hover:bg-gray-50 transition-colors">
                                    <li class="w-full flex items-center gap-2">
                                        <UCheckbox
                                            :model-value="
                                                isPermissionSelected(
                                                    permission.id
                                                )
                                            "
                                            :disabled="loading"
                                            :label="permission.label"
                                            @update:model-value="
                                                        (value: boolean | 'indeterminate') => {
                                                            const checked = value === true;
                                                            const permissionId = Number(permission.id);
                                                            if (checked) {
                                                                if (!isPermissionSelected(permission.id)) {
                                                                    form.permission_ids.push(permissionId);
                                                                }
                                                            } else {
                                                                form.permission_ids = form.permission_ids.filter(
                                                                    (id: number) => Number(id) !== permissionId
                                                                );
                                                            }
                                                        }
                                                    "
                                            :ui="{ label: 'cursor-pointer' }" />
                                        <div
                                            class="flex items-center gap-2 flex-1 min-w-0">
                                            <div
                                                v-if="permission.description"
                                                class="text-xs text-gray-400">
                                                {{ permission.description }}
                                            </div>
                                            <div
                                                v-if="permission.name"
                                                class="text-xs text-gray-400 font-mono">
                                                {{ permission.name }}
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </UCard>

                <div class="mt-6 flex gap-4 justify-end">
                    <UButton
                        type="button"
                        color="neutral"
                        variant="ghost"
                        :disabled="loading"
                        to="/system/roles"
                        label="取消" />

                    <UButton
                        type="submit"
                        label="儲存"
                        color="success"
                        icon="i-lucide-save"
                        :loading="loading"
                        :disabled="loading" />
                </div>
            </UForm>
        </template>
        <template #footer>
            <PageFooter />
        </template>
    </UDashboardPanel>
</template>
