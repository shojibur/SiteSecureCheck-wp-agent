<template>
  <div v-if="modelValue" class="fixed inset-0 z-40">
    <div class="absolute inset-0 bg-black/40" @click="$emit('update:modelValue', false)"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
      <div class="w-full max-w-xl rounded-lg bg-white shadow-lg">
        <div class="p-4 border-b flex items-center justify-between">
          <h3 class="font-semibold">{{ title }}</h3>
          <button class="text-gray-500" @click="$emit('update:modelValue', false)">✕</button>
        </div>
        <div class="p-4">
          <slot />
        </div>
        <div v-if="$slots.footer" class="p-3 border-t bg-gray-50 flex justify-end gap-2">
          <slot name="footer" />
        </div>
      </div>
    </div>
  </div>
  </template>
<script setup>
defineProps({ modelValue: Boolean, title: String })
defineEmits(['update:modelValue'])
</script>

