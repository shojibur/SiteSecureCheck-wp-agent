<template>
  <dialog v-if="modelValue" open class="p-0">
    <div class="p-4 w-[420px]">
      <h3 class="font-semibold mb-2">{{ title }}</h3>
      <p class="text-sm text-gray-700">{{ message }}</p>
      <div class="mt-3 flex justify-end gap-2">
        <button class="px-3 py-2" @click="$emit('update:modelValue', false)">Cancel</button>
        <button class="px-3 py-2 bg-red-600 text-white rounded" @click="emitConfirm">Confirm</button>
      </div>
    </div>
  </dialog>
</template>

<script setup>
const props = defineProps({ modelValue: Boolean, title: String, message: String })
const emit = defineEmits(['update:modelValue','confirm'])
function emitConfirm(){ emit('update:modelValue', false); emit('confirm') }
</script>

