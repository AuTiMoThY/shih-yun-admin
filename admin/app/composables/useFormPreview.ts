/**
 * 表單預覽 Composable
 * 提供側邊欄預覽功能的通用邏輯
 * 可在 News、Case、About 等模組中重複使用
 */

export interface PreviewData {
    // 基本欄位
    title?: string;
    content?: string; // HTML 內容
    cover?: string; // 封面圖 URL
    slide?: string[]; // 輪播圖 URL 陣列
    
    // 日期相關
    show_date?: string; // 顯示日期
    
    // 其他自訂欄位（用於擴展）
    [key: string]: any;
}

export interface PreviewImageSources {
    // 封面圖來源（優先使用預覽，其次使用表單值）
    cover?: {
        preview?: string | null;
        formValue?: string;
    };
    // 輪播圖來源
    slide?: {
        previews?: string[];
        formValue?: string[];
    };
}

export interface UseFormPreviewOptions {
    // 預設是否開啟預覽
    defaultOpen?: boolean;
    // 側邊欄寬度（預設 500px）
    width?: string;
    // 預覽標題
    title?: string;
    // 自訂渲染函數（可選，用於自訂預覽內容）
    customRenderer?: (data: PreviewData) => string;
}

export const useFormPreview = (options?: UseFormPreviewOptions) => {
    const {
        defaultOpen = false,
        width = "500px",
        title = "預覽",
        customRenderer
    } = options || {};

    // 側邊欄開關狀態
    const isOpen = ref(defaultOpen);
    
    // 預覽數據
    const previewData = ref<PreviewData>({});
    
    // 圖片來源（用於處理臨時圖片）
    const imageSources = ref<PreviewImageSources>({});

    /**
     * 開啟預覽
     */
    const open = () => {
        isOpen.value = true;
    };

    /**
     * 關閉預覽
     */
    const close = () => {
        isOpen.value = false;
    };

    /**
     * 切換預覽開關
     */
    const toggle = () => {
        isOpen.value = !isOpen.value;
    };

    /**
     * 更新預覽數據
     * @param data 表單數據
     * @param imageSources 圖片來源（可選）
     */
    const updatePreview = (
        data: PreviewData,
        imageSources?: PreviewImageSources
    ) => {
        // 深拷貝數據，確保陣列是可變的
        const newData: PreviewData = { ...data };
        if (Array.isArray(data.slide)) {
            newData.slide = [...data.slide];
        }
        
        previewData.value = newData;
        
        // 處理圖片來源
        if (imageSources) {
            // 封面圖：優先使用預覽，其次使用表單值
            if (imageSources.cover) {
                const coverUrl =
                    imageSources.cover.preview ||
                    imageSources.cover.formValue ||
                    "";
                previewData.value.cover = coverUrl;
            }
            
            // 輪播圖：優先使用預覽陣列，其次使用表單值
            if (imageSources.slide) {
                const slideUrls =
                    imageSources.slide.previews ||
                    imageSources.slide.formValue ||
                    [];
                previewData.value.slide = [...slideUrls];
            }
        }
    };

    /**
     * 取得有效的封面圖 URL
     * 處理臨時 ID（temp_ 開頭）的情況
     */
    const getCoverUrl = (): string => {
        const cover = previewData.value.cover;
        if (!cover) return "";
        
        // 如果是臨時 ID，嘗試從 imageSources 取得預覽
        if (cover.startsWith("temp_") && imageSources.value.cover?.preview) {
            return imageSources.value.cover.preview;
        }
        
        return cover;
    };

    /**
     * 取得有效的輪播圖 URL 陣列
     * 處理臨時 ID（temp_ 開頭）的情況
     */
    const getSlideUrls = (): string[] => {
        const slides = previewData.value.slide || [];
        
        // 如果有 imageSources，優先使用預覽
        if (imageSources.value.slide?.previews) {
            return imageSources.value.slide.previews;
        }
        
        // 過濾掉臨時 ID（如果沒有對應的預覽）
        return slides.filter((url) => !url.startsWith("temp_"));
    };

    /**
     * 重置預覽數據
     */
    const reset = () => {
        previewData.value = {};
        imageSources.value = {};
    };

    return {
        // 狀態
        isOpen: readonly(isOpen),
        previewData: readonly(previewData),
        
        // 方法
        open,
        close,
        toggle,
        updatePreview,
        getCoverUrl,
        getSlideUrls,
        reset,
        
        // 配置
        width,
        title,
        customRenderer
    };
};

