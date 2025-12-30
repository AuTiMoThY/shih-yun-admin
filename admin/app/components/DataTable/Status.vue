<script setup lang="ts">
import { STATUS_ICON_MAP } from "~/constants/system/status_icon";
import { STATUS_LABEL_MAP } from "~/constants/system/status";
const props = defineProps<{
    status: string | number | boolean;
}>();
</script>
<template>
    <div class="flex items-center gap-2">
        <UIcon
            :name="
                STATUS_ICON_MAP[String(props.status)] ?? 'i-lucide-help-circle'
            "
            :class="
                Number(props.status) === 1 ? 'text-emerald-500' : 'text-rose-500'
            " />
        <span>
            {{ STATUS_LABEL_MAP[String(props.status)] ?? props.status }}
        </span>
    </div>
</template>
