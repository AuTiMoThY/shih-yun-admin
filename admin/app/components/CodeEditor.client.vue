<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from "vue";
import { Codemirror } from "vue-codemirror";
import { html } from "@codemirror/lang-html";
import { oneDark } from "@codemirror/theme-one-dark";
import { EditorView } from "@codemirror/view";
import { html as beautifyHtml } from "js-beautify";

const props = defineProps<{
    modelValue: string;
    disabled?: boolean;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", value: string): void;
}>();

const code = ref("");
const isInternalUpdate = ref(false);
let debounceTimer: number | null = null;

// HTML 格式化函數（美化）
const formatHtml = (html: string): string => {
    if (!html || html.trim() === "") return "";
    try {
        return beautifyHtml(html, {
            indent_size: 2,
            indent_char: " ",
            wrap_line_length: 80,
            preserve_newlines: true,
            end_with_newline: true,
            indent_inner_html: true,
            indent_scripts: "normal",
            wrap_attributes: "auto",
            unformatted: ["code", "pre"]
        });
    } catch (error) {
        console.error("HTML 格式化錯誤:", error);
        return html;
    }
};

// HTML 壓縮函數（移除多餘空白和換行）
const minifyHtml = (html: string): string => {
    if (!html || html.trim() === "") return "";
    try {
        return html
            .replace(/\s+/g, " ") // 將多個空白字元替換為單一空白
            .replace(/>\s+</g, "><") // 移除標籤之間的空白
            .replace(/^\s+|\s+$/g, "") // 移除首尾空白
            .trim();
    } catch (error) {
        console.error("HTML 壓縮錯誤:", error);
        return html;
    }
};

// 檢查 HTML 是否已經格式化（簡單判斷：如果有多行縮排，認為已格式化）
const isFormatted = (html: string): boolean => {
    if (!html) return false;
    // 檢查是否包含換行和縮排
    return html.includes("\n") && (html.includes("  ") || html.includes("\t"));
};

// 檢查是否為深色模式
const isDark = computed(() => {
    if (typeof document !== "undefined") {
        return document.documentElement.classList.contains("dark");
    }
    return false;
});

// 建立 extensions
const extensions = computed(() => {
    const exts = [
        html(),
        EditorView.theme({
            "&": {
                fontSize: "14px"
            },
            ".cm-scroller": {
                overflow: "auto",
                fontFamily:
                    "ui-monospace, SFMono-Regular, 'SF Mono', Menlo, Consolas, 'Liberation Mono', monospace"
            },
            ".cm-editor": {
                borderRadius: "0.5rem",
                border: "1px solid rgb(229 231 235)"
            },
            ".cm-focused": {
                outline: "none",
                borderColor: "rgb(59 130 246)"
            }
        })
    ];

    // 如果是深色模式，使用深色主題
    if (isDark.value) {
        exts.push(oneDark);
    }

    return exts;
});

// 處理編輯器內容變更（使用 debounce 優化性能）
const handleChange = (value: string) => {
    code.value = value; // 保持編輯器中的格式（格式化後的版本）
    
    // 清除之前的計時器
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }
    
    // 使用 debounce，在用戶停止輸入 300ms 後才壓縮並發送
    debounceTimer = setTimeout(() => {
        isInternalUpdate.value = true;
        // 發送壓縮後的版本給父組件（儲存到資料庫）
        const minified = minifyHtml(value);
        emit("update:modelValue", minified);
        
        // 確保更新完成後重置標記
        setTimeout(() => {
            isInternalUpdate.value = false;
        }, 100);
    }, 300);
};

// 監聽外部 modelValue 變化（從資料庫載入時）
watch(
    () => props.modelValue,
    (newValue) => {
        // 如果是內部更新造成的，忽略（因為我們已經有格式化版本在編輯器中）
        if (isInternalUpdate.value) {
            return;
        }

        const newValueStr = newValue || "";
        const currentFormatted = code.value || "";
        
        // 比較壓縮後的版本，如果相同則不需要更新
        const newMinified = minifyHtml(newValueStr);
        const currentMinified = minifyHtml(currentFormatted);
        
        if (newMinified === currentMinified) {
            return;
        }

        // 如果外部值已經格式化，直接使用；否則格式化後顯示
        if (isFormatted(newValueStr)) {
            code.value = newValueStr;
        } else {
            // 如果外部值是壓縮的，格式化後顯示
            const formatted = formatHtml(newValueStr);
            code.value = formatted;
        }
    },
    { immediate: true }
);

onMounted(() => {
    // 初始化時格式化顯示
    const initialValue = props.modelValue || "";
    if (initialValue && !isFormatted(initialValue)) {
        code.value = formatHtml(initialValue);
    } else {
        code.value = initialValue;
    }
});

onUnmounted(() => {
    // 清理 debounce 計時器
    if (debounceTimer) {
        clearTimeout(debounceTimer);
        debounceTimer = null;
    }
});
</script>

<template>
    <Codemirror
        v-model="code"
        :style="{
            minHeight: '300px',
            maxHeight: '600px'
        }"
        :disabled="disabled"
        :extensions="extensions"
        :autofocus="false"
        placeholder="請輸入 HTML 原始碼"
        @change="handleChange" />
</template>


<style scoped>
:deep(.cm-editor) {
    background-color: rgb(249 250 251);
}

.dark :deep(.cm-editor) {
    background-color: rgb(17 24 39);
}

:deep(.cm-scroller) {
    font-family: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas,
        "Liberation Mono", monospace;
}
</style>
