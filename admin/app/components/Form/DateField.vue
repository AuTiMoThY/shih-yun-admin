<script setup lang="ts">
import {
    CalendarDate,
    DateFormatter,
    getLocalTimeZone
} from "@internationalized/date";
import type { DateValue } from "@internationalized/date";
import { shallowRef, watch } from "vue";

interface Props {
    /**
     * 日期欄位的值（格式：YYYY-MM-DD）
     */
    modelValue: string;
    /**
     * 錯誤訊息
     */
    error?: string | boolean;
    /**
     * 是否禁用
     */
    disabled?: boolean;
    /**
     * 標籤文字（默認為"日期"）
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
     * 預設日期（當 modelValue 為空時使用）
     */
    defaultDate?: CalendarDate;
    /**
     * 日期格式化器的語言環境（默認為 "zh-TW"）
     */
    locale?: string;
    /**
     * 日期格式化器的樣式（默認為 "long"）
     */
    dateStyle?: "full" | "long" | "medium" | "short";
    /**
     * 占位符文字（默認為 "請選擇日期"）
     */
    placeholder?: string;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: "",
    label: "日期",
    name: "date",
    disabled: false,
    required: false,
    error: undefined,
    defaultDate: () => new CalendarDate(2024, 1, 1),
    locale: "zh-TW",
    dateStyle: "long",
    placeholder: "請選擇日期"
});

const emit = defineEmits<{
    (e: "update:modelValue", value: string): void;
}>();

// 日期選擇器格式
const df = new DateFormatter(props.locale, {
    dateStyle: props.dateStyle
});

// 將字符串日期轉換為 CalendarDate
const parseDateString = (dateString: string | null | undefined): CalendarDate | null => {
    if (!dateString) return null;
    const parts = dateString.split("-");
    if (parts.length !== 3) return null;
    const year = Number(parts[0] || "2024");
    const month = Number(parts[1] || "1");
    const day = Number(parts[2] || "1");
    if (isNaN(year) || isNaN(month) || isNaN(day)) return null;
    try {
        return new CalendarDate(year, month, day);
    } catch {
        return null;
    }
};

// 日期選擇器值
const dateValue = shallowRef<CalendarDate>(
    parseDateString(props.modelValue) || props.defaultDate
);

// 日期選擇器文字
const dateText = computed(() => {
    if (!dateValue.value) return props.placeholder;
    try {
        return df.format(dateValue.value.toDate(getLocalTimeZone()));
    } catch {
        return props.placeholder;
    }
});

// 日期選擇器更新
const handleDateUpdate = (
    date:
        | DateValue
        | DateValue[]
        | { start?: DateValue; end?: DateValue }
        | null
        | undefined
) => {
    if (date && !Array.isArray(date) && !("start" in date)) {
        // 確保是 CalendarDate 類型
        let calendarDate: CalendarDate;
        if (date instanceof CalendarDate) {
            calendarDate = date;
        } else if ("year" in date && "month" in date && "day" in date) {
            // 如果是其他 DateValue 類型（如 CalendarDateTime），提取日期部分
            calendarDate = new CalendarDate(
                date.year as number,
                date.month as number,
                date.day as number
            );
        } else {
            // 如果無法解析，直接返回
            return;
        }
        const dateString = calendarDate.toString();
        dateValue.value = calendarDate;
        emit("update:modelValue", dateString);
    }
};

// 監聽外部 modelValue 變化
watch(
    () => props.modelValue,
    (newValue) => {
        const parsed = parseDateString(newValue);
        if (parsed) {
            // 只有在解析成功且與當前值不同時才更新
            const currentString = dateValue.value?.toString();
            if (currentString !== newValue) {
                dateValue.value = parsed;
            }
        } else if (!newValue && dateValue.value) {
            // 如果新值為空，重置為預設日期
            dateValue.value = props.defaultDate;
        }
    },
    { immediate: true }
);
</script>

<template>
    <UFormField
        :label="label"
        :name="name"
        :error="error"
        :required="required">
        <UPopover
            :content="{
                side: 'bottom',
                align: 'start'
            }">
            <UButton
                color="neutral"
                variant="outline"
                icon="i-lucide-calendar"
                class="w-full"
                :disabled="disabled">
                {{ dateText }}
            </UButton>

            <template #content>
                <UCalendar
                    v-model="dateValue"
                    class="p-2"
                    :locale="locale"
                    :ui="{ cell: 'cursor-pointer' }"
                    @update:model-value="handleDateUpdate" />
            </template>
        </UPopover>
    </UFormField>
</template>

