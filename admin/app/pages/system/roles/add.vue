<script setup lang="ts">
definePageMeta({
    middleware: ["auth", "permission"]
});
const mode = "add";
const { data } = useRole();
</script>
<template>
    <RoleFormPage :mode="mode" :data="data" />
</template>
