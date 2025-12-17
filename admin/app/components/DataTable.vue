<script setup lang="ts">
import type { TableColumn } from "@nuxt/ui";

interface Props {
    data: any[];
    columns: TableColumn<any>[];
    loading?: boolean;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
    class: "shrink-0"
});
</script>

<template>
    <UTable
        ref="table"
        :class="props.class"
        :data="data"
        :columns="columns"
        :loading="loading"
        :ui="{
            base: 'table-fixed border-separate border-spacing-0',
            thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
            tbody: '[&>tr]:last:[&>td]:border-b-0',
            th: 'py-2 first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r',
            td: 'border-b border-default',
            separator: 'h-0'
        }" />
</template>
