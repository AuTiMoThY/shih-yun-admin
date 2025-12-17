/**
 * 狀態欄位的 composable
 * 用於處理狀態欄位在 boolean 和 string/number 之間的轉換
 * 
 * @param statusRef - 狀態欄位的 ref 或 reactive 對象的屬性
 * @param options - 選項配置
 * @returns statusBoolean - 用於 UCheckbox 的 v-model 的 computed 屬性
 */
export const useStatusField = <T extends string | number | boolean>(
    statusRef: Ref<T> | (() => T),
    options?: {
        /**
         * 將 boolean 轉換為目標類型的函數
         * 例如：value => value ? 1 : 0 (轉換為 number)
         * 例如：value => value ? "1" : "0" (轉換為 string)
         * 例如：value => value (保持 boolean)
         */
        transformTo?: (value: boolean) => T;
        /**
         * 將目標類型轉換為 boolean 的函數
         * 例如：value => Number(value) === 1 (從 number 轉換)
         * 例如：value => value === "1" (從 string 轉換)
         * 例如：value => Boolean(value) (從 boolean 轉換)
         */
        transformFrom?: (value: T) => boolean;
    }
) => {
    // 如果 statusRef 是函數，則創建一個 computed 來讀取值
    const getStatus = typeof statusRef === 'function' 
        ? computed(() => statusRef())
        : computed(() => statusRef.value);
    
    // 如果 statusRef 是函數，需要找到對應的 setter
    // 這裡假設 statusRef 是從 reactive 對象中取得的
    // 實際使用時，應該傳入 ref 或 reactive 對象的屬性
    
    const statusBoolean = computed({
        get: () => {
            const value = getStatus.value;
            
            // 如果提供了自定義轉換函數，使用它
            if (options?.transformFrom) {
                return options.transformFrom(value);
            }
            
            // 默認轉換邏輯
            if (typeof value === "boolean") {
                return value;
            }
            if (typeof value === "number") {
                return value === 1;
            }
            if (typeof value === "string") {
                return value === "1";
            }
            return Boolean(value);
        },
        set: (value: boolean) => {
            // 如果提供了自定義轉換函數，使用它
            if (options?.transformTo) {
                const transformed = options.transformTo(value);
                if (typeof statusRef === 'function') {
                    // 如果是函數，無法直接設置，需要通過其他方式
                    // 這種情況下應該傳入 ref
                    console.warn('useStatusField: 無法設置函數類型的 statusRef，請傳入 ref');
                } else {
                    statusRef.value = transformed as T;
                }
                return;
            }
            
            // 默認轉換邏輯：根據原始類型決定如何設置
            if (typeof statusRef === 'function') {
                console.warn('useStatusField: 無法設置函數類型的 statusRef，請傳入 ref');
                return;
            }
            
            const currentValue = statusRef.value;
            if (typeof currentValue === "number") {
                statusRef.value = (value ? 1 : 0) as T;
            } else if (typeof currentValue === "string") {
                statusRef.value = (value ? "1" : "0") as T;
            } else {
                statusRef.value = value as T;
            }
        }
    });
    
    return {
        statusBoolean
    };
};
