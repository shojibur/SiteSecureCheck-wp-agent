<template>
  <input v-bind="$attrs" :value="modelValue" @input="$emit('update:modelValue', $event.target.value)" class="h-9 w-full rounded-md border border-gray-300 px-3 focus:ring-2 focus:ring-black/30 focus:outline-none" />
</template>
<script setup>
defineProps({ modelValue: [String, Number] })
defineEmits(['update:modelValue'])
</script>

