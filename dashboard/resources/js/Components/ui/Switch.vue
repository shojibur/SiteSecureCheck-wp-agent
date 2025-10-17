<template>
  <button type="button" :aria-pressed="modelValue" @click="$emit('update:modelValue', !modelValue)" :class="modelValue ? 'bg-black' : 'bg-gray-300'" class="relative inline-flex h-6 w-10 items-center rounded-full transition-colors">
    <span :class="modelValue ? 'translate-x-5' : 'translate-x-1'" class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform" />
  </button>
</template>
<script setup>
defineProps({ modelValue: Boolean })
defineEmits(['update:modelValue'])
</script>

