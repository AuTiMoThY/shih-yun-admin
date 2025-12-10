<script setup lang="ts">
    const modalOpen = ref(false);
</script>
<template>
    <UModal
        v-model:open="modalOpen"
        title="編輯層級"
        description=""
        :close="{
            color: 'primary',
            variant: 'outline',
            class: 'rounded-full'
        }">
    </UModal>
</template>