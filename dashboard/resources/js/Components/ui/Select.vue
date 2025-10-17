<template>
  <select v-bind="$attrs" :value="modelValue" @change="$emit('update:modelValue', $event.target.value)" class="h-9 w-full rounded-md border border-gray-300 px-3 focus:ring-2 focus:ring-black/30 focus:outline-none">
    <slot />
  </select>
</template>
<script setup>
defineProps({ modelValue: [String, Number, Boolean] })
defineEmits(['update:modelValue'])
</script>

