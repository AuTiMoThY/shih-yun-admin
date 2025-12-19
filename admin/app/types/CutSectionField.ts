// 欄位類型
export type FieldType = "title" | "subtitle" | "content" | "desktop_image" | "mobile_image" | "video";

// 欄位配置
export interface FieldConfig {
    type: FieldType;
    label: string; // 欄位標題（使用者可自訂）
    value: string; // 欄位內容
    id: string; // 唯一識別碼
}

// 欄位類型定義（用於選擇器）
export interface FieldTypeOption {
    type: FieldType;
    label: string;
    icon: string;
    description: string;
}

// 區塊資料結構
export interface CutSectionData {
    id: string;
    index: number;
    fields: FieldConfig[];
}
