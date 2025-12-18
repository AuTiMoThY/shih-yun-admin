<script setup lang="ts">
const open = defineModel<boolean>("open", { default: false });

const props = defineProps<{
    title?: string;
    description?: string;
    onConfirm: () => void;
}>();
</script>

<template>
    <UModal
        v-model:open="open"
        :title="props.title || '確認刪除'"
        :close="{
            color: 'primary',
            variant: 'outline',
            class: 'rounded-full'
        }">
        <template #body>
            <div class="">
                <div class="flex items-start gap-3">
                    <UIcon
                        name="i-lucide-alert-triangle"
                        class="w-6 h-6 text-red-500 shrink-0 mt-0.5" />
                    <div>
                        <p class="text-base text-gray-500 mt-1">
                            {{
                                props.description ||
                                "此操作無法復原，資料將會被永久刪除。"
                            }}
                        </p>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <UButton
                        label="取消"
                        color="neutral"
                        variant="outline"
                        @click="open = false" />
                    <UButton
                        label="確認刪除"
                        color="error"
                        icon="i-lucide-trash-2"
                        @click="props.onConfirm()" />
                </div>
            </div>
        </template>
    </UModal>
</template>
