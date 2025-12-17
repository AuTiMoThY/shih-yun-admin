<script setup lang="ts">
import { TextStyle, Color } from "@tiptap/extension-text-style";
const props = defineProps({
    modelValue: {
        type: String,
        default: ""
    }
});

const emit = defineEmits<{
    (e: "update:modelValue", value: string): void;
}>();

const { uploadImage } = useImageUpload();
const toast = useToast();

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        TiptapStarterKit,
        TiptapImage.configure({
            inline: false,
            allowBase64: false
        }),
        TextStyle,
        Color
    ],
    onUpdate: ({ editor }) => {
        emit("update:modelValue", editor.getHTML());
    }
});

// 圖片上傳處理
const imageInputRef = ref<HTMLInputElement | null>(null);
const isUploadingImage = ref(false);

const handleImageUpload = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) return;

    // 驗證檔案類型
    const acceptTypes = ["image/jpeg", "image/jpg", "image/png", "image/gif"];
    if (!acceptTypes.includes(file.type)) {
        toast.add({
            title: "檔案格式錯誤",
            description: "請選擇 JPG、PNG 或 GIF 圖片",
            color: "error"
        });
        return;
    }

    // 驗證檔案大小（5MB）
    const maxSize = 5 * 1024 * 1024;
    if (file.size > maxSize) {
        toast.add({
            title: "檔案過大",
            description: "圖片大小不能超過 5MB",
            color: "error"
        });
        return;
    }

    isUploadingImage.value = true;

    try {
        const imageUrl = await uploadImage(file);
        if (imageUrl && editor.value) {
            editor.value.chain().focus().setImage({ src: imageUrl }).run();
        }
    } catch (error) {
        console.error("圖片上傳錯誤:", error);
    } finally {
        isUploadingImage.value = false;
        // 清空 input 值，允許重新選擇相同檔案
        if (imageInputRef.value) {
            imageInputRef.value.value = "";
        }
    }
};

const triggerImageUpload = () => {
    imageInputRef.value?.click();
};

// 文字顏色選擇器
const textColor = ref<string>("#000000");
// 更新顏色選擇器顯示的顏色（從編輯器同步）
const updateColorFromEditor = () => {
    if (!editor.value) return;

    const currentColor = editor.value.getAttributes("textStyle").color;
    // 只在顏色真的改變時才更新，避免觸發不必要的 watch
    if (currentColor && currentColor !== textColor.value) {
        textColor.value = currentColor;
    } else if (!currentColor && textColor.value !== "#000000") {
        // 如果沒有顏色，使用預設黑色
        textColor.value = "#000000";
    }
};

// 監聽外部 modelValue 變更
watch(
    () => props.modelValue,
    (newValue) => {
        if (editor.value && editor.value.getHTML() !== newValue) {
            editor.value.commands.setContent(newValue, { emitUpdate: false });
        }
    }
);

// 監聽編輯器初始化，設置選擇變化監聽
watch(
    () => editor.value,
    (editorInstance) => {
        if (editorInstance) {
            // 初始更新
            updateColorFromEditor();

            // 監聽選擇變化，更新顏色選擇器顯示的顏色
            editorInstance.on("selectionUpdate", () => {
                updateColorFromEditor();
            });
        }
    },
    { immediate: true }
);

// 監聽顏色選擇器變化，應用到編輯器
watch(textColor, (newColor) => {
    if (editor.value && newColor) {
        // 檢查當前選中的文字顏色是否已經是這個顏色，避免重複設置
        const currentColor = editor.value.getAttributes("textStyle").color;
        if (currentColor !== newColor) {
            editor.value.chain().focus().setColor(newColor).run();
        }
    }
});

onBeforeUnmount(() => {
    if (editor.value) {
        editor.value.destroy();
    }
});
</script>

<template>
    <div class="tiptap-editor">
        <!-- 工具列 -->
        <div
            v-if="editor"
            class="toolbar border-b border-gray-200 p-2 flex flex-wrap gap-1">
            <!-- 文字顏色選擇器 -->
            <UPopover :dismissible="false">
                <UTooltip text="文字顏色">
                    <UButton
                    :icon="'i-lucide-palette'"

                        variant="ghost"
                        size="xs"
                        color="neutral"
                        :style="{ color: textColor }">
                    </UButton>
                </UTooltip>

                <template #content="{ close }">
                    <div class="relative">
                        <UButton
                            color="neutral"
                            variant="ghost"
                            icon="i-lucide-x"
                            size="xs"
                            class="absolute top-2 right-2 z-10"
                            @click="close" />
                        <UColorPicker
                            v-model="textColor"
                            format="hex"
                            class="p-4 pt-10" />
                    </div>
                </template>
            </UPopover>

            <div class="w-px h-6 bg-gray-300 mx-1" />
            <!-- 文字格式 -->
            <UTooltip text="粗體">
                <UButton
                    :icon="'i-lucide-bold'"
                    variant="ghost"
                    size="xs"
                    :color="editor.isActive('bold') ? 'primary' : 'neutral'"
                    :disabled="!editor.can().chain().focus().toggleBold().run()"
                    @click="editor.chain().focus().toggleBold().run()" />
            </UTooltip>
            <UTooltip text="斜體">
                <UButton
                    :icon="'i-lucide-italic'"
                    variant="ghost"
                    size="xs"
                    :color="editor.isActive('italic') ? 'primary' : 'neutral'"
                    :disabled="
                        !editor.can().chain().focus().toggleItalic().run()
                    "
                    @click="editor.chain().focus().toggleItalic().run()" />
            </UTooltip>
            <UTooltip text="刪除線">
                <UButton
                    :icon="'i-lucide-strikethrough'"
                    variant="ghost"
                    size="xs"
                    :color="editor.isActive('strike') ? 'primary' : 'neutral'"
                    :disabled="
                        !editor.can().chain().focus().toggleStrike().run()
                    "
                    @click="editor.chain().focus().toggleStrike().run()" />
            </UTooltip>
            <UTooltip text="行內程式碼">
                <UButton
                    :icon="'i-lucide-code'"
                    variant="ghost"
                    size="xs"
                    :color="editor.isActive('code') ? 'primary' : 'neutral'"
                    :disabled="!editor.can().chain().focus().toggleCode().run()"
                    @click="editor.chain().focus().toggleCode().run()" />
            </UTooltip>

            <div class="w-px h-6 bg-gray-300 mx-1" />

            <!-- 清除格式 -->
            <UTooltip text="清除格式">
                <UButton
                    :icon="'i-lucide-eraser'"
                    variant="ghost"
                    size="xs"
                    color="neutral"
                    @click="editor.chain().focus().unsetAllMarks().run()" />
            </UTooltip>
            <UTooltip text="清除節點">
                <UButton
                    :icon="'i-lucide-x-circle'"
                    variant="ghost"
                    size="xs"
                    color="neutral"
                    @click="editor.chain().focus().clearNodes().run()" />
            </UTooltip>

            <div class="w-px h-6 bg-gray-300 mx-1" />

            <!-- 段落樣式 -->
            <UTooltip text="段落">
                <UButton
                    :icon="'i-lucide-pilcrow'"
                    variant="ghost"
                    size="xs"
                    :color="
                        editor.isActive('paragraph') ? 'primary' : 'neutral'
                    "
                    @click="editor.chain().focus().setParagraph().run()" />
            </UTooltip>
            <UTooltip text="標題 1">
                <UButton
                    label="H1"
                    variant="ghost"
                    size="xs"
                    :color="
                        editor.isActive('heading', { level: 1 })
                            ? 'primary'
                            : 'neutral'
                    "
                    @click="
                        editor.chain().focus().toggleHeading({ level: 1 }).run()
                    "
                    :ui="{ label: 'font-bold text-base' }" />
            </UTooltip>
            <UTooltip text="標題 2">
                <UButton
                    label="H2"
                    variant="ghost"
                    size="xs"
                    :color="
                        editor.isActive('heading', { level: 2 })
                            ? 'primary'
                            : 'neutral'
                    "
                    @click="
                        editor.chain().focus().toggleHeading({ level: 2 }).run()
                    "
                    :ui="{ label: 'font-semibold text-sm' }" />
            </UTooltip>
            <UTooltip text="標題 3">
                <UButton
                    label="H3"
                    variant="ghost"
                    size="xs"
                    :color="
                        editor.isActive('heading', { level: 3 })
                            ? 'primary'
                            : 'neutral'
                    "
                    @click="
                        editor.chain().focus().toggleHeading({ level: 3 }).run()
                    "
                    :ui="{ label: 'font-medium text-xs' }" />
            </UTooltip>

            <div class="w-px h-6 bg-gray-300 mx-1" />

            <!-- 列表 -->
            <UTooltip text="項目符號">
                <UButton
                    :icon="'i-lucide-list'"
                    variant="ghost"
                    size="xs"
                    :color="
                        editor.isActive('bulletList') ? 'primary' : 'neutral'
                    "
                    @click="editor.chain().focus().toggleBulletList().run()" />
            </UTooltip>
            <UTooltip text="編號列表">
                <UButton
                    :icon="'i-lucide-list-ordered'"
                    variant="ghost"
                    size="xs"
                    :color="
                        editor.isActive('orderedList') ? 'primary' : 'neutral'
                    "
                    @click="editor.chain().focus().toggleOrderedList().run()" />
            </UTooltip>

            <div class="w-px h-6 bg-gray-300 mx-1" />

            <!-- 其他格式 -->
            <UTooltip text="程式碼區塊">
                <UButton
                    :icon="'i-lucide-code-2'"
                    variant="ghost"
                    size="xs"
                    :color="
                        editor.isActive('codeBlock') ? 'primary' : 'neutral'
                    "
                    @click="editor.chain().focus().toggleCodeBlock().run()" />
            </UTooltip>
            <UTooltip text="引用">
                <UButton
                    :icon="'i-lucide-quote'"
                    variant="ghost"
                    size="xs"
                    :color="
                        editor.isActive('blockquote') ? 'primary' : 'neutral'
                    "
                    @click="editor.chain().focus().toggleBlockquote().run()" />
            </UTooltip>
            <UTooltip text="水平線">
                <UButton
                    :icon="'i-lucide-minus'"
                    variant="ghost"
                    size="xs"
                    color="neutral"
                    @click="editor.chain().focus().setHorizontalRule().run()" />
            </UTooltip>

            <div class="w-px h-6 bg-gray-300 mx-1" />

            <!-- 圖片上傳 -->
            <input
                ref="imageInputRef"
                type="file"
                accept="image/jpeg,image/jpg,image/png,image/gif"
                class="hidden"
                @change="handleImageUpload" />
            <UTooltip text="插入圖片">
                <UButton
                    :icon="'i-lucide-image'"
                    variant="ghost"
                    size="xs"
                    color="neutral"
                    :loading="isUploadingImage"
                    :disabled="isUploadingImage"
                    @click="triggerImageUpload" />
            </UTooltip>

            <div class="w-px h-6 bg-gray-300 mx-1" />

            <!-- 復原/重做 -->
            <UTooltip text="復原">
                <UButton
                    :icon="'i-lucide-undo'"
                    variant="ghost"
                    size="xs"
                    color="neutral"
                    :disabled="!editor.can().chain().focus().undo().run()"
                    @click="editor.chain().focus().undo().run()" />
            </UTooltip>
            <UTooltip text="重做">
                <UButton
                    :icon="'i-lucide-redo'"
                    variant="ghost"
                    size="xs"
                    color="neutral"
                    :disabled="!editor.can().chain().focus().redo().run()"
                    @click="editor.chain().focus().redo().run()" />
            </UTooltip>
        </div>

        <!-- 編輯器內容 -->
        <div class="editor-content">
            <TiptapEditorContent :editor="editor" />
        </div>
    </div>
</template>

<style scoped>
@reference '~/assets/css/main.css';
.tiptap-editor {
    @apply border border-gray-300 rounded-lg overflow-hidden;
}

.editor-content :deep(.ProseMirror) {
    @apply p-4 min-h-[300px] outline-none;
}

.editor-content :deep(.ProseMirror p.is-editor-empty:first-child::before) {
    @apply text-gray-400;
    content: attr(data-placeholder);
    float: left;
    height: 0;
    pointer-events: none;
}

.editor-content :deep(.ProseMirror img) {
    @apply max-w-full h-auto rounded-lg;
}

.editor-content :deep(.ProseMirror h1) {
    @apply text-3xl font-bold mt-4 mb-2;
}

.editor-content :deep(.ProseMirror h2) {
    @apply text-2xl font-bold mt-4 mb-2;
}

.editor-content :deep(.ProseMirror h3) {
    @apply text-xl font-bold mt-4 mb-2;
}

.editor-content :deep(.ProseMirror ul),
.editor-content :deep(.ProseMirror ol) {
    @apply pl-6 my-2;
}

.editor-content :deep(.ProseMirror ul) {
    @apply list-disc;
}

.editor-content :deep(.ProseMirror ol) {
    @apply list-decimal;
}

.editor-content :deep(.ProseMirror blockquote) {
    @apply border-l-4 border-gray-300 pl-4 italic my-2;
}

.editor-content :deep(.ProseMirror code) {
    @apply bg-gray-100 px-1 py-0.5 rounded text-sm font-mono;
}

.editor-content :deep(.ProseMirror pre) {
    @apply bg-gray-100 p-4 rounded-lg my-2 overflow-x-auto;
}

.editor-content :deep(.ProseMirror pre code) {
    @apply bg-transparent p-0;
}
</style>
