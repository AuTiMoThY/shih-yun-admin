<script setup lang="ts">
import { STATUS_LABEL_MAP } from "~/constants/system/status";

interface Props {
    /**
     * 狀態欄位的值
     */
    modelValue: string | number | boolean;
    /**
     * 錯誤訊息
     */
    error?: string | boolean;
    /**
     * 是否禁用
     */
    disabled?: boolean;
    /**
     * 標籤文字（默認為"狀態"）
     */
    label?: string;
    /**
     * 欄位名稱（用於表單驗證）
     */
    name?: string;
    /**
     * 是否必填
     */
    required?: boolean;
    /**
     * 將 boolean 轉換為目標類型的函數
     */
    transformTo?: (value: boolean) => string | number | boolean;
    /**
     * 將目標類型轉換為 boolean 的函數
     */
    transformFrom?: (value: string | number | boolean) => boolean;
    /**
     * UFormField 的 UI 配置
     */
    fieldUi?: Record<string, any>;
}

const props = withDefaults(defineProps<Props>(), {
    label: "狀態",
    name: "status",
    disabled: false,
    required: false,
    error: undefined,
    fieldUi: undefined
});

const emit = defineEmits<{
    (e: "update:modelValue", value: string | number | boolean): void;
}>();

// 狀態的計算屬性，用於 UCheckbox 的 v-model（需要 boolean 類型）
const statusBoolean = computed({
    get: () => {
        const value = props.modelValue;

        // 如果提供了自定義轉換函數，使用它
        if (props.transformFrom) {
            return props.transformFrom(value);
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
        if (props.transformTo) {
            emit("update:modelValue", props.transformTo(value));
            return;
        }

        // 默認轉換邏輯：根據原始類型決定如何設置
        const currentValue = props.modelValue;
        if (typeof currentValue === "number") {
            emit("update:modelValue", value ? 1 : 0);
        } else if (typeof currentValue === "string") {
            emit("update:modelValue", value ? "1" : "0");
        } else {
            emit("update:modelValue", value);
        }
    }
});
</script>

<template>
    <UFormField
        :label="label"
        :name="name"
        :error="error"
        :required="required"
        :ui="fieldUi">
        <UCheckbox
            v-model="statusBoolean"
            :label="STATUS_LABEL_MAP[statusBoolean ? '1' : '0']"
            :disabled="disabled" />
    </UFormField>
</template>
