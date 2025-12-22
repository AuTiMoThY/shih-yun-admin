import { useSortable } from "@vueuse/integrations/useSortable";
import { shallowRef, nextTick, onUnmounted } from "vue";

/**
 * 多張圖片上傳 Composable（支持拖拽排序）
 * 支持延遲上傳（資料庫寫入成功後才上傳）
 */
export const useImageUploadMultiple = (options?: {
    maxSize?: number; // 最大文件大小（字節），默認 5MB
    acceptTypes?: string[]; // 接受的檔案類型，默認 ["image/*"]
    enableSortable?: boolean; // 是否啟用排序功能，默認 true
}) => {
    console.log("useImageUploadMultiple");
    const { uploadImage, getImagePreview } = useImageUpload();
    const toast = useToast();

    const maxSize = options?.maxSize ?? 5 * 1024 * 1024; // 默認 5MB
    const acceptTypes = options?.acceptTypes ?? ["image/*"];
    const enableSortable = options?.enableSortable ?? true;

    // 輸入元素引用
    const inputRef = ref<HTMLInputElement | null>(null);
    // 預覽 URL 數組
    const previews = ref<string[]>([]);
    // 上傳狀態
    const isUploading = ref(false);
    // 待上傳的文件映射：key 是臨時 ID，value 是 File 對象
    const pendingFiles = ref<Map<string, File>>(new Map());
    // 用於排序的響應式數組（與 formValue 同步）
    const sortableData = shallowRef<string[]>([]);
    // 排序容器引用
    const sortableListRef = ref<HTMLElement | null>(null);
    let sortableStop: (() => void) | null = null;
    // 表單數據的響應式引用（用於更新實際值）
    const formValueRef = ref<string[]>([]);

    /**
     * 驗證文件
     */
    const validateFile = (file: File): boolean => {
        // 驗證檔案類型
        const isValidType = acceptTypes.some((type) => {
            if (type.endsWith("/*")) {
                const baseType = type.split("/")[0];
                return file.type.startsWith(`${baseType}/`);
            }
            return file.type === type;
        });

        if (!isValidType) {
            toast.add({
                title: "檔案格式錯誤",
                description: `${file.name} 不是圖片檔案`,
                color: "error"
            });
            return false;
        }

        // 驗證檔案大小
        if (file.size > maxSize) {
            toast.add({
                title: "檔案過大",
                description: `${file.name} 超過 ${Math.round(maxSize / 1024 / 1024)}MB`,
                color: "error"
            });
            return false;
        }

        return true;
    };

    /**
     * 生成臨時 ID
     */
    const generateTempId = (): string => {
        return `temp_${Date.now()}_${Math.random()
            .toString(36)
            .substring(2, 9)}`;
    };

    /**
     * 設置排序功能
     */
    const setupSortable = () => {
        if (!enableSortable || !useSortable) {
            return;
        }

        // 清理舊的實例
        if (sortableStop) {
            sortableStop();
            sortableStop = null;
        }

        if (!sortableListRef.value || sortableData.value.length === 0) {
            return;
        }

        const { stop } = useSortable(sortableListRef, sortableData, {
            handle: ".drag-handle",
            animation: 150,
            fallbackOnBody: true,
            swapThreshold: 0.65,
            onUpdate: async () => {
                // 根據 DOM 順序重建資料（參考 TreeTableRow.vue 的做法）
                const list = sortableData.value || [];
                const items = (Array.from(
                    sortableListRef.value?.querySelectorAll("[data-image-id]") ?? []
                ) || []) as HTMLElement[];
                const idsAfterDom = items
                    .map((item) => item.dataset.imageId)
                    .filter(Boolean);

                // 根據 DOM 順序重建資料
                const map = new Map(
                    list
                        .filter((x) => x !== undefined && x !== null && x !== "")
                        .map((x) => [String(x), x])
                );
                const newSortableData = idsAfterDom
                    .map((id) => map.get(String(id)))
                    .filter((x) => x !== undefined);

                if (!newSortableData.length || newSortableData.length !== map.size) {
                    console.warn("[image-sort] 排序不匹配，保持原順序");
                    return;
                }

                // 在更新前保存舊的 URL 到預覽的映射關係
                const urlToPreviewMap = new Map<string, string>();
                for (
                    let i = 0;
                    i < formValueRef.value.length && i < previews.value.length;
                    i++
                ) {
                    urlToPreviewMap.set(
                        formValueRef.value[i] || "",
                        previews.value[i] || ""
                    );
                }

                // 更新 sortableData 為新的順序
                sortableData.value = [...newSortableData];

                // 同步排序後的數據到 formValueRef
                formValueRef.value = [...newSortableData];

                // 根據新的順序重新排列 previews
                const newPreviews: string[] = [];
                for (const url of newSortableData) {
                    const preview = urlToPreviewMap.get(url) || url;
                    newPreviews.push(preview);
                }
                previews.value = newPreviews;
            }
        });
        sortableStop = stop;
    };

    /**
     * 處理文件選擇（支持多文件，暫不上傳）
     */
    const handleFileSelect = async (event: Event) => {
        const target = event.target as HTMLInputElement;
        const files = target.files;
        if (!files || files.length === 0) return;

        const fileArray = Array.from(files);

        // 驗證所有檔案
        for (const file of fileArray) {
            if (!validateFile(file)) {
                if (inputRef.value) {
                    inputRef.value.value = "";
                }
                return;
            }
        }

        try {
            const tempIds: string[] = [];
            const previewUrls: string[] = [];

            // 處理所有檔案，生成預覽並保存 File 對象
            for (const file of fileArray) {
                if (file) {
                    // 生成臨時 ID
                    const tempId = generateTempId();
                    tempIds.push(tempId);

                    // 保存 File 對象到映射
                    pendingFiles.value.set(tempId, file);

                    // 生成本地預覽
                    const previewUrl = await getImagePreview(file);
                    previewUrls.push(previewUrl);
                }
            }

            // 添加到表單數據（使用臨時 ID）
            formValueRef.value.push(...tempIds);
            previews.value.push(...previewUrls);

            // 同步到可排序數組
            sortableData.value = [...formValueRef.value];

            // 重新設置排序功能
            await nextTick();
            setupSortable();
        } catch (error) {
            console.error("圖片處理錯誤:", error);
            toast.add({
                title: "處理失敗",
                description: "圖片處理時發生錯誤",
                color: "error"
            });
        } finally {
            if (inputRef.value) {
                inputRef.value.value = "";
            }
        }
    };

    /**
     * 觸發文件選擇
     */
    const triggerFileSelect = () => {
        inputRef.value?.click();
    };

    /**
     * 移除指定索引的圖片
     */
    const remove = (index: number) => {
        const itemId = formValueRef.value[index];
        // 如果是待上傳的圖片，從映射中移除
        if (itemId && itemId.startsWith("temp_")) {
            pendingFiles.value.delete(itemId);
        }

        formValueRef.value.splice(index, 1);
        // 同步更新預覽
        if (previews.value.length > index) {
            previews.value.splice(index, 1);
        }
        // 同步到可排序數組
        sortableData.value = [...formValueRef.value];
        // 重新設置排序功能
        nextTick(() => {
            setupSortable();
        });
    };

    /**
     * 上傳待上傳的圖片
     */
    const upload = async (): Promise<boolean> => {
        const pendingIds = formValueRef.value.filter((id) =>
            id.startsWith("temp_")
        ) as string[];

        if (pendingIds.length === 0) {
            return true; // 沒有待上傳的圖片
        }

        isUploading.value = true;

        try {
            // 上傳所有待上傳的圖片
            for (let i = 0; i < formValueRef.value.length; i++) {
                const itemId = formValueRef.value[i];
                if (itemId && itemId.startsWith("temp_")) {
                    const file = pendingFiles.value.get(itemId);
                    if (file) {
                        const uploadedUrl = await uploadImage(file);
                        if (uploadedUrl) {
                            // 替換臨時 ID 為實際 URL
                            formValueRef.value[i] = uploadedUrl;
                            previews.value[i] = uploadedUrl;
                            // 從映射中移除
                            pendingFiles.value.delete(itemId);
                        } else {
                            toast.add({
                                title: "上傳失敗",
                                description: "部分圖片上傳失敗，請重試",
                                color: "error"
                            });
                            return false;
                        }
                    }
                }
            }

            // 同步到可排序數組
            sortableData.value = [...formValueRef.value];
            return true;
        } catch (error) {
            console.error("上傳圖片錯誤:", error);
            toast.add({
                title: "上傳失敗",
                description: "圖片上傳時發生錯誤",
                color: "error"
            });
            return false;
        } finally {
            isUploading.value = false;
        }
    };

    /**
     * 載入初始數據（編輯模式）
     */
    const loadInitialValue = (value: string[]) => {
        console.log("loadInitialValue", value);
        if (Array.isArray(value) && value.length > 0) {
            previews.value = [...value];
            sortableData.value = [...value];
            formValueRef.value = [...value];
            // 清除待上傳映射（編輯模式下不應該有待上傳的文件）
            pendingFiles.value.clear();
            // 設置排序功能 - 等待 DOM 渲染完成
            const trySetupSortable = (retries = 10) => {
                nextTick(() => {
                    if (sortableListRef.value && sortableData.value.length > 0) {
                        // DOM 已準備好，設置排序功能
                        setupSortable();
                    } else if (retries > 0) {
                        // DOM 還沒準備好，重試
                        setTimeout(() => {
                            trySetupSortable(retries - 1);
                        }, 50);
                    }
                });
            };
            trySetupSortable();
        } else {
            previews.value = [];
            sortableData.value = [];
            formValueRef.value = [];
            pendingFiles.value.clear();
        }
    };

    /**
     * 重置
     */
    const reset = () => {
        previews.value = [];
        sortableData.value = [];
        formValueRef.value = [];
        pendingFiles.value.clear();
        if (inputRef.value) {
            inputRef.value.value = "";
        }
        // 清理排序實例
        if (sortableStop) {
            sortableStop();
            sortableStop = null;
        }
    };

    // 組件卸載時清理排序功能
    onUnmounted(() => {
        if (sortableStop) {
            sortableStop();
            sortableStop = null;
        }
    });

    return {
        // Refs
        inputRef,
        previews,
        isUploading,
        sortableData, // 用於排序的數據（與 formValue 同步）
        sortableListRef, // 排序容器引用
        formValue: formValueRef, // 用於雙向綁定表單數據

        // Methods
        handleFileSelect,
        triggerFileSelect,
        remove,
        upload,
        loadInitialValue,
        reset,
        setupSortable,

        // Computed
        hasPendingFiles: computed(() => pendingFiles.value.size > 0)
    };
};
